<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Legacy;

use App\Modules\Catalog\DTOs\LegacyCatalogImportReport;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Title;

final readonly class LegacyCatalogImportAnalyzer
{
    public function __construct(
        private PhpMyAdminJsonTableReader $reader,
        private LegacyCatalogNormalizer $normalizer,
    ) {}

    public function analyze(
        string $mediaPath,
        ?string $topicsPath = null,
        ?string $signaturesPath = null,
    ): LegacyCatalogImportReport {
        $mediaRows = $this->reader->read($mediaPath, 'mediaList');
        $topicRows = $topicsPath !== null ? $this->reader->read($topicsPath, 'mediaTopicList') : [];
        $signatureRows = $signaturesPath !== null ? $this->reader->read($signaturesPath, 'mediaSignatures') : [];

        $warnings = [];
        $conflicts = [];
        $barcodes = [];
        $legacyMediaIds = [];
        $titleKeys = [];
        $editionKeys = [];
        $encodingWarnings = 0;

        foreach ($mediaRows as $index => $row) {
            $record = $this->normalizer->media($row);
            $rowNumber = $index + 1;

            foreach ($record['warnings'] as $warning) {
                $warnings[] = "mediaList Zeile {$rowNumber}: {$warning}";

                if (str_contains($warning, 'Zeichensatzartefakte')) {
                    $encodingWarnings++;
                }
            }

            foreach ($record['errors'] as $error) {
                $conflicts[] = "mediaList Zeile {$rowNumber}: {$error}";
            }

            $barcode = (string) $record['copy']['barcode'];
            $legacyMediaId = $record['copy']['legacy_media_id'];

            if ($barcode !== '') {
                if (isset($barcodes[$barcode])) {
                    $conflicts[] = "Barcode [{$barcode}] kommt mehrfach in mediaList vor (Zeilen {$barcodes[$barcode]} und {$rowNumber}).";
                }

                $barcodes[$barcode] = $rowNumber;
                $this->checkExistingCopy($barcode, is_string($legacyMediaId) ? $legacyMediaId : null, $conflicts);
            }

            if (is_string($legacyMediaId) && $legacyMediaId !== '') {
                if (isset($legacyMediaIds[$legacyMediaId]) && $legacyMediaIds[$legacyMediaId] !== $barcode) {
                    $conflicts[] = "Legacy media_id [{$legacyMediaId}] verweist auf mehrere Inventarnummern.";
                }

                $legacyMediaIds[$legacyMediaId] = $barcode;
            }

            $titleKey = $this->titleKey(
                (string) $record['title']['preferred_title'],
                $record['title']['subtitle'],
            );
            $titleKeys[$titleKey] = true;
            $editionKeys[$record['edition_key']] = true;
        }

        foreach (array_keys($titleKeys) as $titleKey) {
            [$title, $subtitle] = explode("\0", $titleKey, 2);
            $query = Title::query()->where('preferred_title', $title);

            $subtitle === ''
                ? $query->whereNull('subtitle')
                : $query->where('subtitle', $subtitle);

            if ($query->count() > 1) {
                $conflicts[] = "Titel [{$title}] ist im Zielkatalog mehrfach exakt vorhanden; Legacy-Zuordnung wäre nicht eindeutig.";
            }
        }

        $topicIds = $this->analyzeTopics($topicRows, $warnings, $conflicts);
        $this->analyzeSignatures($signatureRows, $topicIds, $warnings, $conflicts);

        return new LegacyCatalogImportReport(
            summary: [
                'media_rows' => count($mediaRows),
                'distinct_barcodes' => count($barcodes),
                'planned_titles' => count($titleKeys),
                'planned_editions' => count($editionKeys),
                'topic_rows' => count($topicRows),
                'signature_rows' => count($signatureRows),
                'encoding_warning_rows' => $encodingWarnings,
                'warnings' => count(array_unique($warnings)),
                'conflicts' => count(array_unique($conflicts)),
            ],
            warnings: array_values(array_unique($warnings)),
            conflicts: array_values(array_unique($conflicts)),
        );
    }

    /** @param list<string> $conflicts */
    private function checkExistingCopy(string $barcode, ?string $legacyMediaId, array &$conflicts): void
    {
        $existingByBarcode = Copy::query()->where('barcode', $barcode)->first();

        if ($existingByBarcode !== null) {
            $sameLegacyRecord = $existingByBarcode->legacy_source === LegacyCatalogNormalizer::SOURCE
                && $legacyMediaId !== null
                && $existingByBarcode->legacy_media_id === $legacyMediaId;

            if (! $sameLegacyRecord) {
                $conflicts[] = "Inventarnummer/Barcode [{$barcode}] existiert bereits im Zielkatalog.";
            }
        }

        if ($legacyMediaId === null) {
            return;
        }

        $existingByLegacyId = Copy::query()
            ->where('legacy_source', LegacyCatalogNormalizer::SOURCE)
            ->where('legacy_media_id', $legacyMediaId)
            ->first();

        if ($existingByLegacyId !== null && $existingByLegacyId->barcode !== $barcode) {
            $conflicts[] = "Legacy media_id [{$legacyMediaId}] ist bereits mit Barcode [{$existingByLegacyId->barcode}] verknüpft.";
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $warnings
     * @param  list<string>  $conflicts
     * @return array<string, true>
     */
    private function analyzeTopics(array $rows, array &$warnings, array &$conflicts): array
    {
        $ids = [];

        foreach ($rows as $index => $row) {
            $id = $this->scalarString($row['id'] ?? null);
            $name = $this->scalarString($row['topic'] ?? null);

            if ($id === null || $name === null) {
                $conflicts[] = 'mediaTopicList Zeile '.($index + 1).': id oder topic fehlt.';

                continue;
            }

            if (isset($ids[$id])) {
                $conflicts[] = "mediaTopicList enthält id [{$id}] mehrfach.";
            }

            $ids[$id] = true;
        }

        foreach ($rows as $row) {
            $parent = $this->scalarString($row['main_topic'] ?? null);

            if ($parent !== null && $parent !== '0' && ! isset($ids[$parent])) {
                $warnings[] = "Topic-Eltern-ID [{$parent}] fehlt im Export; der Topic wird als Wurzel importiert.";
            }
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, true>  $topicIds
     * @param  list<string>  $warnings
     * @param  list<string>  $conflicts
     */
    private function analyzeSignatures(array $rows, array $topicIds, array &$warnings, array &$conflicts): void
    {
        $signatures = [];

        foreach ($rows as $index => $row) {
            $signature = $this->scalarString($row['signature'] ?? null);
            $id = $this->scalarString($row['id'] ?? null);

            if ($signature === null || $id === null) {
                $conflicts[] = 'mediaSignatures Zeile '.($index + 1).': id oder signature fehlt.';

                continue;
            }

            if (isset($signatures[$signature]) && $signatures[$signature] !== $id) {
                $conflicts[] = "Signatur [{$signature}] kommt mit mehreren Legacy-IDs vor.";
            }

            $signatures[$signature] = $id;

            $signatureTopicIds = $this->topicIds($row['topic_ids'] ?? null);

            if ($signatureTopicIds === null) {
                $warnings[] = "Signatur [{$signature}] besitzt keine auswertbare topic_ids-Liste.";

                continue;
            }

            foreach ($signatureTopicIds as $topicId) {
                if (! isset($topicIds[$topicId])) {
                    $warnings[] = "Signatur [{$signature}] verweist auf fehlende Topic-ID [{$topicId}].";
                }
            }
        }
    }

    /** @return list<string>|null */
    private function topicIds(mixed $value): ?array
    {
        $decoded = is_array($value) ? $value : (is_string($value) ? json_decode($value, true) : null);

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            return null;
        }

        $ids = [];

        foreach ($decoded as $id) {
            if (is_scalar($id) && trim((string) $id) !== '') {
                $ids[] = trim((string) $id);
            }
        }

        return array_values(array_unique($ids));
    }

    private function scalarString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function titleKey(string $title, ?string $subtitle): string
    {
        return $title."\0".($subtitle ?? '');
    }
}
