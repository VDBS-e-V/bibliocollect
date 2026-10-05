<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Legacy;

use App\Modules\Catalog\Exceptions\LegacyCatalogImportException;
use JsonException;

final class PhpMyAdminJsonTableReader
{
    /**
     * @return list<array<string, mixed>>
     */
    public function read(string $path, string $table): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new LegacyCatalogImportException("Legacy-JSON [{$path}] ist nicht lesbar.");
        }

        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new LegacyCatalogImportException("Legacy-JSON [{$path}] ist ungültig.", 0, $exception);
        }

        if (! is_array($decoded)) {
            throw new LegacyCatalogImportException("Legacy-JSON [{$path}] enthält keine JSON-Struktur.");
        }

        $fromEnvelope = $this->fromPhpMyAdminEnvelope($decoded, $table);

        if ($fromEnvelope !== null) {
            return $fromEnvelope;
        }

        $named = $decoded[$table] ?? null;

        if (is_array($named)) {
            return $this->rowList($named, $path, $table);
        }

        if (array_is_list($decoded) && $this->looksLikeRows($decoded)) {
            return $this->rowList($decoded, $path, $table);
        }

        throw new LegacyCatalogImportException(
            "Legacy-JSON [{$path}] enthält die Tabelle [{$table}] nicht.",
        );
    }

    /**
     * @param  array<mixed>  $decoded
     * @return list<array<string, mixed>>|null
     */
    private function fromPhpMyAdminEnvelope(array $decoded, string $table): ?array
    {
        if (! array_is_list($decoded)) {
            return null;
        }

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['type'] ?? null) !== 'table') {
                continue;
            }

            $name = $item['name'] ?? null;

            if (! is_string($name) || strcasecmp($name, $table) !== 0) {
                continue;
            }

            $data = $item['data'] ?? [];

            if (! is_array($data)) {
                throw new LegacyCatalogImportException(
                    "Tabelle [{$table}] besitzt im phpMyAdmin-JSON keinen gültigen data-Block.",
                );
            }

            return $this->rowList($data, $table, $table);
        }

        return null;
    }

    /**
     * @param  array<mixed>  $rows
     * @return list<array<string, mixed>>
     */
    private function rowList(array $rows, string $path, string $table): array
    {
        $result = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                throw new LegacyCatalogImportException(
                    "Legacy-JSON [{$path}] enthält in [{$table}] an Position {$index} keine Objektzeile.",
                );
            }

            /** @var array<string, mixed> $row */
            $result[] = $row;
        }

        return $result;
    }

    /** @param array<mixed> $rows */
    private function looksLikeRows(array $rows): bool
    {
        if ($rows === []) {
            return true;
        }

        $first = $rows[0] ?? null;

        if (! is_array($first)) {
            return false;
        }

        return ! array_key_exists('type', $first);
    }
}
