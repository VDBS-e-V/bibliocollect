<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Exceptions;

use RuntimeException;

final class PatronBlockStateConflict extends RuntimeException
{
    public static function alreadyBlocked(): self
    {
        return new self('Das Ausleihkonto ist bereits gesperrt.');
    }

    public static function notBlocked(): self
    {
        return new self('Für dieses Ausleihkonto ist keine aktive Sperre hinterlegt.');
    }

    public static function unavailableStatus(): self
    {
        return new self('Nur aktive Ausleihkonten können gesperrt werden.');
    }
}
