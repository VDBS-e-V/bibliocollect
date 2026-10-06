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
            $rules["days.{$day}.opens_at"] = ['nullable', 'date_format:H:i'];
            $rules["days.{$day}.closes_at"] = ['nullable', 'date_format:H:i'];
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
                $opens = $this->input("days.{$day}.opens_at");
                $closes = $this->input("days.{$day}.closes_at");

                if (! is_string($opens) || ! is_string($closes) || $opens === '' || $closes === '') {
                    $validator->errors()->add("days.{$day}.opens_at", 'Für geöffnete Tage werden Beginn und Ende benötigt.');
                } elseif ($closes <= $opens) {
                    $validator->errors()->add("days.{$day}.closes_at", 'Das Ende muss nach dem Beginn liegen.');
                }
            }

            if ($open === 0) {
                $validator->errors()->add('days', 'Mindestens ein Wochentag muss geöffnet sein.');
            }
        });
    }

    /** @return array<int, array{is_open: bool, opens_at: ?string, closes_at: ?string}> */
    public function days(): array
    {
        $days = [];

        foreach (range(1, 7) as $day) {
            $open = $this->boolean("days.{$day}.is_open");
            $opens = $this->input("days.{$day}.opens_at");
            $closes = $this->input("days.{$day}.closes_at");

            $days[$day] = [
                'is_open' => $open,
                'opens_at' => $open && is_string($opens) ? $opens : null,
                'closes_at' => $open && is_string($closes) ? $closes : null,
            ];
        }

        return $days;
    }
}
