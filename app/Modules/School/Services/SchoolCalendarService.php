<?php

declare(strict_types=1);

namespace App\Modules\School\Services;

use App\Foundation\Support\BusinessClock;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\LibraryOpeningHour;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use RuntimeException;

final readonly class SchoolCalendarService
{
    public function __construct(private BusinessClock $clock) {}

    public function isOpeningDay(DateTimeInterface $date): bool
    {
        $day = CarbonImmutable::instance($date)->setTimezone($this->clock->timezone())->startOfDay();

        $isClosed = LibraryClosure::query()
            ->whereDate('date', $day->toDateString())
            ->exists();

        if ($isClosed) {
            return false;
        }

        return LibraryOpeningHour::query()
            ->where('day_of_week', $day->dayOfWeekIso)
            ->where('is_open', true)
            ->exists();
    }

    public function nextOpeningDay(DateTimeInterface $after, int $maxDays = 60): CarbonImmutable
    {
        $candidate = CarbonImmutable::instance($after)
            ->setTimezone($this->clock->timezone())
            ->startOfDay();

        for ($offset = 1; $offset <= $maxDays; $offset++) {
            $candidate = $candidate->addDay();

            if ($this->isOpeningDay($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException("No library opening day found within {$maxDays} days.");
    }

    public function openingDaysBetween(DateTimeInterface $start, DateTimeInterface $end): int
    {
        $cursor = CarbonImmutable::instance($start)
            ->setTimezone($this->clock->timezone())
            ->startOfDay();
        $last = CarbonImmutable::instance($end)
            ->setTimezone($this->clock->timezone())
            ->startOfDay();

        if ($cursor->greaterThan($last)) {
            return 0;
        }

        $count = 0;

        while ($cursor->lessThanOrEqualTo($last)) {
            if ($this->isOpeningDay($cursor)) {
                $count++;
            }

            $cursor = $cursor->addDay();
        }

        return $count;
    }
}
