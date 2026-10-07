<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Support;

use App\Modules\Patrons\Models\Patron;
use RuntimeException;

/**
 * Neue Bibliotheksnummern: sechs zufällige Ziffern ohne Kennung für Schüler:innen, Lehrkräfte usw. Die Nummer verrät
 * nichts über die Person und ist nicht fortlaufend. Sie beginnt nie mit 0 und ist einmalig.
 */
final class PatronLibraryNumber
{
    private const ATTEMPTS = 50;

    public static function generate(): string
    {
        for ($attempt = 0; $attempt < self::ATTEMPTS; $attempt++) {
            $number = (string) random_int(100_000, 999_999);

            if (! Patron::query()->where('library_number', $number)->exists()) {
                return $number;
            }
        }

        throw new RuntimeException('Es konnte keine freie Bibliotheksnummer gefunden werden.');
    }
}
