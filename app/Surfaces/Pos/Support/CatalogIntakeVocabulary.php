<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Support;

/**
 * Menschenlesbare Bezeichnungen für die Erfassung. Die zugrunde liegenden Werte bleiben offene
 * Vokabulare: Unbekannte Werte (z. B. aus einer externen Quelle) werden unverändert angezeigt.
 */
final class CatalogIntakeVocabulary
{
    /** @return array<string, string> */
    public static function roles(): array
    {
        return [
            'author' => 'Autor:in',
            'illustrator' => 'Illustrator:in',
            'translator' => 'Übersetzer:in',
            'editor' => 'Herausgeber:in',
            'contributor' => 'Mitwirkende:r',
        ];
    }

    /** @return array<string, string> */
    public static function mediaTypes(): array
    {
        return [
            'book' => 'Buch',
            'audiobook' => 'Hörbuch',
            'ebook' => 'E-Book',
            'magazine' => 'Zeitschrift',
            'dvd' => 'DVD / Video',
            'game' => 'Spiel',
        ];
    }

    /** @return array<string, string> */
    public static function copyStatuses(): array
    {
        return [
            'active' => 'Aktiv',
            'damaged' => 'Beschädigt',
            'lost' => 'Verloren',
            'withdrawn' => 'Ausgesondert',
        ];
    }

    public static function role(string $key): string
    {
        return self::roles()[$key] ?? $key;
    }

    public static function mediaType(?string $key): ?string
    {
        return $key === null ? null : (self::mediaTypes()[$key] ?? $key);
    }

    public static function copyStatus(string $key): string
    {
        return self::copyStatuses()[$key] ?? $key;
    }
}
