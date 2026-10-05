<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Import;

use App\Modules\Catalog\DTOs\CatalogImportMapping;
use App\Modules\Catalog\DTOs\CatalogImportNormalizationResult;
use App\Modules\Catalog\Enums\CatalogImportField;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;

final class CatalogImportNormalizer
{
    public function __construct(private readonly CatalogIsbnNormalizer $isbnNormalizer) {}

    /**
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $sourceErrors
     */
    public function normalize(array $raw, CatalogImportMapping $mapping, array $sourceErrors = []): CatalogImportNormalizationResult
    {
        $warnings = [];
        $errors = $sourceErrors;

        /** @var array<string, string|int|null> $data */
        $data = [
            'preferred_title' => $this->stringValue($raw, $mapping, CatalogImportField::PreferredTitle),
            'subtitle' => $this->stringValue($raw, $mapping, CatalogImportField::Subtitle),
            'sort_title' => $this->stringValue($raw, $mapping, CatalogImportField::SortTitle),
            'edition_statement' => $this->stringValue($raw, $mapping, CatalogImportField::EditionStatement),
            'isbn' => $this->stringValue($raw, $mapping, CatalogImportField::Isbn),
            'publisher_name' => $this->stringValue($raw, $mapping, CatalogImportField::PublisherName),
            'publication_year' => $this->stringValue($raw, $mapping, CatalogImportField::PublicationYear),
            'media_type' => $this->stringValue($raw, $mapping, CatalogImportField::MediaType),
            'language_code' => $this->stringValue($raw, $mapping, CatalogImportField::LanguageCode),
            'minimum_age' => $this->stringValue($raw, $mapping, CatalogImportField::MinimumAge),
            'age_rating_label' => $this->stringValue($raw, $mapping, CatalogImportField::AgeRatingLabel),
            'contributor_name' => $this->stringValue($raw, $mapping, CatalogImportField::ContributorName),
            'contributor_sort_name' => $this->stringValue($raw, $mapping, CatalogImportField::ContributorSortName),
            'contributor_role' => $this->stringValue($raw, $mapping, CatalogImportField::ContributorRole),
            'barcode' => $this->stringValue($raw, $mapping, CatalogImportField::Barcode),
            'shelf_location' => $this->stringValue($raw, $mapping, CatalogImportField::ShelfLocation),
            'copy_status' => $this->stringValue($raw, $mapping, CatalogImportField::CopyStatus),
        ];

        if ($data['preferred_title'] === null) {
            $errors[] = 'Der Haupttitel fehlt.';
        }

        if ($data['barcode'] === null) {
            $errors[] = 'Der Barcode fehlt.';
        }

        $this->checkLength($data['preferred_title'], 500, 'Haupttitel', $errors);
        $this->checkLength($data['subtitle'], 500, 'Untertitel', $errors);
        $this->checkLength($data['sort_title'], 500, 'Sortiertitel', $errors);
        $this->checkLength($data['edition_statement'], 255, 'Edition/Auflage', $errors);
        $this->checkLength($data['publisher_name'], 255, 'Verlag', $errors);
        $this->checkLength($data['media_type'], 80, 'Medientyp', $errors);
        $this->checkLength($data['language_code'], 16, 'Sprache', $errors);
        $this->checkLength($data['age_rating_label'], 80, 'Altersfreigabe-Label', $errors);
        $this->checkLength($data['contributor_name'], 255, 'Contributor Name', $errors);
        $this->checkLength($data['contributor_sort_name'], 255, 'Contributor Sort Name', $errors);
        $this->checkLength($data['barcode'], 80, 'Barcode', $errors);
        $this->checkLength($data['shelf_location'], 120, 'Regalstandort', $errors);

        if (is_string($data['isbn'])) {
            $data['isbn'] = $this->normalizeIsbn($data['isbn'], $warnings, $errors);
        }

        $data['publication_year'] = $this->normalizeInteger(
            $data['publication_year'],
            1000,
            2100,
            'Erscheinungsjahr',
            $errors,
        );
        $data['minimum_age'] = $this->normalizeInteger(
            $data['minimum_age'],
            0,
            18,
            'Mindestalter',
            $errors,
        );
        $data['media_type'] = $this->normalizeMediaType($data['media_type']);
        $data['language_code'] = $this->normalizeLanguageCode($data['language_code']);
        $data['copy_status'] = $this->normalizeCopyStatus($data['copy_status'], $errors);

        if ($data['contributor_name'] === null) {
            if ($data['contributor_sort_name'] !== null || $data['contributor_role'] !== null) {
                $warnings[] = 'Contributor-Sortierung oder -Rolle wurde ohne Contributor Name ignoriert.';
            }

            $data['contributor_sort_name'] = null;
            $data['contributor_role'] = null;
        } else {
            $data['contributor_role'] = $this->normalizeContributorRole($data['contributor_role'], $warnings, $errors);
        }

        return new CatalogImportNormalizationResult($data, array_values(array_unique($warnings)), array_values(array_unique($errors)));
    }

    /** @param array<string, mixed> $raw */
    private function stringValue(array $raw, CatalogImportMapping $mapping, CatalogImportField $field): ?string
    {
        $column = $mapping->column($field);

        if ($column === null) {
            return null;
        }

        $value = $raw[$column] ?? null;

        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    private function normalizeIsbn(string $isbn, array &$warnings, array &$errors): string
    {
        $normalized = $this->isbnNormalizer->normalize($isbn);

        if ($this->isbnNormalizer->isStandardFormat($normalized)) {
            return $normalized;
        }

        if (mb_strlen($isbn) > 32) {
            $errors[] = 'Die ISBN ist länger als 32 Zeichen.';

            return $isbn;
        }

        $warnings[] = 'Die ISBN hatte kein übliches ISBN-10/ISBN-13-Format und wurde nur getrimmt.';

        return $normalized;
    }

    private function normalizeMediaType(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $key = mb_strtolower(trim($value));

        return match ($key) {
            'book', 'books', 'buch', 'bücher', 'buecher' => 'book',
            'audiobook', 'audio book', 'hörbuch', 'hoerbuch' => 'audiobook',
            'ebook', 'e-book', 'e_book' => 'ebook',
            'magazine', 'zeitschrift' => 'magazine',
            'dvd', 'video' => 'dvd',
            'game', 'spiel', 'brettspiel' => 'game',
            default => trim($value),
        };
    }

    private function normalizeLanguageCode(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $trimmed = trim($value);
        $key = mb_strtolower($trimmed);

        $known = match ($key) {
            'de', 'deu', 'ger', 'deutsch' => 'de',
            'en', 'eng', 'english', 'englisch' => 'en',
            'fr', 'fra', 'fre', 'french', 'französisch', 'franzoesisch' => 'fr',
            'es', 'spa', 'spanish', 'spanisch', 'español' => 'es',
            default => null,
        };

        if ($known !== null) {
            return $known;
        }

        if (preg_match('/^[A-Za-z]{2,3}(?:[-_][A-Za-z0-9]{2,8})*$/', $trimmed) === 1) {
            return mb_strtolower(str_replace('_', '-', $trimmed));
        }

        return $trimmed;
    }

    /** @param list<string> $errors */
    private function normalizeCopyStatus(mixed $value, array &$errors): string
    {
        if (! is_string($value) || trim($value) === '') {
            return CopyStatus::Active->value;
        }

        $key = mb_strtolower(trim($value));

        $status = match ($key) {
            'active', 'aktiv' => CopyStatus::Active,
            'damaged', 'beschädigt', 'beschaedigt' => CopyStatus::Damaged,
            'lost', 'verloren' => CopyStatus::Lost,
            'withdrawn', 'ausgesondert', 'zurückgezogen', 'zurueckgezogen' => CopyStatus::Withdrawn,
            default => null,
        };

        if ($status === null) {
            $errors[] = "Unbekannter Copy-Status [{$value}].";

            return $key;
        }

        return $status->value;
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    private function normalizeContributorRole(mixed $value, array &$warnings, array &$errors): string
    {
        if (! is_string($value) || trim($value) === '') {
            $warnings[] = 'Für den Contributor fehlte eine Rolle; es wird role_key=contributor verwendet.';

            return 'contributor';
        }

        $key = mb_strtolower(trim($value));
        $key = match ($key) {
            'autor', 'autorin', 'author' => 'author',
            'illustrator', 'illustratorin' => 'illustrator',
            'übersetzer', 'übersetzerin', 'uebersetzer', 'translator' => 'translator',
            default => preg_replace('/\s+/u', '_', $key) ?? $key,
        };

        if (preg_match('/^[a-z][a-z0-9._-]*$/', $key) !== 1 || strlen($key) > 80) {
            $errors[] = "Contributor-Rolle [{$value}] kann nicht als role_key verwendet werden.";
        }

        return $key;
    }

    /** @param list<string> $errors */
    private function normalizeInteger(mixed $value, int $min, int $max, string $label, array &$errors): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = is_int($value) ? (string) $value : trim((string) $value);

        if (preg_match('/^-?\d+$/', $raw) !== 1) {
            $errors[] = "{$label} muss eine ganze Zahl sein.";

            return null;
        }

        $number = (int) $raw;

        if ($number < $min || $number > $max) {
            $errors[] = "{$label} muss zwischen {$min} und {$max} liegen.";

            return null;
        }

        return $number;
    }

    /** @param list<string> $errors */
    private function checkLength(mixed $value, int $max, string $label, array &$errors): void
    {
        if (is_string($value) && mb_strlen($value) > $max) {
            $errors[] = "{$label} ist länger als {$max} Zeichen.";
        }
    }
}
