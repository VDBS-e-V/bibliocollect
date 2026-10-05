<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\DTOs\CatalogImportMapping;
use App\Modules\Catalog\Enums\CatalogImportRowStatus;
use App\Modules\Catalog\Enums\CatalogImportStatus;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Exceptions\CatalogImportCommitException;
use App\Modules\Catalog\Models\CatalogImportBatch;
use App\Modules\Catalog\Models\CatalogImportRow;
use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use Illuminate\Support\Facades\DB;

final class CommitCatalogImportAction
{
    public function __construct(private readonly PreviewCatalogImportAction $preview) {}

    public function execute(CatalogImportBatch $batch): CatalogImportBatch
    {
        if ($batch->status === CatalogImportStatus::Committed) {
            throw new CatalogImportCommitException('Dieser Import wurde bereits übernommen.');
        }

        $mapping = $this->mappingFor($batch);
        $preflight = $this->preview->execute($batch, $mapping);

        if ($preflight->status !== CatalogImportStatus::Ready) {
            throw new CatalogImportCommitException('Der Import enthält blockierende Konflikte oder ungültige Zeilen.');
        }

        return DB::transaction(function () use ($batch, $mapping): CatalogImportBatch {
            $locked = CatalogImportBatch::query()
                ->whereKey($batch->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === CatalogImportStatus::Committed) {
                throw new CatalogImportCommitException('Dieser Import wurde bereits übernommen.');
            }

            $locked = $this->preview->execute($locked, $mapping);

            if ($locked->status !== CatalogImportStatus::Ready) {
                throw new CatalogImportCommitException('Der Import ist nicht mehr konfliktfrei und wurde nicht übernommen.');
            }

            $createdTitles = [];
            $createdEditions = [];
            $createdContributors = [];
            $createdContributions = [];
            $createdCopies = 0;

            foreach ($locked->rows as $row) {
                if ($row->status !== CatalogImportRowStatus::Valid || ! is_array($row->plan)) {
                    throw new CatalogImportCommitException('Der Import enthält eine nicht freigegebene Zeile.');
                }

                $data = $row->normalized_data;

                if (! is_array($data)) {
                    throw new CatalogImportCommitException('Für eine Importzeile fehlen normalisierte Daten.');
                }

                $title = $this->resolveTitle($row, $createdTitles);
                $edition = $this->resolveEdition($row, $title, $createdEditions);
                $contributor = $this->resolveContributor($row, $createdContributors);

                $this->resolveContribution(
                    $row,
                    $title,
                    $contributor,
                    $createdContributions,
                );

                $barcode = (string) ($data['barcode'] ?? '');

                if ($barcode === '') {
                    throw new CatalogImportCommitException('Eine freigegebene Importzeile besitzt keinen Barcode.');
                }

                $existingCopy = Copy::query()
                    ->where('barcode', $barcode)
                    ->lockForUpdate()
                    ->first();

                if ($existingCopy !== null) {
                    throw new CatalogImportCommitException("Barcode [{$barcode}] ist inzwischen bereits vorhanden.");
                }

                Copy::query()->create([
                    'edition_id' => $edition->getKey(),
                    'barcode' => $barcode,
                    'shelf_location' => $data['shelf_location'] ?? null,
                    'status' => CopyStatus::from((string) $data['copy_status']),
                ]);
                $createdCopies++;
            }

            $summary = is_array($locked->summary) ? $locked->summary : [];
            $summary['new_titles'] = count($createdTitles);
            $summary['new_editions'] = count($createdEditions);
            $summary['new_contributors'] = count($createdContributors);
            $summary['new_contributions'] = count($createdContributions);
            $summary['new_copies'] = $createdCopies;
            $summary['committed_rows'] = $createdCopies;

            $locked->forceFill([
                'status' => CatalogImportStatus::Committed,
                'summary' => $summary,
                'committed_at' => now(),
            ])->save();

            return $locked->fresh(['rows']) ?? $locked->load('rows');
        });
    }

    private function mappingFor(CatalogImportBatch $batch): CatalogImportMapping
    {
        if (! is_array($batch->mapping)) {
            throw new CatalogImportCommitException('Für diesen Import wurde noch kein Mapping bestätigt.');
        }

        return CatalogImportMapping::fromArray($batch->mapping);
    }

    /** @param array<string, Title> $created */
    private function resolveTitle(CatalogImportRow $row, array &$created): Title
    {
        $plan = $this->planItem($row, 'title');

        if (($plan['mode'] ?? null) === 'reuse') {
            return Title::query()->findOrFail((string) ($plan['id'] ?? ''));
        }

        $key = (string) ($plan['key'] ?? '');

        if (isset($created[$key])) {
            return $created[$key];
        }

        $data = $this->data($row);

        $title = Title::query()->create([
            'preferred_title' => (string) $data['preferred_title'],
            'subtitle' => $data['subtitle'] ?? null,
            'sort_title' => $data['sort_title'] ?? null,
        ]);
        $created[$key] = $title;

        return $title;
    }

    /** @param array<string, Edition> $created */
    private function resolveEdition(CatalogImportRow $row, Title $title, array &$created): Edition
    {
        $plan = $this->planItem($row, 'edition');

        if (($plan['mode'] ?? null) === 'reuse') {
            return Edition::query()->findOrFail((string) ($plan['id'] ?? ''));
        }

        $key = (string) ($plan['key'] ?? '');

        if (isset($created[$key])) {
            return $created[$key];
        }

        $data = $this->data($row);

        /** @var Edition $edition */
        $edition = $title->editions()->create([
            'edition_statement' => $data['edition_statement'] ?? null,
            'isbn' => $data['isbn'] ?? null,
            'publisher_name' => $data['publisher_name'] ?? null,
            'publication_year' => $data['publication_year'] ?? null,
            'media_type' => $data['media_type'] ?? null,
            'language_code' => $data['language_code'] ?? null,
            'minimum_age' => $data['minimum_age'] ?? null,
            'age_rating_label' => $data['age_rating_label'] ?? null,
        ]);
        $created[$key] = $edition;

        return $edition;
    }

    /** @param array<string, Contributor> $created */
    private function resolveContributor(CatalogImportRow $row, array &$created): ?Contributor
    {
        $plan = $row->plan['contributor'] ?? null;

        if (! is_array($plan)) {
            return null;
        }

        if (($plan['mode'] ?? null) === 'reuse') {
            return Contributor::query()->findOrFail((string) ($plan['id'] ?? ''));
        }

        $key = (string) ($plan['key'] ?? '');

        if (isset($created[$key])) {
            return $created[$key];
        }

        $data = $this->data($row);

        $contributor = Contributor::query()->create([
            'display_name' => (string) $data['contributor_name'],
            'sort_name' => $data['contributor_sort_name'] ?? null,
        ]);
        $created[$key] = $contributor;

        return $contributor;
    }

    /** @param array<string, TitleContribution> $created */
    private function resolveContribution(
        CatalogImportRow $row,
        Title $title,
        ?Contributor $contributor,
        array &$created,
    ): void {
        $plan = $row->plan['contribution'] ?? null;

        if (! is_array($plan) || ($plan['mode'] ?? null) === 'reuse') {
            return;
        }

        if ($contributor === null) {
            throw new CatalogImportCommitException('Eine geplante Verantwortlichkeit besitzt keinen Contributor.');
        }

        $key = (string) ($plan['key'] ?? '');

        if (isset($created[$key])) {
            return;
        }

        /** @var TitleContribution $contribution */
        $contribution = $title->contributions()->create([
            'contributor_id' => $contributor->getKey(),
            'role_key' => (string) ($plan['role_key'] ?? 'contributor'),
            'position' => (int) ($plan['position'] ?? 1),
        ]);
        $created[$key] = $contribution;
    }

    /** @return array<string, mixed> */
    private function planItem(CatalogImportRow $row, string $key): array
    {
        $plan = $row->plan[$key] ?? null;

        if (! is_array($plan)) {
            throw new CatalogImportCommitException("Für Importzeile {$row->row_number} fehlt der Plan [{$key}].");
        }

        return $plan;
    }

    /** @return array<string, string|int|null> */
    private function data(CatalogImportRow $row): array
    {
        if (! is_array($row->normalized_data)) {
            throw new CatalogImportCommitException("Für Importzeile {$row->row_number} fehlen normalisierte Daten.");
        }

        return $row->normalized_data;
    }
}
