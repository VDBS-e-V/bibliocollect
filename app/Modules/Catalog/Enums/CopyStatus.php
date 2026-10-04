<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum CopyStatus: string
{
    case Active = 'active';
    case Damaged = 'damaged';
    case Lost = 'lost';
    case Withdrawn = 'withdrawn';
}
