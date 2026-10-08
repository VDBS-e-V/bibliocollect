<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Import;

use InvalidArgumentException;

/**
 * Liest die Importdatei für Ausleihkonten: Excel (.xlsx) oder CSV (Trennzeichen Semikolon, Komma oder Tabulator, UTF-8 oder Windows-1252).
 *
 * Erwartete Spalten (Reihenfolge egal, Groß-/Kleinschreibung egal): vorname, nachname, geburtsdatum und optional email.
 * Pflicht sind vorname, nachname und geburtsdatum. Die Klasse wird beim Import gewählt; weitere Spalten (z. B. klasse)
 * werden ignoriert.
 */
final class PatronCsvParser
{
    public const MAX_ROWS = 5000;

    public const COLUMNS = ['vorname', 'nachname', 'geburtsdatum', 'email'];

    private const REQUIRED = ['vorname', 'nachname', 'geburtsdatum'];

    private const ALIASES = [
        'vorname' => 'vorname',
        'firstname' => 'vorname',
        'nachname' => 'nachname',
        'familienname' => 'nachname',
        'name' => 'nachname',
        'lastname' => 'nachname',
        'geburtsdatum' => 'geburtsdatum',
        'geboren' => 'geburtsdatum',
        'geburtstag' => 'geburtsdatum',
        'email' => 'email',
        'emailadresse' => 'email',
        'mail' => 'email',
    ];

    /**
     * @return list<array{line: int, values: array<string, string>}>
     *
     * @throws InvalidArgumentException bei unlesbarer Datei, fehlenden Pflichtspalten oder zu vielen Zeilen
     */
    public function parse(string $path): array
    {
        $isExcel = XlsxReader::looksLikeXlsx($path);
        $lines = $isExcel ? (new XlsxReader)->read($path, self::MAX_ROWS + 500) : $this->csvLines($path);

        if ($lines === []) {
            throw new InvalidArgumentException('Die Datei ist leer oder nicht lesbar.');
        }

        $header = array_shift($lines);

        $map = [];

        foreach ($header as $index => $title) {
            $key = self::ALIASES[$this->normalizeHeader((string) $title)] ?? null;

            if ($key !== null && ! in_array($key, $map, true)) {
                $map[$index] = $key;
            }
        }

        $missing = array_diff(self::REQUIRED, $map);

        if ($missing !== []) {
            throw new InvalidArgumentException('Es fehlen Pflichtspalten: '.implode(', ', $missing).'.');
        }

        $rows = [];
        $line = 1;

        foreach ($lines as $cells) {
            $line++;

            if (implode('', array_map(static fn ($cell): string => trim((string) $cell), $cells)) === '') {
                continue;
            }

            if (count($rows) >= self::MAX_ROWS) {
                throw new InvalidArgumentException('Die Datei enthält mehr als '.self::MAX_ROWS.' Zeilen. Bitte in mehrere Dateien aufteilen.');
            }

            $values = array_fill_keys(self::COLUMNS, '');

            foreach ($map as $index => $key) {
                $values[$key] = trim((string) ($cells[$index] ?? ''));
            }

            // Excel speichert Datumswerte als fortlaufende Tageszahl (Tage seit 30.12.1899).
            if ($isExcel && preg_match('/^\d{4,6}(\.0+)?$/', $values['geburtsdatum']) === 1) {
                $values['geburtsdatum'] = gmdate('Y-m-d', (int) round(((float) $values['geburtsdatum'] - 25569) * 86400));
            }

            $rows[] = ['line' => $line, 'values' => $values];
        }

        if ($rows === []) {
            throw new InvalidArgumentException('Die Datei enthält keine Datenzeilen.');
        }

        return $rows;
    }

    /** @return list<list<string>> */
    private function csvLines(string $path): array
    {
        $contents = @file_get_contents($path);

        if ($contents === false || trim($contents) === '') {
            throw new InvalidArgumentException('Die Datei ist leer oder nicht lesbar.');
        }

        $contents = $this->toUtf8($contents);
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new InvalidArgumentException('Die Datei konnte nicht gelesen werden.');
        }

        fwrite($stream, $contents);
        rewind($stream);

        $delimiter = $this->detectDelimiter($contents);
        $lines = [];

        while (($cells = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
            $lines[] = array_map(static fn ($cell): string => (string) $cell, $cells === [null] ? [''] : $cells);
        }

        fclose($stream);

        return $lines;
    }

    private function toUtf8(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        return $contents;
    }

    private function detectDelimiter(string $contents): string
    {
        $firstLine = strtok($contents, "\n") ?: '';
        $best = ';';
        $bestCount = 0;

        foreach ([';', ',', "\t"] as $candidate) {
            $count = substr_count($firstLine, $candidate);

            if ($count > $bestCount) {
                $best = $candidate;
                $bestCount = $count;
            }
        }

        return $best;
    }

    private function normalizeHeader(string $title): string
    {
        $title = mb_strtolower(trim($title));
        $title = strtr($title, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);

        return preg_replace('/[^a-z0-9]/', '', $title) ?? '';
    }
}
