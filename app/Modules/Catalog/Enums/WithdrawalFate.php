<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

/** Was mit einem ausgesonderten Exemplar geschieht. */
enum WithdrawalFate: string
{
    case Disposed = 'disposed';
    case Donated = 'donated';
    case Sold = 'sold';
    case Archived = 'archived';
    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::Disposed => 'Entsorgt (Altpapier)',
            self::Donated => 'Verschenkt oder gespendet',
            self::Sold => 'Verkauft',
            self::Archived => 'Aufbewahrt (Archiv)',
            self::Open => 'Noch offen',
        };
    }

    public static function describe(?string $stored): string
    {
        $case = $stored !== null ? self::tryFrom($stored) : null;

        return $case?->label() ?? ($stored !== null && $stored !== '' ? $stored : 'ohne Angabe');
    }
}
