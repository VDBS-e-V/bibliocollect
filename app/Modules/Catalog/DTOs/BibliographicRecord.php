<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

/**
 * Quellenunabhängiger bibliografischer Treffer aus einer externen Abfrage (z. B. DNB).
 * Er wird nie automatisch gespeichert, sondern nur zur Vorbefüllung der Erfassung genutzt.
 */
final readonly class BibliographicRecord
{
    /** @param list<array{name: string, role: string, gnd_id: string|null}> $contributors */
    public function __construct(
        public string $source,
        public ?string $sourceRecordId,
        public ?string $sourcePermalink,
        public string $title,
        public ?string $subtitle,
        public ?string $responsibilityStatement,
        public array $contributors,
        public ?string $isbn,
        public ?string $publisherName,
        public ?string $publicationPlace,
        public ?int $publicationYear,
        public ?string $editionStatement,
        public ?string $physicalExtent,
        public ?string $languageCode,
        public ?string $originalLanguageCode,
        public ?string $mediaType,
        public ?string $seriesStatement,
        public ?string $summary,
        public ?string $subjectKeywords,
        public ?string $targetAudience,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'source_record_id' => $this->sourceRecordId,
            'source_permalink' => $this->sourcePermalink,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'responsibility_statement' => $this->responsibilityStatement,
            'contributors' => $this->contributors,
            'isbn' => $this->isbn,
            'publisher_name' => $this->publisherName,
            'publication_place' => $this->publicationPlace,
            'publication_year' => $this->publicationYear,
            'edition_statement' => $this->editionStatement,
            'physical_extent' => $this->physicalExtent,
            'language_code' => $this->languageCode,
            'original_language_code' => $this->originalLanguageCode,
            'media_type' => $this->mediaType,
            'series_statement' => $this->seriesStatement,
            'summary' => $this->summary,
            'subject_keywords' => $this->subjectKeywords,
            'target_audience' => $this->targetAudience,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $contributors = [];

        if (isset($data['contributors']) && is_array($data['contributors'])) {
            foreach ($data['contributors'] as $contributor) {
                if (! is_array($contributor) || ! is_string($contributor['name'] ?? null)) {
                    continue;
                }

                $contributors[] = [
                    'name' => $contributor['name'],
                    'role' => is_string($contributor['role'] ?? null) ? $contributor['role'] : 'contributor',
                    'gnd_id' => is_string($contributor['gnd_id'] ?? null) ? $contributor['gnd_id'] : null,
                ];
            }
        }

        return new self(
            source: self::string($data['source'] ?? null) ?? 'unknown',
            sourceRecordId: self::string($data['source_record_id'] ?? null),
            sourcePermalink: self::string($data['source_permalink'] ?? null),
            title: self::string($data['title'] ?? null) ?? '',
            subtitle: self::string($data['subtitle'] ?? null),
            responsibilityStatement: self::string($data['responsibility_statement'] ?? null),
            contributors: $contributors,
            isbn: self::string($data['isbn'] ?? null),
            publisherName: self::string($data['publisher_name'] ?? null),
            publicationPlace: self::string($data['publication_place'] ?? null),
            publicationYear: is_numeric($data['publication_year'] ?? null) ? (int) $data['publication_year'] : null,
            editionStatement: self::string($data['edition_statement'] ?? null),
            physicalExtent: self::string($data['physical_extent'] ?? null),
            languageCode: self::string($data['language_code'] ?? null),
            originalLanguageCode: self::string($data['original_language_code'] ?? null),
            mediaType: self::string($data['media_type'] ?? null),
            seriesStatement: self::string($data['series_statement'] ?? null),
            summary: self::string($data['summary'] ?? null),
            subjectKeywords: self::string($data['subject_keywords'] ?? null),
            targetAudience: self::string($data['target_audience'] ?? null),
        );
    }

    private static function string(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
