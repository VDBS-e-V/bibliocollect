<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Enums;

enum ReservationStatus: string
{
    /** In der Warteschlange, noch kein Exemplar frei. */
    case Waiting = 'waiting';

    /** Ein Exemplar liegt zur Abholung bereit. */
    case Ready = 'ready';

    case Fulfilled = 'fulfilled';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Wartet',
            self::Ready => 'Abholbereit',
            self::Fulfilled => 'Ausgeliehen',
            self::Cancelled => 'Storniert',
            self::Expired => 'Abholfrist abgelaufen',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Waiting || $this === self::Ready;
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return [self::Waiting->value, self::Ready->value];
    }
}
