<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Enums;

enum CardBlockReason: string
{
    case Lost = 'lost';
    case Replaced = 'replaced';
    case Defective = 'defective';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Lost => 'verloren',
            self::Replaced => 'durch neuen Ausweis ersetzt',
            self::Defective => 'defekt',
            self::Withdrawn => 'eingezogen oder Konto beendet',
        };
    }
}
