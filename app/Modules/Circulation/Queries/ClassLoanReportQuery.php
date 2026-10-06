<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Queries;

use App\Foundation\Support\BusinessClock;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Sammelliste offener Ausleihen je Klasse des aktiven Schuljahres, gedacht als Ausdruck für die Klassenleitungen.
 * Ausleihkonten ohne Klasse (Lehrkräfte, Mitarbeiter:innen) stehen am Ende unter „Ohne Klasse“.
 */
final class ClassLoanReportQuery
{
    public function __construct(private readonly BusinessClock $clock) {}

    /**
     * @return list<array{
     *     label: string,
     *     class_id: ?string,
     *     homeroom: ?string,
     *     rows: list<array{patron: string, library_number: string, title: string, barcode: string, due_on: string, days_overdue: int}>
     * }>
     */
    public function groups(bool $overdueOnly, ?string $classId = null): array
    {
        $today = $this->clock->now()->startOfDay();

        $loans = Loan::query()
            ->whereNull('returned_at')
            ->whereNotNull('patron_id')
            ->when($overdueOnly, static fn ($query) => $query->whereDate('due_on', '<', $today->toDateString()))
            ->whereHas('patron', static fn ($query) => $query->where('status', PatronStatus::Active->value))
            ->with(['patron.schoolClass', 'copy.edition.title'])
            ->orderBy('due_on')
            ->get();

        $activeYearId = SchoolYear::query()->where('is_active', true)->value('id');

        /** @var Collection<string, list<array<string, mixed>>> $byClass */
        $byClass = collect();
        $classNames = [];
        $homerooms = [];

        foreach ($loans as $loan) {
            $patron = $loan->patron;
            $class = $patron->schoolClass;
            $key = $class instanceof SchoolClass && $class->school_year_id === $activeYearId ? (string) $class->getKey() : '';

            if ($classId !== null && $classId !== '' && $key !== $classId) {
                continue;
            }

            $classNames[$key] = $key === '' ? 'Ohne Klasse' : $class->name;
            $homerooms[$key] = $key === '' ? null : $class->homeroom_teacher;
            // In der Geschäftszeitzone rechnen, sonst wird aus 10 Tagen durch den UTC-Versatz 9.
            $due = CarbonImmutable::parse($loan->due_on->toDateString(), $today->getTimezone());

            $byClass[$key] = [...($byClass[$key] ?? []), [
                'patron' => $patron->last_name.', '.$patron->first_name,
                'library_number' => $patron->library_number,
                'title' => $loan->copy->edition->title->preferred_title,
                'barcode' => $loan->copy->barcode,
                'due_on' => $due->format('d.m.Y'),
                'days_overdue' => $due->lessThan($today) ? (int) $due->diffInDays($today) : 0,
            ]];
        }

        $groups = [];

        foreach ($byClass as $key => $rows) {
            usort($rows, static fn (array $a, array $b): int => strcasecmp($a['patron'], $b['patron']) ?: strcmp($a['due_on'], $b['due_on']));
            $groups[] = ['label' => $classNames[$key], 'class_id' => $key === '' ? null : (string) $key, 'homeroom' => $homerooms[$key], 'rows' => $rows];
        }

        usort($groups, static function (array $a, array $b): int {
            if ($a['class_id'] === null || $b['class_id'] === null) {
                return ($a['class_id'] === null) <=> ($b['class_id'] === null);
            }

            return strnatcasecmp($a['label'], $b['label']);
        });

        return $groups;
    }
}
