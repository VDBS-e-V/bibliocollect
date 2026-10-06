<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Import;

use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Prüft die gelesenen Zeilen gegen Schule und Bestand und legt fest, was passieren würde. Schreibt nichts.
 *
 * Status je Zeile: `new` (wird angelegt), `existing` (gleiche Person ist schon vorhanden, wird übersprungen),
 * `duplicate` (in der Datei doppelt, wird übersprungen) oder `error` (blockiert den Import).
 */
final class PatronImportPlanner
{
    /**
     * @param  list<array{line: int, values: array<string, string>}>  $rows
     * @return array{rows: list<array<string, mixed>>, counts: array{new: int, existing: int, duplicate: int, error: int}, year: ?SchoolYear}
     */
    public function plan(array $rows): array
    {
        $year = SchoolYear::query()->where('is_active', true)->first();

        $classes = $year !== null
            ? SchoolClass::query()->where('school_year_id', $year->getKey())->where('is_active', true)->get()
                ->keyBy(fn (SchoolClass $class): string => $this->key($class->name))
            : collect();

        $existingPeople = Patron::query()->get(['first_name', 'last_name', 'birth_date'])
            ->mapWithKeys(fn (Patron $patron): array => [$this->personKey($patron->first_name, $patron->last_name, $patron->birth_date->toDateString()) => true]);

        $existingNumbers = Patron::query()->pluck('library_number')->mapWithKeys(fn (string $number): array => [mb_strtolower($number) => true])->all();

        $seenPeople = [];
        $seenNumbers = [];
        $planned = [];
        $counts = ['new' => 0, 'existing' => 0, 'duplicate' => 0, 'error' => 0];

        foreach ($rows as $row) {
            $values = $row['values'];
            $messages = [];

            $kind = $this->kind($values['art']);

            if ($kind === null) {
                $messages[] = 'Unbekannte Art „'.$values['art'].'“ (erlaubt: Schüler:in, Lehrkraft, Mitarbeiter:in).';
            }

            if ($values['vorname'] === '' || $values['nachname'] === '') {
                $messages[] = 'Vor- und Nachname werden benötigt.';
            }

            $birth = $this->date($values['geburtsdatum']);

            if ($birth === null) {
                $messages[] = 'Das Geburtsdatum ist ungültig (erlaubt: TT.MM.JJJJ oder JJJJ-MM-TT).';
            }

            if ($values['email'] !== '' && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
                $messages[] = 'Die E-Mail-Adresse ist ungültig.';
            }

            $classId = null;
            $className = null;

            if ($kind === PatronKind::Student) {
                if ($values['klasse'] === '') {
                    $messages[] = 'Für Schüler:innen wird eine Klasse benötigt.';
                } elseif ($year === null) {
                    $messages[] = 'Es ist kein Schuljahr aktiv, daher kann keine Klasse zugeordnet werden.';
                } else {
                    $class = $classes->get($this->key($values['klasse']));

                    if (! $class instanceof SchoolClass) {
                        $messages[] = 'Die Klasse „'.$values['klasse'].'“ gibt es im aktiven Schuljahr '.$year->name.' nicht.';
                    } else {
                        $classId = (string) $class->getKey();
                        $className = $class->name;
                    }
                }
            }

            $number = $values['bibliotheksnummer'];

            if ($number !== '') {
                if (mb_strlen($number) > 80) {
                    $messages[] = 'Die Bibliotheksnummer ist zu lang.';
                } elseif (isset($existingNumbers[mb_strtolower($number)]) || isset($seenNumbers[mb_strtolower($number)])) {
                    $messages[] = 'Die Bibliotheksnummer „'.$number.'“ ist schon vergeben.';
                }
            }

            $status = 'new';

            if ($messages !== []) {
                $status = 'error';
            } elseif ($birth !== null) {
                $personKey = $this->personKey($values['vorname'], $values['nachname'], $birth);

                if (isset($existingPeople[$personKey])) {
                    $status = 'existing';
                } elseif (isset($seenPeople[$personKey])) {
                    $status = 'duplicate';
                } else {
                    $seenPeople[$personKey] = true;

                    if ($number !== '') {
                        $seenNumbers[mb_strtolower($number)] = true;
                    }
                }
            }

            $counts[$status]++;

            $planned[] = [
                'line' => $row['line'],
                'status' => $status,
                'messages' => $messages,
                'kind' => $kind,
                'first_name' => $values['vorname'],
                'last_name' => $values['nachname'],
                'birth_date' => $birth,
                'email' => $values['email'] !== '' ? $values['email'] : null,
                'school_class_id' => $classId,
                'class_name' => $className,
                'library_number' => $number !== '' ? $number : null,
            ];
        }

        return ['rows' => $planned, 'counts' => $counts, 'year' => $year];
    }

    private function kind(string $value): ?PatronKind
    {
        $value = $this->key($value);

        // Ohne Angabe gilt Schüler:in. Sonst reicht der Wortanfang („Schüler:in“, „Lehrer“, „Mitarbeiterin“).
        return match (true) {
            $value === '', $value === 's', $value === 'student', str_starts_with($value, 'schueler') => PatronKind::Student,
            $value === 'l', $value === 'teacher', str_starts_with($value, 'lehr') => PatronKind::Teacher,
            $value === 'm', $value === 'employee', str_starts_with($value, 'mitarbeiter') => PatronKind::Employee,
            default => null,
        };
    }

    private function date(string $value): ?string
    {
        foreach (['d.m.Y', 'Y-m-d'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat('!'.$format, $value);
            } catch (InvalidFormatException) {
                continue;
            }

            // Der Rückvergleich fängt Überläufe wie den 31.02. ab, die PHP stillschweigend umrechnet.
            if ($date->format($format) === $value
                && $date->year >= 1900 && $date->lessThanOrEqualTo(CarbonImmutable::today())) {
                return $date->toDateString();
            }
        }

        return null;
    }

    private function key(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);

        return preg_replace('/[^a-z0-9]/', '', $value) ?? '';
    }

    private function personKey(string $first, string $last, string $birth): string
    {
        return $this->key($first).'|'.$this->key($last).'|'.$birth;
    }
}
