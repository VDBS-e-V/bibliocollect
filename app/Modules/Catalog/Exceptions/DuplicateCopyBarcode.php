<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class DuplicateCopyBarcode extends RuntimeException
{
    public static function for(string $barcode): self
    {
        return new self("Der Barcode {$barcode} ist bereits einem anderen Exemplar zugeordnet.");
    }
}
