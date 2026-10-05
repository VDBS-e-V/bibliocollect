<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\DTOs\CatalogImportSourceRecord;

interface CatalogImportSource
{
    public function format(): string;

    /** @return list<string> */
    public function headers(): array;

    /** @return iterable<CatalogImportSourceRecord> */
    public function records(): iterable;
}
