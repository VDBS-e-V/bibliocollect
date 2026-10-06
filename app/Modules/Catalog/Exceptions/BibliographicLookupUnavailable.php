<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;
use Throwable;

final class BibliographicLookupUnavailable extends RuntimeException
{
    public static function because(string $reason, ?Throwable $previous = null): self
    {
        return new self('Bibliografische Abfrage nicht verfügbar: '.$reason, 0, $previous);
    }
}
