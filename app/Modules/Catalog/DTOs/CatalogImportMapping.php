<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

use App\Modules\Catalog\Enums\CatalogImportField;

final readonly class CatalogImportMapping
{
    /** @param array<string, string|null> $columns */
    public function __construct(public array $columns) {}

    public function column(CatalogImportField $field): ?string
    {
        $column = $this->columns[$field->value] ?? null;

        return is_string($column) && $column !== '' ? $column : null;
    }

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        $mapping = [];

        foreach (CatalogImportField::cases() as $field) {
            $mapping[$field->value] = $this->column($field);
        }

        return $mapping;
    }

    /** @param array<string, mixed> $mapping */
    public static function fromArray(array $mapping): self
    {
        $columns = [];

        foreach (CatalogImportField::cases() as $field) {
            $value = $mapping[$field->value] ?? null;
            $columns[$field->value] = is_string($value) && $value !== '' ? $value : null;
        }

        return new self($columns);
    }
}
