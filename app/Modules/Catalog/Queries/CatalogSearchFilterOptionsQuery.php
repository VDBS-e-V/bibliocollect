<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Edition;

final class CatalogSearchFilterOptionsQuery
{
    /**
     * @return array{mediaTypes: list<string>, languageCodes: list<string>}
     */
    public function execute(): array
    {
        return [
            'mediaTypes' => $this->distinctValues('media_type'),
            'languageCodes' => $this->distinctValues('language_code'),
        ];
    }

    /** @return list<string> */
    private function distinctValues(string $column): array
    {
        return Edition::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->filter(static fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(static fn (mixed $value): string => mb_strtolower(trim((string) $value)))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
