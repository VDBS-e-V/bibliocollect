<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Exceptions;

use DomainException;

final class LoanStateConflict extends DomainException
{
    public static function alreadyReturned(): self
    {
        return new self('Diese Ausleihe wurde bereits zurückgegeben.');
    }
}
