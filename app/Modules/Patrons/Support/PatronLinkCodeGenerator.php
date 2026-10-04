<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Support;

final class PatronLinkCodeGenerator
{
    private const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function generate(int $length = 10): string
    {
        $characters = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($index = 0; $index < $length; $index++) {
            $characters .= self::ALPHABET[random_int(0, $max)];
        }

        return substr($characters, 0, 5).'-'.substr($characters, 5);
    }
}
