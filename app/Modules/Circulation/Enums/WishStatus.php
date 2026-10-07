<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Enums;

enum WishStatus: string
{
    case New = 'new';
    case Accepted = 'accepted';
    case Ordered = 'ordered';
    case Fulfilled = 'fulfilled';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Neu',
            self::Accepted => 'Angenommen',
            self::Ordered => 'Bestellt',
            self::Fulfilled => 'Ist da',
            self::Declined => 'Abgelehnt',
            self::Withdrawn => 'Zurückgezogen',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::New, self::Accepted, self::Ordered], true);
    }

    /** @return list<string> */
    public static function openValues(): array
    {
        return [self::New->value, self::Accepted->value, self::Ordered->value];
    }

    /** Stände, die Mitarbeitende setzen können. */
    public function isDecision(): bool
    {
        return $this !== self::Withdrawn;
    }
}
