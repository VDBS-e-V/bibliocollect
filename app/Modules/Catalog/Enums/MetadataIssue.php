<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

/**
 * Arten von Metadatenproblemen. Die ersten Fälle sind echte Defekte; die beiden letzten sind nur
 * Anreicherung und zählen bewusst nicht als Mangel (sie fehlen im Altbestand fast überall).
 */
enum MetadataIssue: string
{
    case ControlCharacters = 'control_characters';
    case LostCharacters = 'lost_characters';
    case Mojibake = 'mojibake';
    case MissingContributors = 'missing_contributors';
    case MissingYear = 'missing_year';
    case MissingPublisher = 'missing_publisher';
    case MissingMediaType = 'missing_media_type';
    case MissingIsbn = 'missing_isbn';
    case MissingSummary = 'missing_summary';
    case MissingKeywords = 'missing_keywords';

    public function label(): string
    {
        return match ($this) {
            self::ControlCharacters => 'Unsichtbare Steuerzeichen',
            self::LostCharacters => 'Verlorene Umlaute („?“ im Wort)',
            self::Mojibake => 'Zeichensatzfehler',
            self::MissingContributors => 'Keine Verantwortlichen',
            self::MissingYear => 'Kein Erscheinungsjahr',
            self::MissingPublisher => 'Kein Verlag',
            self::MissingMediaType => 'Kein Medientyp',
            self::MissingIsbn => 'Keine ISBN',
            self::MissingSummary => 'Keine Zusammenfassung',
            self::MissingKeywords => 'Keine Schlagwörter',
        };
    }

    /** Kurzform für Badges und Filter. */
    public function shortLabel(): string
    {
        return match ($this) {
            self::ControlCharacters => 'Steuerzeichen',
            self::LostCharacters => 'Verlorene Umlaute',
            self::Mojibake => 'Zeichensatz',
            self::MissingContributors => 'Ohne Verantwortliche',
            self::MissingYear => 'Ohne Jahr',
            self::MissingPublisher => 'Ohne Verlag',
            self::MissingMediaType => 'Ohne Medientyp',
            self::MissingIsbn => 'Ohne ISBN',
            self::MissingSummary => 'Ohne Zusammenfassung',
            self::MissingKeywords => 'Ohne Schlagwörter',
        };
    }

    /** Gewicht für die Sortierung: Defekte, die Suche und Anzeige stören, zuerst. */
    public function weight(): int
    {
        return match ($this) {
            self::LostCharacters, self::Mojibake => 40,
            self::ControlCharacters => 30,
            self::MissingContributors => 25,
            self::MissingYear => 15,
            self::MissingPublisher, self::MissingMediaType => 10,
            self::MissingIsbn => 8,
            self::MissingSummary, self::MissingKeywords => 0,
        };
    }

    /** Nur Anreicherung: gehört nicht zu den echten Mängeln. */
    public function isEnrichment(): bool
    {
        return $this === self::MissingSummary || $this === self::MissingKeywords;
    }

    /** @return list<self> */
    public static function defects(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $issue): bool => ! $issue->isEnrichment()));
    }
}
