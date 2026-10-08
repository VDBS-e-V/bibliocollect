<?php

declare(strict_types=1);

namespace App\Modules\School\Actions;

use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;

/**
 * Legt die Klassen der VDBS-Schule für ein Schuljahr an: Grundschule 1.1 bis 6.3, Mittelstufe 7.1 bis 10.5 mit 9.6, 10.6 und WiKo,
 * Oberstufe 11.1 bis 11.4, 12 und 13. Vorhandene Klassen bleiben unverändert; nur Fehlendes kommt dazu.
 */
final class CreateStandardClassesAction
{
    /**
     * Name und Jahrgang je Klasse. Die Willkommensklasse (WiKo) steht ohne eigenen Jahrgang in der Mittelstufe und zählt als Jahrgang 7.
     *
     * @return list<array{name: string, grade: int}>
     */
    public static function definitions(): array
    {
        $classes = [];

        foreach (range(1, 6) as $grade) {
            foreach (range(1, 3) as $parallel) {
                $classes[] = ['name' => $grade.'.'.$parallel, 'grade' => $grade];
            }
        }

        foreach (range(7, 10) as $grade) {
            foreach (range(1, 5) as $parallel) {
                $classes[] = ['name' => $grade.'.'.$parallel, 'grade' => $grade];
            }
        }

        array_push($classes, ['name' => '9.6', 'grade' => 9], ['name' => '10.6', 'grade' => 10], ['name' => 'WiKo', 'grade' => 7]);

        foreach (range(1, 4) as $parallel) {
            $classes[] = ['name' => '11.'.$parallel, 'grade' => 11];
        }

        array_push($classes, ['name' => '12', 'grade' => 12], ['name' => '13', 'grade' => 13]);

        return $classes;
    }

    /** @return int Anzahl der neu angelegten Klassen */
    public function execute(SchoolYear $schoolYear): int
    {
        $existing = SchoolClass::query()->where('school_year_id', $schoolYear->getKey())->pluck('name')->map(static fn (string $name): string => mb_strtolower(trim($name)))->all();
        $created = 0;

        foreach (self::definitions() as ['name' => $name, 'grade' => $grade]) {
            if (in_array(mb_strtolower($name), $existing, true)) {
                continue;
            }

            SchoolClass::query()->create(['school_year_id' => $schoolYear->getKey(), 'name' => $name, 'grade_level' => $grade, 'is_active' => true]);
            $created++;
        }

        return $created;
    }
}
