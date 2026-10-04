<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Enums;

enum PatronStatus: string
{
    case Active = 'active';
    case Departed = 'departed';
    case Archived = 'archived';
}
