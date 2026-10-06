<?php

declare(strict_types=1);

namespace App\Modules\Circulation\DTOs;

use Carbon\CarbonImmutable;

/**
 * Öffentlich unbedenkliche Verfügbarkeit: nur Zählwerte und das früheste Rückgabedatum, nie Barcodes oder Personen.
 */
final readonly class CopyAvailability
{
    public function __construct(
        public int $activeCopies,
        public int $loanedCopies,
        public ?CarbonImmutable $earliestDueOn,
        public int $heldCopies = 0,
        public int $waitingReservations = 0,
    ) {}

    public static function none(): self
    {
        return new self(0, 0, null);
    }

    public function availableCopies(): int
    {
        return max(0, $this->activeCopies - $this->loanedCopies - $this->heldCopies);
    }

    /** Für eine Vormerkung zurückgelegte Exemplare sind weder verfügbar noch ausgeliehen. */
    public function hasHeldCopies(): bool
    {
        return $this->heldCopies > 0;
    }

    public function hasActiveCopies(): bool
    {
        return $this->activeCopies > 0;
    }

    public function isAvailable(): bool
    {
        return $this->availableCopies() > 0;
    }
}
