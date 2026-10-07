<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class CatalogShelfInUse extends RuntimeException
{
    public static function withCopies(string $code, int $copies): self
    {
        return new self("Das Regalbrett „{$code}“ ist noch bei {$copies} Exemplaren eingetragen und kann nicht gelöscht werden. Schalte es stattdessen aus.");
    }
}
