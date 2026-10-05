<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Legacy;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use DateTimeImmutable;

final readonly class LegacyCatalogNormalizer
{
    public const SOURCE = 'vdbs-legacy';

    public function __construct(private CatalogIsbnNormalizer $isbnNormalizer) {}

    /**
     * @param  array<string, mixed>  $row
     * @return array{
     *   title:array{preferred_title:string,subtitle:?string,sort_title:?string},
     *   edition:array<string,mixed>,
     *   copy:array<string,mixed>,
     *   contributors:list<array{name:string,sort_name:?string,gnd_id:?string,role_key:string,position:int}>,
     *   warnings:list<string>,
     *   errors:list<string>,
     *   edition_key:string
     * }
     */
    public function media(array $row): array
    {
        $warnings = [];
        $errors = [];
        $title = $this->string($row['main_title'] ?? null);
        $barcode = $this->string($row['inventory_number'] ?? null);

        if ($title === null) {
            $errors[] = 'main_title fehlt.';
            $title = '';
        }

        if ($barcode === null) {
            $errors[] = 'inventory_number fehlt.';
            $barcode = '';
        }

        if ($this->hasPossibleEncodingArtifact($row)) {
            $warnings[] = "Barcode [{$barcode}] enthält mögliche Legacy-Zeichensatzartefakte.";
        }

        $primaryIdentifier = $this->primaryIsbn($row['isbn_eans'] ?? null, $warnings);
        $alternateIdentifiers = $this->identifiers($row['isbn_eans_plus'] ?? null);
        $rawPrimary = $this->string($row['isbn_eans'] ?? null);

        if ($rawPrimary !== null && ($primaryIdentifier === null || $primaryIdentifier !== $rawPrimary)) {
            $alternateIdentifiers[] = $rawPrimary;
        }

        $alternateIdentifiers = array_values(array_unique(array_filter(
            $alternateIdentifiers,
            static fn (string $value): bool => $value !== $primaryIdentifier,
        )));

        $sourceRecordId = $this->string($row['dnb_rcn_id'] ?? null)
            ?? $this->recordIdFromPermalink($row['dnb_rcn_permalink'] ?? null);
        $sourcePermalink = $this->safeUrl($row['dnb_rcn_permalink'] ?? null);
        $year = $this->year($row['publication_year'] ?? null, $warnings, $barcode);
        $ageRecommendation = $this->jsonValue($row['age_recommendation'] ?? null, $warnings, 'age_recommendation', $barcode);
        $ageRating = $this->string($row['fsk_rating'] ?? null);

        if ($ageRating !== null && mb_strtolower($ageRating) === 'keine angabe') {
            $ageRating = null;
        }

        $minimumAge = $this->minimumAge($ageRecommendation, $ageRating);
        $storedAgeRecommendation = $this->structuredJsonValue($ageRecommendation);
        $legacyMediaId = $this->string($row['media_id'] ?? null);

        if ($legacyMediaId === null) {
            $errors[] = 'media_id fehlt.';
        }

        $signature = $this->string($row['location_signature'] ?? null);
        $condition = $this->string($row['used_condition'] ?? null);
        $legacyCondition = $this->string($row['used_condition_old'] ?? null);
        $depreciatedAt = $this->date($row['depreciation_at'] ?? null, $warnings, 'depreciation_at', $barcode);
        $depreciationReason = $this->string($row['depreciation_reason'] ?? null);

        $edition = [
            'responsibility_statement' => $this->string($row['authors_statement'] ?? null),
            'series_statement' => $this->string($row['series_statement'] ?? null),
            'edition_statement' => $this->string($row['edition_statement'] ?? null),
            'edition_number' => $this->string($row['edition_number'] ?? null),
            'isbn' => $primaryIdentifier,
            'alternate_identifiers' => $alternateIdentifiers === [] ? null : $alternateIdentifiers,
            'issn' => $this->string($row['issn'] ?? null),
            'doi_handle' => $this->string($row['doi_handle'] ?? null),
            'publication_place' => $this->string($row['publication_place'] ?? null),
            'publisher_name' => $this->string($row['publisher'] ?? null),
            'publication_year' => $year,
            'local_classification' => $this->string($row['local_classification'] ?? null),
            'media_type' => $this->mediaType($row['media_type'] ?? null),
            'language_code' => $this->language($row['language'] ?? null),
            'original_language_code' => $this->language($row['original_language'] ?? null),
            'page_count' => $this->positiveInt($row['page_number'] ?? null),
            'physical_extent' => $this->string($row['physical_extent'] ?? null),
            'file_size_bytes' => $this->positiveInt($row['file_size_bytes'] ?? null),
            'format_type' => $this->string($row['format_type'] ?? null),
            'summary' => $this->plainText($row['summary'] ?? null),
            'subject_keywords' => $this->string($row['subject_keywords'] ?? null),
            'subject_keywords_system' => $this->string($row['subject_keywords_system'] ?? null),
            'target_audience' => $this->string($row['target_audience'] ?? null),
            'age_recommendation' => $storedAgeRecommendation,
            'minimum_age' => $minimumAge,
            'age_rating_label' => $ageRating,
            'metadata_source' => $sourceRecordId !== null || $sourcePermalink !== null ? 'dnb' : self::SOURCE,
            'source_record_id' => $sourceRecordId,
            'source_permalink' => $sourcePermalink,
            'legacy_source' => self::SOURCE,
        ];

        $editionKeyPayload = [
            $title,
            $this->string($row['subtitle'] ?? null),
            $primaryIdentifier,
            $edition['edition_statement'],
            $edition['edition_number'],
            $edition['publisher_name'],
            $year,
            $sourceRecordId,
            $edition['media_type'],
            $edition['language_code'],
        ];
        $editionKey = hash('sha256', json_encode($editionKeyPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $edition['legacy_record_key'] = $editionKey;

        return [
            'title' => [
                'preferred_title' => $title,
                'subtitle' => $this->string($row['subtitle'] ?? null),
                'sort_title' => null,
            ],
            'edition' => $edition,
            'copy' => [
                'barcode' => $barcode,
                'shelf_location' => $signature,
                'signature' => $signature,
                'status' => $this->copyStatus($condition, $legacyCondition, $depreciatedAt, $depreciationReason),
                'legacy_source' => self::SOURCE,
                'legacy_media_id' => $legacyMediaId,
                'legacy_in_transition' => $this->boolean($row['in_transition'] ?? null),
                'legacy_school_id' => $this->string($row['school_id'] ?? null),
                'access_status' => $this->string($row['access_status'] ?? null),
                'purchase_date' => $this->date($row['purchase_date'] ?? null, $warnings, 'purchase_date', $barcode),
                'purchase_price' => $this->decimal($row['purchase_price'] ?? null),
                'legacy_is_available' => $this->boolean($row['is_available'] ?? null),
                'cataloged_on' => $this->date($row['added_on'] ?? null, $warnings, 'added_on', $barcode),
                'legacy_cover_path' => $this->string($row['cover_image_path'] ?? null),
                'legacy_loan_count' => $this->positiveInt($row['loan_counter'] ?? null),
                'legacy_last_loan_date' => $this->date($row['last_loan_date'] ?? null, $warnings, 'last_loan_date', $barcode),
                'internal_notes' => $this->string($row['internal_notes'] ?? null),
                'condition_code' => $condition,
                'legacy_condition' => $legacyCondition,
                'depreciation_reason' => $depreciationReason,
                'depreciated_at' => $depreciatedAt,
                'further_use' => $this->string($row['further_use'] ?? null),
                'legacy_metadata' => $row,
            ],
            'contributors' => $this->contributors($row, $warnings, $barcode),
            'warnings' => array_values(array_unique($warnings)),
            'errors' => array_values(array_unique($errors)),
            'edition_key' => $editionKey,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $warnings
     * @return list<array{name:string,sort_name:?string,gnd_id:?string,role_key:string,position:int}>
     */
    private function contributors(array $row, array &$warnings, string $barcode): array
    {
        $contributors = [];
        $mainName = $this->string($row['main_author_name'] ?? null);
        $mainGnd = $this->normalizeGnd($row['main_author_gnd_id'] ?? null);

        if ($mainName !== null) {
            $contributors[] = [
                'name' => $mainName,
                'sort_name' => null,
                'gnd_id' => $mainGnd,
                'role_key' => 'author',
                'position' => 1,
            ];
        }

        $additional = $this->jsonValue(
            $row['additional_contributors_json'] ?? null,
            $warnings,
            'additional_contributors_json',
            $barcode,
        );

        if (! is_array($additional)) {
            return $contributors;
        }

        $position = count($contributors) + 1;

        foreach ($additional as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = $this->firstString($item, ['name', 'display_name', 'contributor', 'author', 'person', 'corporate_name']);

            if ($name === null) {
                continue;
            }

            $role = $this->firstString($item, ['role_key', 'role', 'relator', 'function']) ?? 'contributor';
            $roleKey = $this->roleKey($role);

            $contributors[] = [
                'name' => $name,
                'sort_name' => $this->firstString($item, ['sort_name', 'sortName']),
                'gnd_id' => $this->normalizeGnd($this->firstString($item, ['gnd_id', 'gnd', 'authority_id', 'id'])),
                'role_key' => $roleKey,
                'position' => $position++,
            ];
        }

        return $contributors;
    }

    private function copyStatus(?string $condition, ?string $legacyCondition, ?string $depreciatedAt, ?string $depreciationReason): CopyStatus
    {
        if ($depreciatedAt !== null || $depreciationReason !== null) {
            return CopyStatus::Withdrawn;
        }

        $value = mb_strtolower(trim($condition ?? $legacyCondition ?? ''));

        if (str_contains($value, 'zerstört') || str_contains($value, 'zerstoert')) {
            return CopyStatus::Withdrawn;
        }

        if (str_contains($value, 'schäd') || str_contains($value, 'schaed') || str_contains($value, 'schäden')) {
            return CopyStatus::Damaged;
        }

        return CopyStatus::Active;
    }

    /** @param list<string> $warnings */
    private function primaryIsbn(mixed $value, array &$warnings): ?string
    {
        $raw = $this->string($value);

        if ($raw === null) {
            return null;
        }

        foreach ($this->identifiers($raw) as $candidate) {
            $normalized = $this->isbnNormalizer->normalize($candidate);

            if ($this->isbnNormalizer->isStandardFormat($normalized)) {
                return $normalized;
            }
        }

        $normalized = $this->isbnNormalizer->normalize($raw);

        if ($this->isbnNormalizer->isStandardFormat($normalized)) {
            return $normalized;
        }

        $warnings[] = "ISBN/EAN [{$raw}] ist kein plausibles ISBN-10/ISBN-13 und wird nur als alternativer Identifikator erhalten.";

        return null;
    }

    /** @return list<string> */
    private function identifiers(mixed $value): array
    {
        $raw = $this->string($value);

        if ($raw === null) {
            return [];
        }

        $parts = preg_split('/[;,|\r\n]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [$raw];
        $result = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if ($part !== '') {
                $result[] = $part;
            }
        }

        return array_values(array_unique($result));
    }

    /** @param list<string> $warnings */
    private function year(mixed $value, array &$warnings, string $barcode): ?int
    {
        $raw = $this->string($value);

        if ($raw === null || $raw === '0' || $raw === '0000') {
            return null;
        }

        if (preg_match('/^\d{4}$/', $raw) !== 1) {
            $warnings[] = "Barcode [{$barcode}]: Erscheinungsjahr [{$raw}] wurde nicht übernommen.";

            return null;
        }

        $year = (int) $raw;

        if ($year < 1000 || $year > 2100) {
            $warnings[] = "Barcode [{$barcode}]: Erscheinungsjahr [{$raw}] liegt außerhalb 1000–2100.";

            return null;
        }

        return $year;
    }

    /** @param list<string> $warnings */
    private function date(mixed $value, array &$warnings, string $field, string $barcode): ?string
    {
        $raw = $this->string($value);

        if ($raw === null || $raw === '0000-00-00') {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        $errors = DateTimeImmutable::getLastErrors();

        if ($parsed === false || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            $warnings[] = "Barcode [{$barcode}]: Datum [{$field}={$raw}] wurde nicht übernommen.";

            return null;
        }

        return $parsed->format('Y-m-d');
    }

    /** @param list<string> $warnings */
    private function jsonValue(mixed $value, array &$warnings, string $field, string $barcode): mixed
    {
        if (is_array($value)) {
            return $value;
        }

        $raw = $this->string($value);

        if ($raw === null) {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $warnings[] = "Barcode [{$barcode}]: JSON-Feld [{$field}] ist ungültig und bleibt nur in legacy_metadata erhalten.";

            return null;
        }

        return $decoded;
    }

    /** @return array<string|int, mixed>|null */
    private function structuredJsonValue(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return ['raw' => (string) $value];
        }

        return null;
    }

    private function minimumAge(mixed $ageRecommendation, ?string $ageRating): ?int
    {
        $candidates = [];

        if ($ageRecommendation !== null) {
            $encoded = is_scalar($ageRecommendation)
                ? (string) $ageRecommendation
                : (json_encode($ageRecommendation, JSON_UNESCAPED_UNICODE) ?: '');
            $candidates[] = $encoded;
        }

        if ($ageRating !== null) {
            $candidates[] = $ageRating;
        }

        foreach ($candidates as $candidate) {
            if (preg_match('/(?:ab|fsk)?\s*(\d{1,2})/iu', $candidate, $matches) === 1) {
                $age = (int) $matches[1];

                if ($age >= 0 && $age <= 18) {
                    return $age;
                }
            }
        }

        return null;
    }

    private function mediaType(mixed $value): ?string
    {
        $raw = $this->string($value);

        if ($raw === null) {
            return null;
        }

        return match (mb_strtolower($raw)) {
            'buch' => 'book',
            'audiobook', 'hörbuch', 'hoerbuch' => 'audiobook',
            'ebook', 'e-book' => 'ebook',
            'journal' => 'journal',
            'manuscript' => 'manuscript',
            'map' => 'map',
            'sonstiges' => 'other',
            'dvd' => 'dvd',
            'cd' => 'cd',
            default => mb_strtolower($raw),
        };
    }

    private function language(mixed $value): ?string
    {
        $raw = $this->string($value);

        if ($raw === null) {
            return null;
        }

        return match (mb_strtolower($raw)) {
            'de', 'deu', 'ger', 'deutsch' => 'de',
            'en', 'eng', 'englisch', 'english' => 'en',
            'fr', 'fra', 'fre', 'französisch', 'franzoesisch', 'french' => 'fr',
            'es', 'spa', 'spanisch', 'spanish' => 'es',
            'it', 'ita', 'italienisch', 'italian' => 'it',
            'nl', 'nld', 'dut', 'niederländisch', 'niederlaendisch', 'dutch' => 'nl',
            'pl', 'pol', 'polnisch', 'polish' => 'pl',
            default => mb_strtolower($raw),
        };
    }

    private function roleKey(string $value): string
    {
        $key = mb_strtolower(trim($value));
        $key = match ($key) {
            'autor', 'autorin', 'author', 'verfasser', 'verfasserin' => 'author',
            'illustrator', 'illustratorin', 'illustration' => 'illustrator',
            'übersetzer', 'übersetzerin', 'uebersetzer', 'translator' => 'translator',
            'herausgeber', 'herausgeberin', 'editor' => 'editor',
            default => preg_replace('/[^a-z0-9._-]+/', '_', $key) ?? 'contributor',
        };

        $key = trim($key, '_-');

        return preg_match('/^[a-z][a-z0-9._-]{0,79}$/', $key) === 1 ? $key : 'contributor';
    }

    private function normalizeGnd(mixed $value): ?string
    {
        $raw = $this->string($value);

        if ($raw === null) {
            return null;
        }

        $raw = preg_replace('/^\(DE-588\)/i', '', $raw) ?? $raw;
        $raw = trim($raw);

        return $raw === '' ? null : $raw;
    }

    private function safeUrl(mixed $value): ?string
    {
        $raw = $this->string($value);

        if ($raw === null) {
            return null;
        }

        return filter_var($raw, FILTER_VALIDATE_URL) !== false
            && in_array(parse_url($raw, PHP_URL_SCHEME), ['http', 'https'], true)
                ? $raw
                : null;
    }

    private function recordIdFromPermalink(mixed $value): ?string
    {
        $url = $this->safeUrl($value);

        if ($url === null) {
            return null;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        return $path === '' ? null : basename($path);
    }

    private function plainText(mixed $value): ?string
    {
        $raw = $this->string($value);

        if ($raw === null) {
            return null;
        }

        $plain = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $plain === '' ? null : preg_replace('/\s+/u', ' ', $plain);
    }

    private function positiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $number = (int) $value;

        return $number >= 0 ? $number : null;
    }

    private function decimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = str_replace(',', '.', trim((string) $value));

        if (! is_numeric($raw)) {
            return null;
        }

        return number_format((float) $raw, 2, '.', '');
    }

    private function boolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'ja' => true,
            '0', 'false', 'no', 'nein' => false,
            default => null,
        };
    }

    private function string(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' || mb_strtolower($trimmed) === 'null' ? null : $trimmed;
    }

    /** @param array<string, mixed> $row */
    private function hasPossibleEncodingArtifact(array $row): bool
    {
        foreach (['main_title', 'subtitle', 'authors_statement', 'main_author_name', 'publisher', 'summary'] as $field) {
            $value = $row[$field] ?? null;

            if (is_string($value) && preg_match('/\p{L}\?\p{L}/u', $value) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private function firstString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->string($data[$key] ?? null);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }
}
