<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Lookup\Support;

/**
 * Sprachcodes der Quellen vereinheitlichen. Die DNB und Open Library liefern dreistellige Codes (MARC: „ger“, „eng“), Google Books
 * zweistellige (ISO 639-1: „de“, „en“). Im Katalog gilt der dreistellige Code, damit der Filter „Sprache“ nicht dieselbe Sprache doppelt führt.
 */
final class LanguageCodes
{
    /** @var array<string, array{marc: string, label: string}> ISO 639-1 */
    private const KNOWN = [
        'de' => ['marc' => 'ger', 'label' => 'Deutsch'],
        'en' => ['marc' => 'eng', 'label' => 'Englisch'],
        'fr' => ['marc' => 'fre', 'label' => 'Französisch'],
        'es' => ['marc' => 'spa', 'label' => 'Spanisch'],
        'it' => ['marc' => 'ita', 'label' => 'Italienisch'],
        'pt' => ['marc' => 'por', 'label' => 'Portugiesisch'],
        'nl' => ['marc' => 'dut', 'label' => 'Niederländisch'],
        'pl' => ['marc' => 'pol', 'label' => 'Polnisch'],
        'ru' => ['marc' => 'rus', 'label' => 'Russisch'],
        'uk' => ['marc' => 'ukr', 'label' => 'Ukrainisch'],
        'tr' => ['marc' => 'tur', 'label' => 'Türkisch'],
        'ar' => ['marc' => 'ara', 'label' => 'Arabisch'],
        'fa' => ['marc' => 'per', 'label' => 'Persisch'],
        'ro' => ['marc' => 'rum', 'label' => 'Rumänisch'],
        'bg' => ['marc' => 'bul', 'label' => 'Bulgarisch'],
        'sr' => ['marc' => 'srp', 'label' => 'Serbisch'],
        'hr' => ['marc' => 'hrv', 'label' => 'Kroatisch'],
        'bs' => ['marc' => 'bos', 'label' => 'Bosnisch'],
        'el' => ['marc' => 'gre', 'label' => 'Griechisch'],
        'sq' => ['marc' => 'alb', 'label' => 'Albanisch'],
        'hu' => ['marc' => 'hun', 'label' => 'Ungarisch'],
        'cs' => ['marc' => 'cze', 'label' => 'Tschechisch'],
        'sv' => ['marc' => 'swe', 'label' => 'Schwedisch'],
        'da' => ['marc' => 'dan', 'label' => 'Dänisch'],
        'no' => ['marc' => 'nor', 'label' => 'Norwegisch'],
        'fi' => ['marc' => 'fin', 'label' => 'Finnisch'],
        'zh' => ['marc' => 'chi', 'label' => 'Chinesisch'],
        'ja' => ['marc' => 'jpn', 'label' => 'Japanisch'],
        'ko' => ['marc' => 'kor', 'label' => 'Koreanisch'],
        'hi' => ['marc' => 'hin', 'label' => 'Hindi'],
        'ku' => ['marc' => 'kur', 'label' => 'Kurdisch'],
        'la' => ['marc' => 'lat', 'label' => 'Latein'],
    ];

    /** Dreistellige Varianten (ISO 639-2/T) auf den MARC-Code. */
    private const ALIASES = ['deu' => 'ger', 'fra' => 'fre', 'nld' => 'dut', 'ces' => 'cze', 'ron' => 'rum', 'ell' => 'gre', 'sqi' => 'alb', 'zho' => 'chi', 'fas' => 'per'];

    /** Der Code, wie der Katalog ihn führt (dreistellig, klein). Unbekanntes bleibt unverändert, Leeres wird null. */
    public static function marc(?string $code): ?string
    {
        $code = $code === null ? '' : mb_strtolower(trim($code));
        $code = preg_replace('#^/languages/#', '', $code) ?? $code;
        $code = explode('-', $code)[0];

        if ($code === '' || $code === 'und' || $code === 'zxx' || $code === 'mul') {
            return null;
        }

        if (isset(self::KNOWN[$code])) {
            return self::KNOWN[$code]['marc'];
        }

        return self::ALIASES[$code] ?? $code;
    }

    /** Sprachname auf Deutsch zu einem ein- oder dreistelligen Code; null, wenn unbekannt. */
    public static function label(?string $code): ?string
    {
        $marc = self::marc($code);

        if ($marc === null) {
            return null;
        }

        foreach (self::KNOWN as $entry) {
            if ($entry['marc'] === $marc) {
                return $entry['label'];
            }
        }

        return null;
    }

    /**
     * Sprachgruppe einer ISBN-13 (die Ziffern nach 978 oder 979). Gruppe 3 gehört zum deutschen Sprachraum.
     */
    public static function isbnGroup(string $isbn13): ?string
    {
        if (preg_match('/^97[89](\d)/', $isbn13, $m) !== 1) {
            return null;
        }

        return $m[1];
    }
}
