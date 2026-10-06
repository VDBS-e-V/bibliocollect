<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Support;

use Illuminate\Support\HtmlString;

/** Darstellung von Metadatenwerten in der Prüfansicht: beschädigte Stellen werden sichtbar markiert. */
final class CatalogQualityPresenter
{
    /** @return array<string, string> */
    public static function kindLabels(): array
    {
        return [
            'fill' => 'ergänzt',
            'fix' => 'korrigiert',
            'local' => 'bereinigt',
            'add' => 'Person ergänzen',
            'rename' => 'Name korrigiert',
            'differs' => 'abweichend',
        ];
    }

    /** Unsichtbare Steuerzeichen und verdächtige Fragezeichen im Wort erhalten eine Markierung. */
    public static function highlight(?string $value): HtmlString
    {
        if ($value === null || trim($value) === '') {
            return new HtmlString('<span class="bc-catalog-muted">leer</span>');
        }

        $marked = preg_replace(
            '/[\x{0080}-\x{009F}]/u',
            '<mark class="bc-quality-mark" title="Unsichtbares Steuerzeichen">¤</mark>',
            e($value),
        ) ?? e($value);

        $marked = preg_replace(
            '/(?<=\p{L})\?(?=\p{L})/u',
            '<mark class="bc-quality-mark" title="Möglicherweise ein verlorener Umlaut">?</mark>',
            $marked,
        ) ?? $marked;

        return new HtmlString($marked);
    }

    public static function plain(?string $value, int $limit = 400): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit).' …' : $value;
    }
}
