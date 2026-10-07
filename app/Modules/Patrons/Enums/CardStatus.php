<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Enums;

enum CardStatus: string
{
    case Generated = 'generated';
    case InPrint = 'in_print';
    case Available = 'available';
    case Assigned = 'assigned';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Generated => 'Erzeugt',
            self::InPrint => 'Im Druck',
            self::Available => 'Verfügbar',
            self::Assigned => 'Zugeordnet',
            self::Blocked => 'Gesperrt',
        };
    }

    /** @return list<string> Ausweise, die noch keiner Person gehören und ausgegeben werden können. */
    public static function unassignedValues(): array
    {
        return [self::Generated->value, self::InPrint->value, self::Available->value];
    }

    public function isUnassigned(): bool
    {
        return in_array($this->value, self::unassignedValues(), true);
    }
}
