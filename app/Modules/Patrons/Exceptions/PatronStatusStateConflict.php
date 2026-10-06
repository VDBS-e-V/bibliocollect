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

    /** @param  list<string>  $reasons */
    public static function openCirculation(array $reasons): self
    {
        return new self('Das Ausleihkonto kann noch nicht ausscheiden: '.implode(' ', $reasons));
    }

    public static function futureEffectiveDate(): self
    {
        return new self('Das Austrittsdatum darf nicht in der Zukunft liegen.');
    }
}
