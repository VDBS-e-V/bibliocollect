<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

/** Warum ein Exemplar ausgesondert wird. */
enum WithdrawalReason: string
{
    case Damaged = 'damaged';
    case Outdated = 'outdated';
    case Duplicate = 'duplicate';
    case Unused = 'unused';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Damaged => 'Beschädigt oder abgenutzt',
            self::Outdated => 'Inhaltlich veraltet',
            self::Duplicate => 'Doppelt vorhanden',
            self::Unused => 'Wird nicht mehr gelesen',
            self::Other => 'Sonstiger Grund',
        };
    }

    /** Klartext zu einem gespeicherten Wert; ältere Freitexte aus dem Altsystem bleiben unverändert lesbar. */
    public static function describe(?string $stored): string
    {
        $case = $stored !== null ? self::tryFrom($stored) : null;

        return $case?->label() ?? ($stored !== null && $stored !== '' ? $stored : 'ohne Angabe');
    }
}
