<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Import;

use InvalidArgumentException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Liest das erste Tabellenblatt einer Excel-Datei (.xlsx) als Zeilen aus Text. Ohne zusätzliche Bibliothek: eine .xlsx-Datei ist ein
 * ZIP mit XML. Formeln werden nicht ausgewertet (es zählt der gespeicherte Wert), Formatierungen werden ignoriert.
 */
final class XlsxReader
{
    private const MAX_PART_BYTES = 30 * 1024 * 1024;

    /** Zeigt eine .xlsx-Datei an der ZIP-Kennung (PK), unabhängig vom Dateinamen. */
    public static function looksLikeXlsx(string $path): bool
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $head = (string) fread($handle, 4);
        fclose($handle);

        return str_starts_with($head, "PK\x03\x04");
    }

    /**
     * @return list<list<string>> Zeile für Zeile, Zellen von links (A) an; leere Zellen sind leere Texte
     *
     * @throws InvalidArgumentException bei Dateien, die keine lesbare Excel-Arbeitsmappe sind
     */
    public function read(string $path, int $maxRows = 6000): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('Die Excel-Datei ist nicht lesbar. Bitte als .xlsx speichern.');
        }

        try {
            $sheetPath = $this->firstSheetPath($zip);
            $sheet = $this->xml($zip, $sheetPath);
            $shared = $this->sharedStrings($zip);
        } finally {
            $zip->close();
        }

        $rows = [];

        foreach ($sheet->sheetData->row ?? [] as $row) {
            $cells = [];
            $fallback = 0;

            foreach ($row->c as $cell) {
                $index = $this->columnIndex((string) $cell['r'], $fallback);
                $fallback = $index + 1;
                $cells[$index] = $this->cellValue($cell, $shared);
            }

            $line = [];

            if ($cells !== []) {
                for ($i = 0; $i <= max(array_keys($cells)); $i++) {
                    $line[] = $cells[$i] ?? '';
                }
            }

            $number = (int) $row['r'];
            $number = $number > 0 ? $number : count($rows) + 1;

            while (count($rows) < $number - 1) {
                $rows[] = [];
            }

            $rows[] = $line;

            if (count($rows) > $maxRows) {
                throw new InvalidArgumentException('Die Excel-Datei enthält zu viele Zeilen.');
            }
        }

        return $rows;
    }

    /** @param  list<string>  $shared */
    private function cellValue(SimpleXMLElement $cell, array $shared): string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            return trim($this->text($cell->is));
        }

        $value = trim((string) $cell->v);

        return match ($type) {
            's' => $shared[(int) $value] ?? '',
            'b' => $value === '1' ? 'wahr' : 'falsch',
            default => $value,
        };
    }

    private function firstSheetPath(ZipArchive $zip): string
    {
        $workbook = $this->xml($zip, 'xl/workbook.xml');
        $first = $workbook->sheets->sheet[0] ?? null;

        if ($first !== null) {
            $relationId = (string) $first->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')?->id;
            $relations = $this->xml($zip, 'xl/_rels/workbook.xml.rels');

            foreach ($relations->Relationship ?? [] as $relation) {
                if ((string) $relation['Id'] === $relationId) {
                    $target = ltrim((string) $relation['Target'], '/');

                    return str_starts_with($target, 'xl/') ? $target : 'xl/'.$target;
                }
            }
        }

        if ($zip->locateName('xl/worksheets/sheet1.xml') !== false) {
            return 'xl/worksheets/sheet1.xml';
        }

        throw new InvalidArgumentException('In der Excel-Datei wurde kein Tabellenblatt gefunden.');
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $strings = [];

        foreach ($this->xml($zip, 'xl/sharedStrings.xml')->si ?? [] as $item) {
            $strings[] = trim($this->text($item));
        }

        return $strings;
    }

    /** Text eines Elements, auch bei Formatierung in mehreren Teilen (<r><t>…). */
    private function text(?SimpleXMLElement $element): string
    {
        if ($element === null) {
            return '';
        }

        $text = '';

        foreach ($element->xpath('.//*[local-name()="t"]') ?: [] as $part) {
            $text .= (string) $part;
        }

        return $text;
    }

    private function columnIndex(string $reference, int $fallback): int
    {
        if (preg_match('/^([A-Z]+)/', strtoupper($reference), $match) !== 1) {
            return $fallback;
        }

        $index = 0;

        foreach (str_split($match[1]) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function xml(ZipArchive $zip, string $name): SimpleXMLElement
    {
        $stat = $zip->statName($name);

        if ($stat === false || $stat['size'] > self::MAX_PART_BYTES) {
            throw new InvalidArgumentException('Die Excel-Datei ist beschädigt oder zu groß.');
        }

        $contents = $zip->getFromName($name);
        $xml = $contents === false ? false : simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);

        if (! $xml instanceof SimpleXMLElement) {
            throw new InvalidArgumentException('Die Excel-Datei ist beschädigt.');
        }

        return $xml;
    }
}
