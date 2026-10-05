<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CatalogImportMapping;
use App\Modules\Catalog\DTOs\CatalogImportRowAnalysis;
use App\Modules\Catalog\Enums\CatalogImportRowStatus;
use App\Modules\Catalog\Enums\CatalogImportStatus;
use App\Modules\Catalog\Import\CatalogImportNormalizer;
use App\Modules\Catalog\Models\CatalogImportBatch;
use App\Modules\Catalog\Models\CatalogImportRow;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Queries\CatalogImportMatchQuery;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class PreviewCatalogImportAction
{
    public function __construct(
        private readonly CatalogImportNormalizer $normalizer,
        private readonly CatalogImportMatchQuery $matches,
        private readonly CatalogIsbnNormalizer $isbnNormalizer,
    ) {}

    public function execute(CatalogImportBatch $batch, CatalogImportMapping $mapping): CatalogImportBatch
    {
        return DB::transaction(fn (): CatalogImportBatch => $this->evaluate($batch, $mapping));
    }

    private function evaluate(CatalogImportBatch $batch, CatalogImportMapping $mapping): CatalogImportBatch
    {
        if ($batch->status === CatalogImportStatus::Committed) {
            return $batch->load('rows');
        }

        $batch->forceFill([
            'status' => CatalogImportStatus::Previewed,
            'mapping' => $mapping->toArray(),
            'summary' => null,
        ])->save();

        /** @var Collection<int, CatalogImportRow> $rows */
        $rows = $batch->rows()->orderBy('row_number')->get();
        /** @var array<string, CatalogImportRowAnalysis> $analysis */
        $analysis = [];
        /** @var array<string, list<string>> $barcodes */
        $barcodes = [];
        /** @var array<string, list<string>> $isbnGroups */
        $isbnGroups = [];

        foreach ($rows as $row) {
            $result = $this->normalizer->normalize(
                $row->raw_data,
                $mapping,
                $row->source_errors,
            );

            $rowId = (string) $row->getKey();
            $analysis[$rowId] = new CatalogImportRowAnalysis(
                row: $row,
                data: $result->data,
                warnings: $result->warnings,
                errors: $result->errors,
            );

            $barcode = $result->data['barcode'] ?? null;

            if (is_string($barcode) && $barcode !== '') {
                $barcodes[$barcode][] = $rowId;
            }

            $isbn = $result->data['isbn'] ?? null;

            if (is_string($isbn) && $this->isbnNormalizer->isStandardFormat($isbn)) {
                $isbnGroups[$isbn][] = $rowId;
            }
        }

        foreach ($barcodes as $barcode => $rowIds) {
            if (count($rowIds) < 2) {
                continue;
            }

            foreach ($rowIds as $rowId) {
                $analysis[$rowId]->conflicts[] = "Barcode [{$barcode}] kommt mehrfach in dieser Importdatei vor.";
            }
        }

        $existingBarcodes = $this->matches->existingBarcodes(array_keys($barcodes));

        foreach ($existingBarcodes as $barcode) {
            foreach ($barcodes[$barcode] ?? [] as $rowId) {
                $analysis[$rowId]->conflicts[] = "Barcode [{$barcode}] ist bereits im Katalog vorhanden.";
            }
        }

        $editionsByIsbn = $this->matches->editionsByIsbns(array_keys($isbnGroups));

        foreach ($isbnGroups as $isbn => $rowIds) {
            if (count($rowIds) < 2) {
                continue;
            }

            $signatures = [];

            foreach ($rowIds as $rowId) {
                $signatures[$this->bibliographicSignature($analysis[$rowId]->data)] = true;
            }

            if (count($signatures) > 1) {
                foreach ($rowIds as $rowId) {
                    $analysis[$rowId]->conflicts[] = "ISBN [{$isbn}] ist innerhalb der Datei mit unterschiedlichen Titel-/Editionsdaten belegt.";
                }
            }
        }

        foreach ($analysis as $entry) {
            if ($entry->errors !== [] || $entry->conflicts !== []) {
                continue;
            }

            $isbn = $entry->data['isbn'] ?? null;
            $isbnMatches = is_string($isbn) && $isbn !== ''
                ? ($editionsByIsbn[$isbn] ?? [])
                : [];

            $entry->plan = $this->buildPlan(
                $entry->row,
                $entry->data,
                $isbnMatches,
                $entry->warnings,
                $entry->conflicts,
            );

            if ($entry->conflicts !== []) {
                $entry->plan = null;
            }
        }

        $summary = $this->emptySummary(count($rows));
        /** @var array<string, array<string, true>> $sets */
        $sets = [
            'new_titles' => [],
            'reused_titles' => [],
            'new_editions' => [],
            'reused_editions' => [],
            'new_contributors' => [],
            'new_contributions' => [],
        ];

        foreach ($analysis as $entry) {
            $row = $entry->row;
            $warnings = array_values(array_unique($entry->warnings));
            $conflicts = array_values(array_unique($entry->conflicts));
            $errors = array_values(array_unique($entry->errors));

            $status = CatalogImportRowStatus::Valid;

            if ($errors !== []) {
                $status = CatalogImportRowStatus::Invalid;
                $summary['invalid_rows']++;
            } elseif ($conflicts !== []) {
                $status = CatalogImportRowStatus::Conflict;
                $summary['conflict_rows']++;
            }

            $summary['warnings'] += count($warnings);
            $summary['conflicts'] += count($conflicts);

            $row->forceFill([
                'status' => $status,
                'normalized_data' => $entry->data,
                'plan' => $entry->plan,
                'warnings' => $warnings,
                'conflicts' => [...$errors, ...$conflicts],
            ])->save();

            if ($status !== CatalogImportRowStatus::Valid || ! is_array($entry->plan)) {
                continue;
            }

            $summary['valid_rows']++;
            $summary['new_copies']++;
            $this->collectPlanSummary($entry->plan, $sets);
        }

        foreach ($sets as $key => $values) {
            $summary[$key] = count($values);
        }

        $blocked = $summary['invalid_rows'] > 0 || $summary['conflict_rows'] > 0;

        $batch->forceFill([
            'status' => $blocked ? CatalogImportStatus::Blocked : CatalogImportStatus::Ready,
            'summary' => $summary,
        ])->save();

        return $batch->fresh(['rows']) ?? $batch->load('rows');
    }

    /**
     * @param  array<string, string|int|null>  $data
     * @param  list<Edition>  $isbnMatches
     * @param  list<string>  $warnings
     * @param  list<string>  $conflicts
     * @return array<string, mixed>|null
     */
    private function buildPlan(
        CatalogImportRow $row,
        array $data,
        array $isbnMatches,
        array &$warnings,
        array &$conflicts,
    ): ?array {
        $preferredTitle = (string) ($data['preferred_title'] ?? '');
        $isbn = $data['isbn'] ?? null;
        $hasMatchableIsbn = is_string($isbn) && $this->isbnNormalizer->isStandardFormat($isbn);
        $titlePlan = null;
        $editionPlan = null;

        if ($hasMatchableIsbn) {
            if (count($isbnMatches) > 1) {
                $conflicts[] = "ISBN [{$isbn}] ist im Katalog nicht eindeutig und kann nicht automatisch wiederverwendet werden.";

                return null;
            }

            if (count($isbnMatches) === 1) {
                $edition = $isbnMatches[0];
                $title = $edition->title;

                if (! $title instanceof Title) {
                    $conflicts[] = "Die bestehende Ausgabe für ISBN [{$isbn}] besitzt keinen gültigen Titelbezug.";

                    return null;
                }

                if ($title->preferred_title !== $preferredTitle) {
                    $conflicts[] = "ISBN [{$isbn}] gehört im Katalog zum Titel [{$title->preferred_title}], die Importzeile nennt aber [{$preferredTitle}].";

                    return null;
                }

                $titlePlan = [
                    'mode' => 'reuse',
                    'id' => (string) $title->getKey(),
                    'key' => 'title:existing:'.$title->getKey(),
                ];
                $editionPlan = [
                    'mode' => 'reuse',
                    'id' => (string) $edition->getKey(),
                    'key' => 'edition:existing:'.$edition->getKey(),
                ];
                $warnings[] = "Eindeutiger ISBN-Match [{$isbn}]: bestehender Titel und bestehende Ausgabe werden wiederverwendet; Importwerte überschreiben keine Stammdaten.";

                if ($this->editionDiffers($edition, $data)) {
                    $warnings[] = "Die Importwerte für ISBN [{$isbn}] weichen von vorhandenen Editionsdaten ab; die vorhandenen Werte bleiben unverändert.";
                }
            }
        }

        if ($titlePlan === null) {
            $titlePlan = $this->resolveTitlePlan($preferredTitle, $data, $conflicts);

            if (is_array($titlePlan) && $titlePlan['mode'] === 'reuse') {
                $warnings[] = "Eindeutiger exakter Haupttitel-Match [{$preferredTitle}]: der bestehende Titel wird wiederverwendet; abweichende Importwerte überschreiben keine Titeldaten.";
            }
        }

        if ($titlePlan === null) {
            return null;
        }

        if ($editionPlan === null) {
            $editionKey = $hasMatchableIsbn
                ? 'edition:new:isbn:'.$isbn
                : 'edition:new:row:'.$row->getKey();

            $editionPlan = [
                'mode' => 'create',
                'id' => null,
                'key' => $editionKey,
            ];
        }

        $contributorPlan = null;
        $contributionPlan = null;
        $contributorName = $data['contributor_name'] ?? null;
        $contributorSortName = $data['contributor_sort_name'] ?? null;
        $contributorRole = $data['contributor_role'] ?? null;

        if (is_string($contributorName) && $contributorName !== '' && is_string($contributorRole) && $contributorRole !== '') {
            $contributors = $this->matches->contributorsByExactName(
                $contributorName,
                is_string($contributorSortName) ? $contributorSortName : null,
            );

            if ($contributors->count() > 1) {
                $conflicts[] = "Contributor [{$contributorName}] ist im Katalog nicht eindeutig und wird nicht automatisch zusammengeführt.";

                return null;
            }

            if ($contributors->count() === 1) {
                $contributor = $contributors->firstOrFail();
                $contributorPlan = [
                    'mode' => 'reuse',
                    'id' => (string) $contributor->getKey(),
                    'key' => 'contributor:existing:'.$contributor->getKey(),
                ];
            } else {
                $contributorPlan = [
                    'mode' => 'create',
                    'id' => null,
                    'key' => 'contributor:new:'.hash('sha256', $contributorName."\n".(string) $contributorSortName),
                ];
            }

            $reuseContribution = $titlePlan['mode'] === 'reuse'
                && $contributorPlan['mode'] === 'reuse'
                && $this->matches->contributionExists(
                    (string) $titlePlan['id'],
                    (string) $contributorPlan['id'],
                    $contributorRole,
                );

            $contributionPlan = [
                'mode' => $reuseContribution ? 'reuse' : 'create',
                'key' => sprintf(
                    'contribution:%s:%s:%s',
                    $titlePlan['key'],
                    $contributorPlan['key'],
                    $contributorRole,
                ),
                'role_key' => $contributorRole,
                'position' => 1,
            ];
        }

        return [
            'title' => $titlePlan,
            'edition' => $editionPlan,
            'contributor' => $contributorPlan,
            'contribution' => $contributionPlan,
            'copy' => [
                'mode' => 'create',
                'key' => 'copy:new:'.(string) ($data['barcode'] ?? ''),
            ],
        ];
    }

    /**
     * @param  array<string, string|int|null>  $data
     * @param  list<string>  $conflicts
     * @return array{mode:string,id:string|null,key:string}|null
     */
    private function resolveTitlePlan(string $preferredTitle, array $data, array &$conflicts): ?array
    {
        $titles = $this->matches->titlesByPreferredTitle($preferredTitle);

        if ($titles->count() > 1) {
            $conflicts[] = "Haupttitel [{$preferredTitle}] ist im Katalog mehrfach vorhanden und kann ohne eindeutige ISBN nicht automatisch wiederverwendet werden.";

            return null;
        }

        if ($titles->count() === 1) {
            $title = $titles->firstOrFail();

            return [
                'mode' => 'reuse',
                'id' => (string) $title->getKey(),
                'key' => 'title:existing:'.$title->getKey(),
            ];
        }

        return [
            'mode' => 'create',
            'id' => null,
            'key' => 'title:new:'.hash('sha256', implode("\n", [
                $preferredTitle,
                (string) ($data['subtitle'] ?? ''),
                (string) ($data['sort_title'] ?? ''),
            ])),
        ];
    }

    /** @param array<string, string|int|null> $data */
    private function editionDiffers(Edition $edition, array $data): bool
    {
        foreach ([
            'edition_statement',
            'publisher_name',
            'publication_year',
            'media_type',
            'language_code',
            'minimum_age',
            'age_rating_label',
        ] as $field) {
            $incoming = $data[$field] ?? null;

            if ($incoming === null || $incoming === '') {
                continue;
            }

            if ((string) $edition->getAttribute($field) !== (string) $incoming) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, string|int|null> $data */
    private function bibliographicSignature(array $data): string
    {
        $fields = [
            'preferred_title',
            'subtitle',
            'sort_title',
            'edition_statement',
            'publisher_name',
            'publication_year',
            'media_type',
            'language_code',
            'minimum_age',
            'age_rating_label',
        ];
        $values = [];

        foreach ($fields as $field) {
            $values[] = (string) ($data[$field] ?? '');
        }

        return hash('sha256', implode("\n", $values));
    }

    /** @return array<string, int> */
    private function emptySummary(int $rowsTotal): array
    {
        return [
            'rows_total' => $rowsTotal,
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'conflict_rows' => 0,
            'new_titles' => 0,
            'reused_titles' => 0,
            'new_editions' => 0,
            'reused_editions' => 0,
            'new_contributors' => 0,
            'new_contributions' => 0,
            'new_copies' => 0,
            'warnings' => 0,
            'conflicts' => 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  array<string, array<string, true>>  $sets
     */
    private function collectPlanSummary(array $plan, array &$sets): void
    {
        foreach ([
            'title' => ['new_titles', 'reused_titles'],
            'edition' => ['new_editions', 'reused_editions'],
            'contributor' => ['new_contributors', null],
        ] as $planKey => [$newKey, $reuseKey]) {
            $item = $plan[$planKey] ?? null;

            if (! is_array($item)) {
                continue;
            }

            $key = (string) ($item['key'] ?? '');

            if (($item['mode'] ?? null) === 'create') {
                $sets[$newKey][$key] = true;
            } elseif ($reuseKey !== null && ($item['mode'] ?? null) === 'reuse') {
                $sets[$reuseKey][$key] = true;
            }
        }

        $contribution = $plan['contribution'] ?? null;

        if (is_array($contribution) && ($contribution['mode'] ?? null) === 'create') {
            $sets['new_contributions'][(string) ($contribution['key'] ?? '')] = true;
        }
    }
}
