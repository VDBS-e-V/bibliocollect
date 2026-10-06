<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Exceptions;

use RuntimeException;

final class SchoolYearTransitionConflict extends RuntimeException
{
    public static function notActive(): self
    {
        return new self('Der Wechsel geht nur vom aktiven Schuljahr aus.');
    }

    public static function targetNotDraft(): self
    {
        return new self('Das Zielschuljahr muss ein noch nicht aktives Schuljahr sein.');
    }

    public static function incompleteMapping(string $className): self
    {
        return new self("Für die Klasse {$className} fehlt die Zuordnung.");
    }

    public static function invalidTarget(string $className): self
    {
        return new self("Für die Klasse {$className} ist das Ziel ungültig.");
    }

    /** @param  list<string>  $details */
    public static function blockedDepartures(array $details): self
    {
        return new self('Diese Ausleihkonten können nicht ausscheiden: '.implode('; ', $details));
    }
}
