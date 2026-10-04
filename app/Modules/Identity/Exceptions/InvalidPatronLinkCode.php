<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class InvalidPatronLinkCode extends RuntimeException
{
    public static function invalidOrExpired(): self
    {
        return new self('Der Verknüpfungscode ist ungültig, abgelaufen oder wurde bereits verwendet.');
    }

    public static function patronUnavailable(): self
    {
        return new self('Das zugehörige Ausleihkonto kann derzeit nicht verknüpft werden.');
    }
}
