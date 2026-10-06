<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Legacy;

use App\Modules\Catalog\DTOs\LegacyCatalogQualityReport;

final readonly class LegacyCatalogQualityAuditor
{
    /** @var list<string> */
    private const ARTIFACT_FIELDS = [
        'main_title',
        'subtitle',
        'authors_statement',
        'series_statement',
        'edition_statement',
        'edition_number',
        'main_author_name',
        'additional_contributors_json',
        'publication_place',
        'publisher',
        'local_classification',
        'physical_extent',
        'format_type',
        'summary',
        'subject_keywords',
        'subject_keywords_system',
        'target_audience',
        'location_signature',
    ];

    /** @var list<string> */
    private const CONTRIBUTOR_NAME_KEYS = [
        'name',
        'display_name',
        'contributor',
        'author',
        'person',
        'corporate_name',
    ];

    /** @var list<string> */
    private const COMMON_MOJIBAKE = [
        'Ã¤',
        'Ã¶',
        'Ã¼',
        'Ã„',
        'Ã–',
        'Ãœ',
        'ÃŸ',
        'â€“',
        'â€”',
        'â€ž',
        'â€œ',
        'â€™',
        'â€˜',
        'Â ',
    ];

    public function __construct(private PhpMyAdminJsonTableReader $reader) {}

    public function audit(string $mediaPath): LegacyCatalogQualityReport
    {
        $rows = $this->reader->read($mediaPath, 'mediaList');
        $summary = [
            'media_rows' => count($rows),
            'dnb_reference_rows' => 0,
            'encoding_artifact_rows' => 0,
            'encoding_artifact_field_hits' => 0,
            'encoding_artifact_rows_with_dnb' => 0,
            'encoding_artifact_rows_without_dnb' => 0,
            'responsibility_statement_rows' => 0,
            'responsibility_without_structured_contributors_rows' => 0,
            'main_author_rows' => 0,
            'main_author_with_gnd_rows' => 0,
            'main_author_without_gnd_rows' => 0,
            'additional_contributor_rows' => 0,
            'additional_contributor_items' => 0,
            'invalid_additional_contributors_json_rows' => 0,
            'unrecognized_additional_contributor_items' => 0,
            'dnb_refresh_candidate_rows' => 0,
            'manual_review_rows' => 0,
            'quality_issue_rows' => 0,
            'clean_rows' => 0,
        ];
        $issues = [];

        foreach ($rows as $row) {
            $artifactFields = $this->artifactFields($row);
            $dnbRecordId = $this->dnbRecordId($row);
            $responsibility = $this->scalarString($row['authors_statement'] ?? null);
            $mainAuthor = $this->scalarString($row['main_author_name'] ?? null);
            $mainAuthorGnd = $this->scalarString($row['main_author_gnd_id'] ?? null);
            $additional = $this->additionalContributors($row['additional_contributors_json'] ?? null);
            $structuredContributorCount = ($mainAuthor !== null ? 1 : 0) + $additional['count'];
            $rowIssues = [];

            if ($dnbRecordId !== null) {
                $summary['dnb_reference_rows']++;
            }

            if ($artifactFields !== []) {
                $summary['encoding_artifact_rows']++;
                $summary['encoding_artifact_field_hits'] += count($artifactFields);
                $rowIssues[] = 'encoding_artifact';

                if ($dnbRecordId !== null) {
                    $summary['encoding_artifact_rows_with_dnb']++;
                } else {
                    $summary['encoding_artifact_rows_without_dnb']++;
                }
            }

            if ($responsibility !== null) {
                $summary['responsibility_statement_rows']++;

                if ($structuredContributorCount === 0) {
                    $summary['responsibility_without_structured_contributors_rows']++;
                    $rowIssues[] = 'responsibility_without_structured_contributors';
                }
            }

            if ($mainAuthor !== null) {
                $summary['main_author_rows']++;

                if ($mainAuthorGnd !== null) {
                    $summary['main_author_with_gnd_rows']++;
                } else {
                    $summary['main_author_without_gnd_rows']++;
                }
            }

            if ($additional['count'] > 0) {
                $summary['additional_contributor_rows']++;
                $summary['additional_contributor_items'] += $additional['count'];
            }

            if (! $additional['valid']) {
                $summary['invalid_additional_contributors_json_rows']++;
                $rowIssues[] = 'additional_contributors_invalid_json';
            }

            if ($additional['unrecognized'] > 0) {
                $summary['unrecognized_additional_contributor_items'] += $additional['unrecognized'];
                $rowIssues[] = 'additional_contributors_unrecognized_items';
            }

            $rowIssues = array_values(array_unique($rowIssues));

            if ($rowIssues === []) {
                continue;
            }

            $summary['quality_issue_rows']++;

            if ($dnbRecordId !== null) {
                $summary['dnb_refresh_candidate_rows']++;
            } else {
                $summary['manual_review_rows']++;
            }

            $issues[] = [
                'media_id' => $this->scalarString($row['media_id'] ?? null),
                'barcode' => $this->scalarString($row['inventory_number'] ?? null),
                'dnb_record_id' => $dnbRecordId,
                'title' => $this->scalarString($row['main_title'] ?? null),
                'responsibility_statement' => $responsibility,
                'artifact_fields' => $artifactFields,
                'structured_contributor_count' => $structuredContributorCount,
                'additional_contributor_count' => $additional['count'],
                'issues' => $rowIssues,
            ];
        }

        $summary['clean_rows'] = $summary['media_rows'] - $summary['quality_issue_rows'];

        return new LegacyCatalogQualityReport(
            summary: $summary,
            issues: $issues,
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<string>
     */
    private function artifactFields(array $row): array
    {
        $fields = [];

        foreach (self::ARTIFACT_FIELDS as $field) {
            $value = $this->scalarString($row[$field] ?? null);

            if ($value !== null && $this->hasEncodingArtifact($value)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    private function hasEncodingArtifact(string $value): bool
    {
        if (str_contains($value, "\u{FFFD}")) {
            return true;
        }

        if (preg_match('/\p{L}\?\p{L}/u', $value) === 1) {
            return true;
        }

        foreach (self::COMMON_MOJIBAKE as $needle) {
            if (str_contains($value, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $row */
    private function dnbRecordId(array $row): ?string
    {
        $id = $this->scalarString($row['dnb_rcn_id'] ?? null);

        if ($id !== null) {
            return $id;
        }

        $url = $this->scalarString($row['dnb_rcn_permalink'] ?? null);

        if ($url === null || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! in_array($host, ['d-nb.info', 'www.d-nb.info'], true)) {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $path === '' ? null : basename($path);
    }

    /**
     * @return array{valid:bool,count:int,unrecognized:int}
     */
    private function additionalContributors(mixed $value): array
    {
        if ($value === null || $value === '') {
            return ['valid' => true, 'count' => 0, 'unrecognized' => 0];
        }

        if (is_array($value)) {
            $decoded = $value;
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['valid' => false, 'count' => 0, 'unrecognized' => 0];
            }
        } else {
            return ['valid' => false, 'count' => 0, 'unrecognized' => 0];
        }

        if (! is_array($decoded) || ! array_is_list($decoded)) {
            return ['valid' => false, 'count' => 0, 'unrecognized' => 0];
        }

        $count = 0;
        $unrecognized = 0;

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                $unrecognized++;

                continue;
            }

            /** @var array<string, mixed> $item */
            if ($this->firstString($item, self::CONTRIBUTOR_NAME_KEYS) === null) {
                $unrecognized++;

                continue;
            }

            $count++;
        }

        return [
            'valid' => true,
            'count' => $count,
            'unrecognized' => $unrecognized,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private function firstString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->scalarString($data[$key] ?? null);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function scalarString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' || mb_strtolower($value) === 'null' ? null : $value;
    }
}
