<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Support;

/** Ausweisnummern: 9 Zufallsziffern plus Luhn-Prüfziffer (10 Stellen), damit Tippfehler auffallen. */
final class PatronCardNumber
{
    public static function random(): int
    {
        return random_int(100_000_000, 999_999_999);
    }

    public static function fromBase(int $base): string
    {
        return $base.self::checkDigit((string) $base);
    }

    public static function base(string $number): int
    {
        return (int) substr($number, 0, 9);
    }

    public static function isValid(string $number): bool
    {
        return preg_match('/^\d{10}$/', $number) === 1 && self::fromBase((int) substr($number, 0, 9)) === $number;
    }

    private static function checkDigit(string $digits): int
    {
        $sum = 0;

        foreach (array_reverse(str_split($digits)) as $index => $char) {
            $value = (int) $char;

            if ($index % 2 === 0) {
                $value *= 2;

                if ($value > 9) {
                    $value -= 9;
                }
            }

            $sum += $value;
        }

        return (10 - $sum % 10) % 10;
    }
}
