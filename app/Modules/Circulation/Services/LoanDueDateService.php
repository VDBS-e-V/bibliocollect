<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Modules\School\Services\SchoolCalendarService;
use Carbon\CarbonImmutable;

final readonly class LoanDueDateService
{
    public function __construct(private SchoolCalendarService $calendar) {}

    public function forCheckoutAt(CarbonImmutable $checkedOutAt): CarbonImmutable
    {
        $loanPeriodDays = max(1, (int) config('circulation.default_loan_period_days', 14));
        $target = $checkedOutAt->startOfDay()->addDays($loanPeriodDays);

        if ($this->calendar->isOpeningDay($target)) {
            return $target;
        }

        return $this->calendar->nextOpeningDay($target);
    }
}
