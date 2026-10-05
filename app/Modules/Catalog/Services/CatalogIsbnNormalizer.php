<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

final class CatalogIsbnNormalizer
{
    public function normalize(string $value): string
    {
        $trimmed = trim($value);
        $withoutPrefix = preg_replace('/^isbn(?:-1[03])?\s*:?\s*/iu', '', $trimmed) ?? $trimmed;
        $compact = strtoupper(preg_replace('/[\s\p{Pd}]+/u', '', $withoutPrefix) ?? $withoutPrefix);

        if ($this->isStandardFormat($compact)) {
            return $compact;
        }

        return $trimmed;
    }

    public function isStandardFormat(string $value): bool
    {
        return preg_match('/^(?:\d{13}|\d{9}[\dX])$/', $value) === 1;
    }
}
