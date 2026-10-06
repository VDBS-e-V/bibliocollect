<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Modules\School\Services\SchoolCalendarService;
use Carbon\CarbonImmutable;

final readonly class LoanDueDateService
{
    public function __construct(private SchoolCalendarService $calendar) {}

    public function forCheckoutAt(CarbonImmutable $checkedOutAt, ?int $periodDays = null): CarbonImmutable
    {
        $loanPeriodDays = max(1, $periodDays ?? (int) config('circulation.default_loan_period_days', 14));
        $target = $checkedOutAt->startOfDay()->addDays($loanPeriodDays);

        if ($this->calendar->isOpeningDay($target)) {
            return $target;
        }

        return $this->calendar->nextOpeningDay($target);
    }

    /**
     * Neue Fälligkeit bei Verlängerung: ab heute, aber nie vor der bisherigen Fälligkeit,
     * damit eine frühe Verlängerung die Leihfrist nicht verkürzt.
     */
    public function forRenewalAt(CarbonImmutable $renewedAt, CarbonImmutable $currentDueOn, ?int $loanPeriodDays = null): CarbonImmutable
    {
        $configured = config('circulation.renewal_period_days');
        $periodDays = max(1, (int) ($configured ?? $loanPeriodDays ?? config('circulation.default_loan_period_days', 14)));
        $today = $renewedAt->startOfDay();
        $current = $currentDueOn->startOfDay();
        $target = ($current->greaterThan($today) ? $current : $today)->addDays($periodDays);

        if ($this->calendar->isOpeningDay($target)) {
            return $target;
        }

        return $this->calendar->nextOpeningDay($target);
    }

    /** Letzter Abholtag einer bereitgelegten Vormerkung; fällt er auf einen Schließtag, zählt der nächste Öffnungstag. */
    public function forPickupDeadline(CarbonImmutable $readyAt): CarbonImmutable
    {
        $days = max(1, (int) config('circulation.reservation_pickup_days', 7));
        $target = $readyAt->startOfDay()->addDays($days);

        if ($this->calendar->isOpeningDay($target)) {
            return $target;
        }

        return $this->calendar->nextOpeningDay($target);
    }
}
