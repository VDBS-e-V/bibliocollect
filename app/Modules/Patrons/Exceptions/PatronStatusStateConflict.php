<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Exceptions;

use RuntimeException;

final class PatronStatusStateConflict extends RuntimeException
{
    public static function cannotDepart(): self
    {
        return new self('Nur aktive Ausleihkonten können als ausgeschieden markiert werden.');
    }

    public static function futureEffectiveDate(): self
    {
        return new self('Das Austrittsdatum darf nicht in der Zukunft liegen.');
    }
}
