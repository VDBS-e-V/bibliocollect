<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum MetadataReviewStatus: string
{
    case Open = 'open';
    case Dismissed = 'dismissed';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::Dismissed => 'Kein Handlungsbedarf',
            self::Resolved => 'Erledigt',
        };
    }
}
