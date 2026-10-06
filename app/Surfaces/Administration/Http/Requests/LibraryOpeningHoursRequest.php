<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

final class LibraryOpeningHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = [];

        foreach (range(1, 7) as $day) {
            $rules["days.{$day}.is_open"] = ['sometimes', 'boolean'];
            $rules["days.{$day}.ranges"] = ['nullable', 'array', 'max:6'];
            $rules["days.{$day}.ranges.*.from"] = ['nullable', 'date_format:H:i'];
            $rules["days.{$day}.ranges.*.to"] = ['nullable', 'date_format:H:i'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $open = 0;

            foreach (range(1, 7) as $day) {
                if (! $this->boolean("days.{$day}.is_open")) {
                    continue;
                }

                $open++;
                $ranges = $this->rangesFor($day);

                if ($ranges === []) {
                    $validator->errors()->add("days.{$day}.ranges", 'Für geöffnete Tage wird mindestens ein Zeitraum mit Beginn und Ende benötigt.');

                    continue;
                }

                $previousEnd = null;

                foreach ($ranges as $range) {
                    if ($range['from'] === '' || $range['to'] === '') {
                        $validator->errors()->add("days.{$day}.ranges", 'Jeder Zeitraum braucht Beginn und Ende.');
                    } elseif ($range['to'] <= $range['from']) {
                        $validator->errors()->add("days.{$day}.ranges", 'Das Ende muss nach dem Beginn liegen.');
                    } elseif ($previousEnd !== null && $range['from'] < $previousEnd) {
                        $validator->errors()->add("days.{$day}.ranges", 'Die Zeiträume eines Tages dürfen sich nicht überschneiden.');
                    }

                    $previousEnd = $range['to'];
                }
            }

            if ($open === 0) {
                $validator->errors()->add('days', 'Mindestens ein Wochentag muss geöffnet sein.');
            }
        });
    }

    /** @return array<int, array{is_open: bool, ranges: list<array{from: string, to: string}>}> */
    public function days(): array
    {
        $days = [];

        foreach (range(1, 7) as $day) {
            $open = $this->boolean("days.{$day}.is_open");
            $days[$day] = ['is_open' => $open, 'ranges' => $open ? $this->rangesFor($day) : []];
        }

        return $days;
    }

    /**
     * Gefüllte Zeiträume eines Tages nach Beginn sortiert. Leere Zeilen (Reserveplätze im Formular) fallen weg;
     * eine halb ausgefüllte Zeile zählt mit und wird bei der Prüfung beanstandet.
     *
     * @return list<array{from: string, to: string}>
     */
    private function rangesFor(int $day): array
    {
        $ranges = [];
        $input = $this->input("days.{$day}.ranges", []);

        foreach (is_array($input) ? $input : [] as $range) {
            $from = is_array($range) && is_string($range['from'] ?? null) ? $range['from'] : '';
            $to = is_array($range) && is_string($range['to'] ?? null) ? $range['to'] : '';

            if ($from === '' && $to === '') {
                continue;
            }

            $ranges[] = ['from' => $from, 'to' => $to];
        }

        usort($ranges, static fn (array $a, array $b): int => strcmp($a['from'], $b['from']));

        return $ranges;
    }
}
