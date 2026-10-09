<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Import;

use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\SchoolClass;
use App\Modules\School\Models\SchoolYear;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Prüft die gelesenen Zeilen einer Klassenliste gegen Schule und Bestand und legt fest, was passieren würde. Schreibt nichts.
 * Alle Zeilen gehören zu der Klasse, die beim Import gewählt wurde, und sind Schüler:innen.
 *
 * Status je Zeile: `new` (wird angelegt), `update` (Person ist vorhanden, E-Mail oder Klasse ändern sich; nur wenn Aktualisieren
 * gewählt ist), `existing` (gleiche Person ist schon vorhanden, nichts zu tun), `duplicate` (in der Datei doppelt, wird übersprungen)
 * oder `error` (blockiert den Import). Ohne gewählte Klasse steht die Klasse je Zeile in der Spalte `klasse`.
 */
final class PatronImportPlanner
{
    /**
     * @param  list<array{line: int, values: array<string, string>}>  $rows
     * @return array{rows: list<array<string, mixed>>, counts: array{new: int, update: int, existing: int, duplicate: int, error: int}, year: ?SchoolYear, class: ?SchoolClass}
     */
    public function plan(array $rows, ?string $classId, bool $update = false): array
    {
        $year = SchoolYear::query()->where('is_active', true)->first();
        $fromFile = $classId === null || $classId === '';

        $class = $year !== null && ! $fromFile
            ? SchoolClass::query()->where('school_year_id', $year->getKey())->where('is_active', true)->find($classId)
            : null;

        $classesByName = $year !== null
            ? SchoolClass::query()->where('school_year_id', $year->getKey())->where('is_active', true)->get()->keyBy(fn (SchoolClass $schoolClass): string => $this->key($schoolClass->name))
            : collect();

        $classNames = SchoolClass::query()->pluck('name', 'id')->all();

        $existingPeople = Patron::query()->get()
            ->mapWithKeys(fn (Patron $patron): array => [$this->personKey($patron->first_name, $patron->last_name, $patron->birth_date->toDateString()) => $patron]);

        $seenPeople = [];
        $planned = [];
        $counts = ['new' => 0, 'update' => 0, 'existing' => 0, 'duplicate' => 0, 'error' => 0];

        foreach ($rows as $row) {
            $values = $row['values'];
            $messages = [];

            $rowClass = $class;

            if ($fromFile) {
                $name = trim($values['klasse'] ?? '');
                $rowClass = $name !== '' ? $classesByName->get($this->key($name)) : null;

                if ($year === null) {
                    $messages[] = 'Es ist kein Schuljahr aktiv, daher kann keine Klasse zugeordnet werden.';
                } elseif ($name === '') {
                    $messages[] = 'In der Spalte klasse fehlt die Klasse (oder wähle beim Hochladen eine Klasse).';
                } elseif (! $rowClass instanceof SchoolClass) {
                    $messages[] = 'Die Klasse „'.$name.'“ gibt es im aktiven Schuljahr '.$year->name.' nicht.';
                }
            } elseif (! $rowClass instanceof SchoolClass) {
                $messages[] = $year === null
                    ? 'Es ist kein Schuljahr aktiv, daher kann keine Klasse zugeordnet werden.'
                    : 'Die gewählte Klasse gibt es im aktiven Schuljahr '.$year->name.' nicht (mehr).';
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

            $status = 'new';
            $changes = [];
            $patronId = null;

            if ($messages !== []) {
                $status = 'error';
            } elseif ($birth !== null) {
                $personKey = $this->personKey($values['vorname'], $values['nachname'], $birth);

                if (isset($existingPeople[$personKey])) {
                    $status = 'existing';
                    $patron = $existingPeople[$personKey];

                    if ($update) {
                        if ($patron->status !== PatronStatus::Active) {
                            $changes[] = 'Ausgeschieden oder archiviert: wird nicht verändert.';
                        } else {
                            if ($values['email'] !== '' && mb_strtolower($values['email']) !== mb_strtolower((string) $patron->email)) {
                                $changes[] = 'E-Mail: '.($patron->email ?: '—').' → '.$values['email'];
                            }

                            if ($rowClass instanceof SchoolClass && (string) $patron->school_class_id !== (string) $rowClass->getKey()) {
                                $changes[] = 'Klasse: '.($classNames[(string) $patron->school_class_id] ?? '—').' → '.$rowClass->name;
                            }

                            if ($changes !== []) {
                                $status = 'update';
                                $patronId = (string) $patron->getKey();
                            }
                        }
                    }
                } elseif (isset($seenPeople[$personKey])) {
                    $status = 'duplicate';
                } else {
                    $seenPeople[$personKey] = true;
                }
            }

            $counts[$status]++;

            $planned[] = [
                'line' => $row['line'],
                'status' => $status,
                'messages' => $messages,
                'kind' => PatronKind::Student,
                'first_name' => $values['vorname'],
                'last_name' => $values['nachname'],
                'birth_date' => $birth,
                'email' => $values['email'] !== '' ? $values['email'] : null,
                'school_class_id' => $rowClass instanceof SchoolClass ? (string) $rowClass->getKey() : null,
                'class_name' => $rowClass instanceof SchoolClass ? $rowClass->name : null,
                'library_number' => null,
                'changes' => $changes,
                'patron_id' => $patronId,
            ];
        }

        return ['rows' => $planned, 'counts' => $counts, 'year' => $year, 'class' => $class];
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
