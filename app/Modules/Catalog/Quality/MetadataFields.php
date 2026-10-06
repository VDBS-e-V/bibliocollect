<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Quality;

use App\Modules\Catalog\Models\Edition;

/**
 * Die Felder, die die Metadatenprüfung bewerten und vorschlagen darf. Alles andere (lokale Klassifikation,
 * Mindestalter, Exemplare, Signaturen, Notizen, Cover, Legacy-Felder) wird nie angefasst.
 */
final class MetadataFields
{
    public const TITLE = 'title';

    public const EDITION = 'edition';

    /** @return array<string, array{target: string, column: string, label: string, text: bool}> */
    public static function all(): array
    {
        return [
            'title.preferred_title' => ['target' => self::TITLE, 'column' => 'preferred_title', 'label' => 'Haupttitel', 'text' => true],
            'title.subtitle' => ['target' => self::TITLE, 'column' => 'subtitle', 'label' => 'Untertitel', 'text' => true],
            'edition.responsibility_statement' => ['target' => self::EDITION, 'column' => 'responsibility_statement', 'label' => 'Verantwortlichkeitsangabe', 'text' => true],
            'edition.publisher_name' => ['target' => self::EDITION, 'column' => 'publisher_name', 'label' => 'Verlag', 'text' => true],
            'edition.publication_place' => ['target' => self::EDITION, 'column' => 'publication_place', 'label' => 'Verlagsort', 'text' => true],
            'edition.publication_year' => ['target' => self::EDITION, 'column' => 'publication_year', 'label' => 'Erscheinungsjahr', 'text' => false],
            'edition.edition_statement' => ['target' => self::EDITION, 'column' => 'edition_statement', 'label' => 'Auflage / Ausgabe', 'text' => true],
            'edition.physical_extent' => ['target' => self::EDITION, 'column' => 'physical_extent', 'label' => 'Umfang', 'text' => true],
            'edition.language_code' => ['target' => self::EDITION, 'column' => 'language_code', 'label' => 'Sprache', 'text' => false],
            'edition.original_language_code' => ['target' => self::EDITION, 'column' => 'original_language_code', 'label' => 'Originalsprache', 'text' => false],
            'edition.media_type' => ['target' => self::EDITION, 'column' => 'media_type', 'label' => 'Medientyp', 'text' => false],
            'edition.series_statement' => ['target' => self::EDITION, 'column' => 'series_statement', 'label' => 'Reihe', 'text' => true],
            'edition.summary' => ['target' => self::EDITION, 'column' => 'summary', 'label' => 'Zusammenfassung', 'text' => true],
            'edition.subject_keywords' => ['target' => self::EDITION, 'column' => 'subject_keywords', 'label' => 'Schlagwörter', 'text' => true],
            'edition.target_audience' => ['target' => self::EDITION, 'column' => 'target_audience', 'label' => 'Zielgruppe', 'text' => true],
            'edition.isbn' => ['target' => self::EDITION, 'column' => 'isbn', 'label' => 'ISBN', 'text' => false],
        ];
    }

    /**
     * Felder, in denen "?" im Wort ein Hinweis auf verlorene Umlaute ist (Freitexte wie Zusammenfassungen ausgenommen).
     *
     * @return list<string>
     */
    public static function lostCharacterFields(): array
    {
        return [
            'title.preferred_title', 'title.subtitle', 'edition.responsibility_statement', 'edition.publisher_name',
            'edition.publication_place', 'edition.edition_statement', 'edition.series_statement', 'edition.physical_extent',
        ];
    }

    public static function roleLabel(string $roleKey): string
    {
        return match ($roleKey) {
            'author' => 'Autor:in',
            'illustrator' => 'Illustrator:in',
            'translator' => 'Übersetzer:in',
            'editor' => 'Herausgeber:in',
            'contributor' => 'Mitwirkende:r',
            default => $roleKey,
        };
    }

    /** Aktueller Wert eines Felds als Text (leer → null). Titel und Ausgabe müssen geladen sein. */
    public static function value(Edition $edition, string $key): ?string
    {
        $field = self::all()[$key] ?? null;

        if ($field === null) {
            return null;
        }

        $raw = $field['target'] === self::TITLE
            ? $edition->title->getAttribute($field['column'])
            : $edition->getAttribute($field['column']);

        if ($raw === null || (is_string($raw) && trim($raw) === '')) {
            return null;
        }

        return is_scalar($raw) ? (string) $raw : null;
    }
}
