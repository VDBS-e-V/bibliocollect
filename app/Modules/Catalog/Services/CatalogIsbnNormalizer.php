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

    /**
     * Vergleichbare ISBN-13: eine ISBN-10 wird umgerechnet (978-Präfix, neue Prüfziffer), eine ISBN-13 bleibt.
     * Null, wenn der Wert keine Standard-ISBN ist.
     */
    public function toIsbn13(string $value): ?string
    {
        $compact = $this->normalize($value);

        if (! $this->isStandardFormat($compact)) {
            return null;
        }

        if (strlen($compact) === 13) {
            return $compact;
        }

        $base = '978'.substr($compact, 0, 9);
        $sum = 0;

        foreach (str_split($base) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return $base.((10 - $sum % 10) % 10);
    }
}
