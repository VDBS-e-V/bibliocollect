<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Exceptions;

use DomainException;

final class CirculationRuleViolation extends DomainException
{
    /** @param list<string> $reasons */
    public function __construct(public readonly array $reasons)
    {
        parent::__construct(implode(' ', $reasons));
    }

    public static function copyNotFound(string $barcode): self
    {
        return new self(["Kein Exemplar mit Barcode [{$barcode}] gefunden."]);
    }
}
