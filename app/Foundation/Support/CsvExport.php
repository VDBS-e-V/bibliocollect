<?php

declare(strict_types=1);

namespace App\Foundation\Support;

/**
 * Schreibt CSV-Zeilen so, dass Tabellenprogramme Zellen nicht als Formel ausführen: Texte, die mit = + - @ oder einem
 * Tabulator beginnen, bekommen ein Hochkomma vorangestellt (Schutz vor „CSV-Injection“ durch Titel oder Namen).
 */
final class CsvExport
{
    /**
     * @param  resource  $handle
     * @param  array<int|string, mixed>  $row
     */
    public static function put($handle, array $row, string $separator = ';'): int|false
    {
        return fputcsv($handle, array_map(static fn (mixed $cell): mixed => self::cell($cell), $row), $separator, '"', '');
    }

    public static function cell(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) && preg_match('/^[-+]?\d+([.,]\d+)?$/', $value) !== 1) {
            return "'".$value;
        }

        return $value;
    }
}
