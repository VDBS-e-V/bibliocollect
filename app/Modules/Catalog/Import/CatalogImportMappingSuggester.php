<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Import;

use App\Modules\Catalog\DTOs\CatalogImportMapping;
use App\Modules\Catalog\Enums\CatalogImportField;

final class CatalogImportMappingSuggester
{
    /** @param list<string> $headers */
    public function suggest(array $headers): CatalogImportMapping
    {
        $normalizedHeaders = [];

        foreach ($headers as $header) {
            $normalizedHeaders[$this->normalize($header)] = $header;
        }

        $mapping = [];

        foreach (CatalogImportField::cases() as $field) {
            $mapping[$field->value] = null;

            foreach ($this->aliases($field) as $alias) {
                $match = $normalizedHeaders[$this->normalize($alias)] ?? null;

                if ($match !== null) {
                    $mapping[$field->value] = $match;
                    break;
                }
            }
        }

        return new CatalogImportMapping($mapping);
    }

    /** @return list<string> */
    private function aliases(CatalogImportField $field): array
    {
        return match ($field) {
            CatalogImportField::PreferredTitle => ['preferred_title', 'title', 'titel', 'haupttitel'],
            CatalogImportField::Subtitle => ['subtitle', 'untertitel'],
            CatalogImportField::SortTitle => ['sort_title', 'sortiertitel'],
            CatalogImportField::EditionStatement => ['edition_statement', 'edition', 'auflage', 'ausgabe'],
            CatalogImportField::Isbn => ['isbn', 'isbn13', 'isbn_13'],
            CatalogImportField::PublisherName => ['publisher_name', 'publisher', 'verlag'],
            CatalogImportField::PublicationYear => ['publication_year', 'year', 'erscheinungsjahr', 'jahr'],
            CatalogImportField::MediaType => ['media_type', 'medientyp', 'medium'],
            CatalogImportField::LanguageCode => ['language_code', 'language', 'sprache', 'sprachcode'],
            CatalogImportField::MinimumAge => ['minimum_age', 'mindestalter', 'alter'],
            CatalogImportField::AgeRatingLabel => ['age_rating_label', 'altersfreigabe', 'altersfreigabe_label'],
            CatalogImportField::ContributorName => ['contributor_name', 'contributor', 'author', 'autor', 'verantwortliche'],
            CatalogImportField::ContributorSortName => ['contributor_sort_name', 'sort_name', 'contributor_sort'],
            CatalogImportField::ContributorRole => ['contributor_role', 'role', 'role_key', 'rolle'],
            CatalogImportField::Barcode => ['barcode', 'bar_code', 'strichcode', 'exemplarbarcode'],
            CatalogImportField::ShelfLocation => ['shelf_location', 'shelf', 'regalstandort', 'standort'],
            CatalogImportField::CopyStatus => ['copy_status', 'status', 'exemplarstatus'],
        };
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return preg_replace('/[^a-z0-9äöüß]+/u', '', $value) ?? $value;
    }
}
