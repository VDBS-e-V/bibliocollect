<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum CatalogImportRowStatus: string
{
    case Pending = 'pending';
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Conflict = 'conflict';
}
