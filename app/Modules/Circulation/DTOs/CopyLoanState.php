<?php

declare(strict_types=1);

namespace App\Modules\Circulation\DTOs;

use Carbon\CarbonImmutable;

/** Ausleihzustand eines einzelnen Exemplars; öffentlich nur mit Rückgabedatum, nie mit Person. */
final readonly class CopyLoanState
{
    public function __construct(
        public bool $loaned,
        public ?CarbonImmutable $dueOn,
        public bool $held,
    ) {}
}
