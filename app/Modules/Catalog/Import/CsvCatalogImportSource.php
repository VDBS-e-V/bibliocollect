<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Import;

use App\Modules\Catalog\Contracts\CatalogImportSource;
use App\Modules\Catalog\DTOs\CatalogImportSourceRecord;
use App\Modules\Catalog\Exceptions\CatalogImportSourceException;
use Generator;

final class CsvCatalogImportSource implements CatalogImportSource
{
    /** @var list<string>|null */
    private ?array $headers = null;

    private ?string $delimiter = null;

    public function __construct(private readonly string $path)
    {
        if (! is_file($this->path) || ! is_readable($this->path)) {
            throw new CatalogImportSourceException('Die CSV-Datei konnte nicht gelesen werden.');
        }
    }

    public function format(): string
    {
        return 'csv';
    }

    /** @return list<string> */
    public function headers(): array
    {
        $this->initialize();

        return $this->headers ?? [];
    }

    /** @return Generator<int, CatalogImportSourceRecord> */
    public function records(): iterable
    {
        $this->initialize();

        $handle = fopen($this->path, 'rb');

        if ($handle === false) {
            throw new CatalogImportSourceException('Die CSV-Datei konnte nicht geöffnet werden.');
        }

        try {
            fgetcsv($handle, 0, $this->delimiter ?? ',', '"', '');
            $rowNumber = 1;

            while (($row = fgetcsv($handle, 0, $this->delimiter ?? ',', '"', '')) !== false) {
                $rowNumber++;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $headers = $this->headers ?? [];
                $errors = [];

                if (count($row) !== count($headers)) {
                    $errors[] = sprintf(
                        'Zeile %d enthält %d Spalten; erwartet werden %d.',
                        $rowNumber,
                        count($row),
                        count($headers),
                    );
                }

                $row = array_pad(array_slice($row, 0, count($headers)), count($headers), null);
                $values = [];

                foreach ($headers as $index => $header) {
                    $value = $row[$index] ?? null;
                    $values[$header] = is_string($value) ? $value : null;
                }

                yield new CatalogImportSourceRecord($rowNumber, $values, $errors);
            }
        } finally {
            fclose($handle);
        }
    }

    private function initialize(): void
    {
        if ($this->headers !== null && $this->delimiter !== null) {
            return;
        }

        $handle = fopen($this->path, 'rb');

        if ($handle === false) {
            throw new CatalogImportSourceException('Die CSV-Datei konnte nicht geöffnet werden.');
        }

        try {
            $line = fgets($handle);

            if ($line === false) {
                throw new CatalogImportSourceException('Die CSV-Datei ist leer.');
            }

            $this->delimiter = $this->detectDelimiter($line);
            rewind($handle);
            $headers = fgetcsv($handle, 0, $this->delimiter, '"', '');

            if ($headers === false) {
                throw new CatalogImportSourceException('Die CSV-Datei enthält keine Kopfzeile.');
            }

            $normalized = [];

            foreach ($headers as $index => $header) {
                $value = is_string($header) ? trim($header) : '';

                if ($index === 0) {
                    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
                }

                if ($value === '') {
                    throw new CatalogImportSourceException('Die CSV-Kopfzeile enthält einen leeren Spaltennamen.');
                }

                if (in_array($value, $normalized, true)) {
                    throw new CatalogImportSourceException("Die CSV-Kopfzeile enthält die Spalte [{$value}] mehrfach.");
                }

                $normalized[] = $value;
            }

            $this->headers = $normalized;
        } finally {
            fclose($handle);
        }
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t"];
        $best = ',';
        $bestCount = 0;

        foreach ($candidates as $candidate) {
            $columns = str_getcsv($line, $candidate, '"', '');
            $count = count($columns);

            if ($count > $bestCount) {
                $best = $candidate;
                $bestCount = $count;
            }
        }

        return $best;
    }

    /** @param list<string|null> $row */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (is_string($value) && trim($value) !== '') {
                return false;
            }
        }

        return true;
    }
}
