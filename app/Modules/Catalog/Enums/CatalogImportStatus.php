<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

enum CatalogImportStatus: string
{
    case Uploaded = 'uploaded';
    case Previewed = 'previewed';
    case Ready = 'ready';
    case Blocked = 'blocked';
    case Committed = 'committed';
    case Failed = 'failed';
}
