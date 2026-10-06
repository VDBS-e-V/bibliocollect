<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Exceptions\SchoolYearTransitionConflict;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Services\SchoolYearTransitionPlanner;
use App\Modules\School\Actions\ActivateSchoolYearAction;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Support\Facades\DB;

/**
 * Führt den Schuljahreswechsel in einer Transaktion aus: Ausleihkonten wechseln in die neue Klasse oder scheiden aus,
 * danach wird das neue Schuljahr aktiv. Scheitert ein Schritt, ändert sich nichts.
 */
final readonly class TransitionSchoolYearAction
{
    public function __construct(
        private BusinessClock $clock,
        private SchoolYearTransitionPlanner $planner,
        private DepartPatronAction $depart,
        private ActivateSchoolYearAction $activate,
        private AuditRecorder $audit,
    ) {}

    /**
     * @param  array<string, string>  $mapping  Quellklasse-ID => Zielklasse-ID, `depart` oder `keep`
     * @return array{promoted: int, departed: int, kept: int}
     *
     * @throws SchoolYearTransitionConflict
     */
    public function execute(SchoolYear $from, SchoolYear $to, array $mapping, User $actor): array
    {
        return DB::transaction(function () use ($from, $to, $mapping, $actor): array {
            $from = SchoolYear::query()->whereKey($from->getKey())->lockForUpdate()->firstOrFail();
            $to = SchoolYear::query()->whereKey($to->getKey())->lockForUpdate()->firstOrFail();

            if (! $from->is_active) {
                throw SchoolYearTransitionConflict::notActive();
            }

            if ($to->is_active || $to->getKey() === $from->getKey()) {
                throw SchoolYearTransitionConflict::targetNotDraft();
            }

            $targets = $to->classes()->where('is_active', true)->get()->keyBy(static fn (SchoolClass $class): string => (string) $class->getKey());
            $promote = [];
            $departing = [];
            $kept = 0;

            foreach ($from->classes()->where('is_active', true)->get() as $class) {
                $patrons = $this->planner->activePatrons($class);

                if ($patrons->isEmpty()) {
                    continue;
                }

                $choice = $mapping[(string) $class->getKey()] ?? '';

                if ($choice === '') {
                    throw SchoolYearTransitionConflict::incompleteMapping($class->name);
                }

                if ($choice === SchoolYearTransitionPlanner::KEEP) {
                    $kept += $patrons->count();

                    continue;
                }

                if ($choice === SchoolYearTransitionPlanner::DEPART) {
                    $departing[] = $patrons;

                    continue;
                }

                if (! $targets->has($choice)) {
                    throw SchoolYearTransitionConflict::invalidTarget($class->name);
                }

                $promote[$choice] = [...($promote[$choice] ?? []), ...$patrons->modelKeys()];
            }

            $blocked = [];

            foreach ($departing as $patrons) {
                foreach ($this->planner->blockedPatrons($patrons) as $entry) {
                    $blocked[] = $entry['library_number'].' ('.implode(' ', $entry['reasons']).')';
                }
            }

            if ($blocked !== []) {
                throw SchoolYearTransitionConflict::blockedDepartures($blocked);
            }

            $promoted = 0;

            foreach ($promote as $targetId => $patronIds) {
                $promoted += Patron::query()
                    ->whereIn('id', $patronIds)
                    ->update(['school_class_id' => $targetId]);
            }

            $departed = 0;
            $today = $this->clock->now()->toDateString();

            foreach ($departing as $patrons) {
                foreach ($patrons as $patron) {
                    $this->depart->execute($patron, $today, $actor);
                    $departed++;
                }
            }

            $this->activate->execute($to);

            $this->audit->record(
                'school.year.transitioned',
                "Schuljahreswechsel {$from->name} → {$to->name}: {$promoted} versetzt, {$departed} ausgeschieden, {$kept} unverändert.",
                $to,
                ['from_year_id' => (string) $from->getKey(), 'to_year_id' => (string) $to->getKey(), 'promoted' => $promoted, 'departed' => $departed, 'kept' => $kept],
                (int) $actor->getKey(),
            );

            return ['promoted' => $promoted, 'departed' => $departed, 'kept' => $kept];
        });
    }
}
