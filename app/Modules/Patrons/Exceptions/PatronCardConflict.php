<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Exceptions;

use RuntimeException;

final class PatronCardConflict extends RuntimeException
{
    public static function unknown(): self
    {
        return new self('Diesen Ausweis gibt es nicht. Bitte die Nummer prüfen.');
    }

    public static function blocked(): self
    {
        return new self('Dieser Ausweis ist gesperrt und kann nicht verwendet werden.');
    }

    public static function alreadyAssigned(): self
    {
        return new self('Dieser Ausweis gehört schon einer anderen Person. Er muss zuerst gesperrt werden.');
    }

    public static function patronNotActive(): self
    {
        return new self('Das Ausleihkonto ist nicht aktiv.');
    }
}
