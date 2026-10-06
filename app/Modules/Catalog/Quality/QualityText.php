<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Quality;

use Normalizer;

/**
 * Textprüfungen und -bereinigung für die Metadatenqualität.
 *
 * Hintergrund der "verlorenen Umlaute": Der Altbestand speichert zerlegte Zeichen (NFD, z. B. "u" + Trema)
 * und hat dabei das Kombinationszeichen durch "?" ersetzt ("Mu?nchen"). Aus dem DNB-Wert "München" lässt sich
 * dieselbe Verfälschung nachbauen ({@see self::lostMarksMask()}); so wird eine Korrektur nur vorgeschlagen,
 * wenn sie wirklich der Ursprung des beschädigten Werts ist, nie geraten.
 */
final class QualityText
{
    private const MOJIBAKE = ['Ã¤', 'Ã¶', 'Ã¼', 'Ã„', 'Ã–', 'Ãœ', 'ÃŸ', 'â€“', 'â€”', 'â€ž', 'â€œ', 'â€™', 'â€˜', 'Â '];

    /** Platzhalter wie „Zu diesem Buch gibt es aktuell noch keine Inhaltsangabe“ sind keine Zusammenfassung. */
    public static function isPlaceholderSummary(?string $value): bool
    {
        return $value !== null && preg_match('/keine\s+Inhaltsangabe/iu', $value) === 1;
    }

    public static function hasControlCharacters(?string $value): bool
    {
        return $value !== null && preg_match('/[\x{0080}-\x{009F}]/u', $value) === 1;
    }

    public static function hasLostCharacters(?string $value): bool
    {
        return $value !== null && preg_match('/\p{L}\?\p{L}/u', $value) === 1;
    }

    public static function hasMojibake(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        foreach (self::MOJIBAKE as $pattern) {
            if (str_contains($value, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /** Entfernt C1-Steuerzeichen (DNB-Sortiermarker), vereinheitlicht auf NFC und Leerraum. */
    public static function clean(string $value): string
    {
        $cleaned = preg_replace('/[\x{0080}-\x{009F}]/u', '', $value) ?? $value;
        $composed = Normalizer::normalize($cleaned, Normalizer::FORM_C);
        $cleaned = is_string($composed) ? $composed : $cleaned;

        return trim(preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned);
    }

    /** So sähe der Wert aus, wenn jedes Kombinationszeichen durch "?" ersetzt worden wäre. */
    public static function lostMarksMask(string $value): string
    {
        $decomposed = Normalizer::normalize($value, Normalizer::FORM_D);
        $decomposed = is_string($decomposed) ? $decomposed : $value;

        return preg_replace('/\p{Mn}/u', '?', $decomposed) ?? $decomposed;
    }

    /** Gleichheit unabhängig von Groß-/Kleinschreibung, Normalform und Leerraum. */
    public static function same(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return $a === $b;
        }

        return mb_strtolower(self::clean($a)) === mb_strtolower(self::clean($b));
    }

    /** Wie {@see self::same()}, aber $damaged darf Kombinationszeichen als "?" verloren haben. */
    public static function sameAfterLoss(?string $damaged, ?string $original): bool
    {
        if ($damaged === null || $original === null) {
            return false;
        }

        return mb_strtolower(self::clean($damaged)) === mb_strtolower(self::lostMarksMask(self::clean($original)));
    }
}
