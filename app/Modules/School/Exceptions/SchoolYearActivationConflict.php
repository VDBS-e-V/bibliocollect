<?php

declare(strict_types=1);

namespace App\Modules\School\Exceptions;

use RuntimeException;

final class SchoolYearActivationConflict extends RuntimeException
{
    public static function withoutActiveClasses(): self
    {
        return new self('Das Schuljahr braucht mindestens eine aktive Klasse, bevor es aktiviert werden kann.');
    }

    /** @param list<int> $gradeLevels */
    public static function missingPromotedGrades(array $gradeLevels): self
    {
        return new self('Für den Schuljahreswechsel fehlen aktive Zielklassen in den Jahrgängen: '.implode(', ', $gradeLevels).'.');
    }
}
