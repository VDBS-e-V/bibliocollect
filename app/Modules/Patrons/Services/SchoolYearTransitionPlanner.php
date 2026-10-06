<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Services;

use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Illuminate\Database\Eloquent\Collection;

/**
 * Bereitet den Schuljahreswechsel vor: Welche Klasse des laufenden Jahres geht in welche Klasse des neuen Jahres,
 * wer scheidet aus und was hindert das. Schreibt nichts.
 */
final class SchoolYearTransitionPlanner
{
    public const DEPART = 'depart';

    public const KEEP = 'keep';

    public function __construct(private readonly PatronDepartureGuards $guards) {}

    /**
     * @return list<array{
     *     class: SchoolClass,
     *     patron_count: int,
     *     suggestion: string,
     *     blocked: list<array{library_number: string, reasons: list<string>}>
     * }>
     */
    public function plan(SchoolYear $from, SchoolYear $to): array
    {
        $targets = $to->classes()->where('is_active', true)->orderBy('grade_level')->orderBy('name')->get();
        $rows = [];

        $sourceClasses = $from->classes()->where('is_active', true)->orderBy('grade_level')->orderBy('name')->get();

        foreach ($sourceClasses as $class) {
            $patrons = $this->activePatrons($class);
            $suggestion = $this->suggest($class, $targets);

            $blocked = [];

            if ($suggestion === self::DEPART) {
                $blocked = $this->blockedPatrons($patrons);
            }

            $rows[] = [
                'class' => $class,
                'patron_count' => $patrons->count(),
                'suggestion' => $suggestion,
                'blocked' => $blocked,
            ];
        }

        return $rows;
    }

    /** @return Collection<int, Patron> */
    public function activePatrons(SchoolClass $class): Collection
    {
        return Patron::query()
            ->where('school_class_id', $class->getKey())
            ->where('status', PatronStatus::Active->value)
            ->orderBy('library_number')
            ->get();
    }

    /**
     * @param  Collection<int, Patron>  $patrons
     * @return list<array{library_number: string, reasons: list<string>}>
     */
    public function blockedPatrons(Collection $patrons): array
    {
        $blocked = [];

        foreach ($patrons as $patron) {
            $reasons = $this->guards->blockReasons($patron);

            if ($reasons !== []) {
                $blocked[] = ['library_number' => $patron->library_number, 'reasons' => $reasons];
            }
        }

        return $blocked;
    }

    /**
     * Vorschlag: Klasse 13 scheidet aus; sonst die Klasse mit gleichem Namen, aber um eins erhöhtem Jahrgang
     * („5a“ → „6a“), oder die einzige Klasse des nächsten Jahrgangs. Sonst bleibt die Zuordnung offen.
     *
     * @param  Collection<int, SchoolClass>  $targets
     */
    private function suggest(SchoolClass $class, Collection $targets): string
    {
        if ($class->grade_level >= 13) {
            return self::DEPART;
        }

        $nextGrade = $class->grade_level + 1;

        if (preg_match('/^\s*'.$class->grade_level.'(?!\d)(.*)$/u', $class->name, $match) === 1) {
            $expected = mb_strtolower($nextGrade.$match[1]);
            $named = $targets->first(static fn (SchoolClass $target): bool => $target->grade_level === $nextGrade
                && mb_strtolower(trim($target->name)) === trim($expected));

            if ($named instanceof SchoolClass) {
                return (string) $named->getKey();
            }
        }

        $sameGrade = $targets->where('grade_level', $nextGrade);

        return $sameGrade->count() === 1 ? (string) $sameGrade->first()->getKey() : '';
    }
}
