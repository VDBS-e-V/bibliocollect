<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

/**
 * Bestätigte Titel- und Ausgabedaten eines Erfassungsvorgangs.
 * Die Herkunft externer Daten (Quelle, Datensatz-ID) wird getrennt als {@see CatalogIntakeProvenance} geführt.
 */
final readonly class CatalogIntakeDetails
{
    /** @param list<array{name: string, role: string, gnd_id: string|null}> $contributors */
    public function __construct(
        public string $preferredTitle,
        public ?string $subtitle,
        public ?string $responsibilityStatement,
        public array $contributors,
        public ?string $isbn,
        public ?string $publisherName,
        public ?string $publicationPlace,
        public ?int $publicationYear,
        public ?string $editionStatement,
        public ?string $physicalExtent,
        public ?string $mediaType,
        public ?string $languageCode,
        public ?string $originalLanguageCode,
        public ?string $seriesStatement,
        public ?string $targetAudience,
        public ?int $minimumAge,
        public ?string $subjectKeywords,
        public ?string $summary,
        public ?string $localClassification,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'preferred_title' => $this->preferredTitle,
            'subtitle' => $this->subtitle,
            'responsibility_statement' => $this->responsibilityStatement,
            'contributors' => $this->contributors,
            'isbn' => $this->isbn,
            'publisher_name' => $this->publisherName,
            'publication_place' => $this->publicationPlace,
            'publication_year' => $this->publicationYear,
            'edition_statement' => $this->editionStatement,
            'physical_extent' => $this->physicalExtent,
            'media_type' => $this->mediaType,
            'language_code' => $this->languageCode,
            'original_language_code' => $this->originalLanguageCode,
            'series_statement' => $this->seriesStatement,
            'target_audience' => $this->targetAudience,
            'minimum_age' => $this->minimumAge,
            'subject_keywords' => $this->subjectKeywords,
            'summary' => $this->summary,
            'local_classification' => $this->localClassification,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $contributors = [];

        if (isset($data['contributors']) && is_array($data['contributors'])) {
            foreach ($data['contributors'] as $contributor) {
                if (! is_array($contributor) || ! is_string($contributor['name'] ?? null) || trim($contributor['name']) === '') {
                    continue;
                }

                $contributors[] = [
                    'name' => trim($contributor['name']),
                    'role' => is_string($contributor['role'] ?? null) && $contributor['role'] !== '' ? $contributor['role'] : 'contributor',
                    'gnd_id' => is_string($contributor['gnd_id'] ?? null) && $contributor['gnd_id'] !== '' ? $contributor['gnd_id'] : null,
                ];
            }
        }

        return new self(
            preferredTitle: self::string($data['preferred_title'] ?? null) ?? '',
            subtitle: self::string($data['subtitle'] ?? null),
            responsibilityStatement: self::string($data['responsibility_statement'] ?? null),
            contributors: $contributors,
            isbn: self::string($data['isbn'] ?? null),
            publisherName: self::string($data['publisher_name'] ?? null),
            publicationPlace: self::string($data['publication_place'] ?? null),
            publicationYear: is_numeric($data['publication_year'] ?? null) ? (int) $data['publication_year'] : null,
            editionStatement: self::string($data['edition_statement'] ?? null),
            physicalExtent: self::string($data['physical_extent'] ?? null),
            mediaType: self::string($data['media_type'] ?? null),
            languageCode: self::string($data['language_code'] ?? null),
            originalLanguageCode: self::string($data['original_language_code'] ?? null),
            seriesStatement: self::string($data['series_statement'] ?? null),
            targetAudience: self::string($data['target_audience'] ?? null),
            minimumAge: is_numeric($data['minimum_age'] ?? null) ? (int) $data['minimum_age'] : null,
            subjectKeywords: self::string($data['subject_keywords'] ?? null),
            summary: self::string($data['summary'] ?? null),
            localClassification: self::string($data['local_classification'] ?? null),
        );
    }

    /** Vorbefüllung aus einem externen Treffer. Das Mindestalter wird bewusst nie automatisch gesetzt. */
    public static function fromRecord(BibliographicRecord $record): self
    {
        return self::fromArray([
            'preferred_title' => $record->title,
            'subtitle' => $record->subtitle,
            'responsibility_statement' => $record->responsibilityStatement,
            'contributors' => $record->contributors,
            'isbn' => $record->isbn,
            'publisher_name' => $record->publisherName,
            'publication_place' => $record->publicationPlace,
            'publication_year' => $record->publicationYear,
            'edition_statement' => $record->editionStatement,
            'physical_extent' => $record->physicalExtent,
            'media_type' => $record->mediaType,
            'language_code' => $record->languageCode,
            'original_language_code' => $record->originalLanguageCode,
            'series_statement' => $record->seriesStatement,
            'target_audience' => $record->targetAudience,
            'subject_keywords' => $record->subjectKeywords,
            'summary' => $record->summary,
        ]);
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
