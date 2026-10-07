<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Actions\PlaceReservationAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\LibraryClosure;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Beispieldaten für den Betrieb: Klassen mit Klassenleitung, Schüler:innen, Lehrkräfte, Schließtage und Ausleihen
 * auf vorhandenen Exemplaren (überfällig, bald fällig, laufend) samt Vormerkungen. Medien werden nicht angelegt.
 *
 * Aufruf: php artisan db:seed --class=SampleOperationsSeeder
 * Wiederholbar: Sind die Beispielkonten (Nummern aus number()) schon da, passiert nichts.
 */
final class SampleOperationsSeeder extends Seeder
{
    private const FIRST_NAMES = [
        'Lena', 'Mia', 'Emma', 'Hannah', 'Sofia', 'Anna', 'Lea', 'Marie', 'Clara', 'Ida', 'Ella', 'Nora',
        'Leon', 'Ben', 'Paul', 'Jonas', 'Elias', 'Finn', 'Noah', 'Luca', 'Felix', 'Theo', 'Emil', 'Anton',
    ];

    private const LAST_NAMES = [
        'Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber', 'Meyer', 'Wagner', 'Becker', 'Schulz', 'Hoffmann',
        'Koch', 'Richter', 'Klein', 'Wolf', 'Schröder', 'Neumann', 'Schwarz', 'Zimmermann', 'Braun', 'Krüger',
        'Hofmann', 'Hartmann', 'Lange', 'Schmitt', 'Werner', 'Krause', 'Meier', 'Lehmann', 'Köhler', 'Herrmann',
    ];

    private const TEACHERS = ['Frau Albrecht', 'Herr Brandt', 'Frau Conrad', 'Herr Dietrich', 'Frau Engel', 'Herr Falk', 'Frau Grünwald', 'Herr Heinze'];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Beispieldaten werden in der Produktionsumgebung nicht angelegt.');

            return;
        }

        if (Patron::query()->where('library_number', $this->number(0))->exists()) {
            $this->command?->info('Die Beispieldaten sind bereits vorhanden.');

            return;
        }

        $staff = User::query()->where('email', 'staff@demo.bibliocollect.test')->first()
            ?? User::query()->orderBy('id')->first();

        if (! $staff instanceof User) {
            $this->command?->warn('Es gibt kein Benutzerkonto für die Ausleihen. Bitte zuerst den DemoSeeder ausführen.');

            return;
        }

        DB::transaction(function () use ($staff): void {
            $year = SchoolYear::query()->where('is_active', true)->first();

            if (! $year instanceof SchoolYear) {
                $this->command?->warn('Es ist kein Schuljahr aktiv. Bitte zuerst den DemoSeeder ausführen.');

                return;
            }

            $classes = $this->seedClasses($year);
            $this->seedClosures();
            $patrons = $this->seedPatrons($classes);
            $this->seedLoansAndReservations($patrons, $staff);

            $this->command?->info(count($patrons).' Beispielkonten, Klassen mit Klassenleitung und Ausleihen angelegt.');
        });
    }

    /** @return list<SchoolClass> */
    private function seedClasses(SchoolYear $year): array
    {
        $names = ['5a' => 5, '5b' => 5, '6a' => 6, '6b' => 6, '7a' => 7, '7b' => 7, '8a' => 8, '9a' => 9, '10a' => 10];
        $classes = [];
        $index = 0;

        foreach ($names as $name => $grade) {
            $classes[] = SchoolClass::query()->updateOrCreate(
                ['school_year_id' => $year->getKey(), 'name' => $name],
                ['grade_level' => $grade, 'is_active' => true, 'homeroom_teacher' => self::TEACHERS[$index % count(self::TEACHERS)]],
            );
            $index++;
        }

        // Das nächste Schuljahr bekommt die Folgeklassen, damit sich der Schuljahreswechsel ausprobieren lässt.
        $next = SchoolYear::query()->where('is_active', false)->where('starts_on', '>', $year->starts_on)->orderBy('starts_on')->first();

        if ($next instanceof SchoolYear) {
            foreach (['5a' => 5, '5b' => 5, '6a' => 6, '6b' => 6, '7a' => 7, '7b' => 7, '8a' => 8, '8b' => 8, '9a' => 9, '10a' => 10, '11a' => 11] as $name => $grade) {
                SchoolClass::query()->firstOrCreate(
                    ['school_year_id' => $next->getKey(), 'name' => $name],
                    ['grade_level' => $grade, 'is_active' => true],
                );
            }
        }

        return $classes;
    }

    private function seedClosures(): void
    {
        $year = CarbonImmutable::now()->year;

        $ranges = [
            ["{$year}-10-26", "{$year}-10-30", 'Herbstferien'],
            ["{$year}-12-21", ($year + 1).'-01-01', 'Weihnachtsferien'],
        ];

        foreach ($ranges as [$from, $to, $reason]) {
            for ($day = CarbonImmutable::parse($from); $day->lessThanOrEqualTo(CarbonImmutable::parse($to)); $day = $day->addDay()) {
                if (! LibraryClosure::query()->whereDate('date', $day->toDateString())->exists()) {
                    LibraryClosure::query()->create(['date' => $day->toDateString(), 'reason' => $reason]);
                }
            }
        }
    }

    /** Feste, nicht fortlaufende sechsstellige Nummer für die Beispielkonten (wie im Echtbetrieb ohne Kennung). */
    private function number(int $index): string
    {
        return (string) (100003 + (($index * 7919 + 13) % 800000));
    }

    /**
     * @param  list<SchoolClass>  $classes
     * @return list<Patron>
     */
    private function seedPatrons(array $classes): array
    {
        $patrons = [];
        $index = 0;

        foreach ($classes as $classIndex => $class) {
            for ($i = 0; $i < 5; $i++) {
                $seed = $classIndex * 5 + $i;
                $first = self::FIRST_NAMES[$seed % count(self::FIRST_NAMES)];
                $last = self::LAST_NAMES[($seed * 7 + 3) % count(self::LAST_NAMES)];
                $birthYear = CarbonImmutable::now()->year - ($class->grade_level + 6);

                $patrons[] = Patron::query()->create([
                    'library_number' => $this->number($index),
                    'kind' => PatronKind::Student,
                    'status' => PatronStatus::Active,
                    'first_name' => $first,
                    'last_name' => $last,
                    'birth_date' => sprintf('%d-%02d-%02d', $birthYear, ($seed % 12) + 1, ($seed % 27) + 1),
                    'school_class_id' => $class->getKey(),
                ]);

                $index++;
            }
        }

        foreach (array_slice(self::TEACHERS, 0, 3) as $index => $name) {
            $patrons[] = Patron::query()->create([
                'library_number' => $this->number(1000 + $index),
                'kind' => PatronKind::Teacher,
                'status' => PatronStatus::Active,
                'first_name' => explode(' ', $name)[0] === 'Frau' ? 'Petra' : 'Thomas',
                'last_name' => explode(' ', $name)[1],
                'birth_date' => (1970 + $index * 4).'-03-1'.$index,
            ]);
        }

        return $patrons;
    }

    /** @param  list<Patron>  $patrons */
    private function seedLoansAndReservations(array $patrons, User $staff): void
    {
        $students = array_values(array_filter($patrons, static fn (Patron $patron): bool => $patron->kind === PatronKind::Student));

        // Exemplare ohne Altersgrenze, die aktuell nicht ausgeliehen sind.
        $copies = Copy::query()
            ->where('status', CopyStatus::Active->value)
            ->whereDoesntHave('edition', static fn ($query) => $query->whereNotNull('minimum_age'))
            ->whereNotIn('id', Loan::query()->whereNull('returned_at')->select('copy_id'))
            ->with('edition')
            ->orderBy('id')
            ->limit(60)
            ->get();

        $today = CarbonImmutable::now()->startOfDay();

        // (Tage bis zur Fälligkeit, Tage seit Ausleihe)
        $plan = [
            [-18, 32], [-12, 26], [-9, 23], [-5, 19], [-3, 17], [-1, 15],
            [1, 13], [2, 12], [2, 12], [5, 9], [7, 7], [9, 5], [11, 3], [12, 2], [13, 1], [14, 0],
        ];

        $loans = [];

        foreach ($plan as $index => [$dueIn, $daysAgo]) {
            $copy = $copies[$index] ?? null;
            $patron = $students[($index * 3) % count($students)] ?? null;

            if (! $copy instanceof Copy || ! $patron instanceof Patron) {
                break;
            }

            $loans[] = Loan::query()->create([
                'patron_id' => $patron->getKey(),
                'copy_id' => $copy->getKey(),
                'checked_out_at' => $today->subDays($daysAgo)->setTime(10, 15),
                'due_on' => $today->addDays($dueIn)->toDateString(),
                'checked_out_by_user_id' => $staff->getKey(),
            ]);
        }

        // Vormerkungen auf bereits ausgeliehene Titel; die erste wird durch eine Rückgabe abholbereit.
        $place = app(PlaceReservationAction::class);
        $return = app(ReturnLoanAction::class);

        foreach ([0, 1, 2] as $slot) {
            $loan = $loans[6 + $slot] ?? null;
            $waiter = $students[(40 + $slot) % count($students)] ?? null;

            if ($loan instanceof Loan && $waiter instanceof Patron) {
                $loan->load('copy');
                $place->execute($waiter, $loan->copy->barcode, $staff);
            }
        }

        if (($loans[6] ?? null) instanceof Loan) {
            $return->execute($loans[6], $staff);
        }
    }
}
