<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReservationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:80'],
        ];
    }

    public function identifier(): string
    {
        return (string) $this->validated('identifier');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'identifier' => trim((string) $this->input('identifier', '')),
        ]);
    }
}
