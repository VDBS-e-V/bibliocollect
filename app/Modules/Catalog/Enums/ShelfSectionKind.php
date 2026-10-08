<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

/**
 * Die drei Ebenen über dem Regalbrett: Bereichsgruppe (I), Bereich (A) und Regal (1). Der Standort „I. A 1 a“ ist daraus und
 * der Bezeichnung des Regalbretts (a) zusammengesetzt.
 */
enum ShelfSectionKind: string
{
    case Group = 'group';
    case Area = 'area';
    case Rack = 'rack';

    public function label(): string
    {
        return match ($this) {
            self::Group => 'Bereichsgruppe',
            self::Area => 'Bereich',
            self::Rack => 'Regal',
        };
    }

    /** Die Ebene, in der dieser Eintrag liegt (null bei der Bereichsgruppe). */
    public function parentKind(): ?self
    {
        return match ($this) {
            self::Group => null,
            self::Area => self::Group,
            self::Rack => self::Area,
        };
    }

    public function childKind(): ?self
    {
        return match ($this) {
            self::Group => self::Area,
            self::Area => self::Rack,
            self::Rack => null,
        };
    }
}
