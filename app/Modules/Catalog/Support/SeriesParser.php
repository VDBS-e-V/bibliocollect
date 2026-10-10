<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Support;

/**
 * Zerlegt die Reihenangabe einer Ausgabe („Die Schule der magischen Tiere ; 3“, „Was ist was, Band 12“) in Reihenname, Bandnummer
 * und einen Vergleichsschlüssel, mit dem Schreibvarianten („Gullivers Bu?cher“, „Gullivers Bücher“, „[Fischer]“) zusammenfallen.
 *
 * Der Altbestand führt hier oft Verlags- und Taschenbuchreihen („dtv“, „Fischer“), selten echte Reihen mit Band; das Zerlegen ist
 * deshalb vorsichtig: Reine Zahlen sind keine Reihe, und eine Nummer wird nur am Ende und mit Trennzeichen oder Bandwort erkannt.
 */
final class SeriesParser
{
    private const VOLUME_WORD = '(?:Bd|Band|Nr|No|Teil|Heft|Vol|Volume)\.?';

    /** @return array{name: string, volume: ?string, key: string}|null */
    public static function parse(?string $statement): ?array
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', (string) $statement));
        $text = trim($text, " \t[]()");

        if ($text === '' || preg_match('/^[\d\s.,;:\-–\/]+$/u', $text) === 1) {
            return null;
        }

        $name = $text;
        $volume = null;
        $number = '(\d{1,4}(?:\s*[-–\/]\s*\d{1,4})?)';
        $word = self::VOLUME_WORD;

        // „Name ; 3“, „Name, Band 3“, „Name : Bd. 3“ (Trennzeichen) und „Name Band 3“ / „Name 3“ (nur Leerraum, höchstens dreistellig).
        if (preg_match('/^(.*?)\s*[;,:]\s*(?:'.$word.'\s*)?'.$number.'$/ui', $text, $m) === 1
            || preg_match('/^(.*?\S)\s+'.$word.'\s*'.$number.'$/ui', $text, $m) === 1
            || preg_match('/^(.*?[^\d\s])\s+(\d{1,3})$/u', $text, $m) === 1) {
            $candidate = trim($m[1], " \t,;:-–");

            if ($candidate !== '' && preg_match('/^[\d\s.]+$/u', $candidate) !== 1) {
                $name = $candidate;
                $volume = preg_replace('/\s+/u', '', $m[2]);
            }
        }

        $name = trim((string) preg_replace('/\s+/u', ' ', $name), " \t[]()");
        $key = self::key($name);

        if ($key === '' || mb_strlen($key) < 2) {
            return null;
        }

        return ['name' => $name, 'volume' => $volume, 'key' => $key];
    }

    /** Vergleichsschlüssel: Kleinbuchstaben ohne Umlaute und Sonderzeichen; ein „?“ (zerstörter Umlaut im Altbestand) fällt weg. */
    public static function key(string $name): string
    {
        // Zerlegte Umlaute (u + Trema) zuerst zusammenziehen, dann gleich behandeln wie die zusammengesetzten.
        $name = (string) preg_replace('/\x{0308}/u', '', preg_replace('/([aouAOU])\x{0308}/u', '$1', $name));
        $folded = strtr(mb_strtolower($name), ['ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss', 'é' => 'e', 'è' => 'e', 'à' => 'a', 'ç' => 'c', '?' => '']);

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $folded));
    }

    /** Erste ganze Zahl der Bandangabe zum Sortieren („3“, „3-4“ → 3), sonst null. */
    public static function volumeNumber(?string $volume): ?int
    {
        return $volume !== null && preg_match('/\d+/', $volume, $m) === 1 ? (int) $m[0] : null;
    }
}
