<?php

declare(strict_types=1);

namespace App\Modules\School\Exceptions;

use RuntimeException;

final class SchoolClassStateConflict extends RuntimeException
{
    public static function lastActiveClass(): self
    {
        return new self('Die letzte aktive Klasse eines aktiven Schuljahres kann nicht deaktiviert werden.');
    }
}
