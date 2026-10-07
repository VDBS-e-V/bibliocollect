<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

/** Regel für neue Inventarnummern (Mediennummern): genau sieben Ziffern, zum Beispiel 0012482. */
final class CatalogInventoryNumber
{
    public const DIGITS = 7;

    public const MESSAGE = 'Die Inventarnummer besteht aus genau 7 Ziffern, zum Beispiel 0012482.';

    public static function isValid(string $number): bool
    {
        return preg_match('/^\d{'.self::DIGITS.'}$/', $number) === 1;
    }
}
