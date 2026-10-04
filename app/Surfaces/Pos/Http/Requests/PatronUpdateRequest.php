<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Patrons\DTOs\PatronUpdateData;
use App\Modules\School\Rules\ActiveSchoolClassRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PatronUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $patronId = (string) $this->route('patronId');

        return [
            'library_number' => ['required', 'string', 'max:80', Rule::unique('patrons', 'library_number')->ignore($patronId)],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'school_class_id' => ['nullable', 'string', new ActiveSchoolClassRule],
            'leaving_on' => ['nullable', 'date', 'after:birth_date'],
        ];
    }

    public function toData(): PatronUpdateData
    {
        $data = $this->validated();

        return new PatronUpdateData(
            libraryNumber: (string) $data['library_number'],
            firstName: (string) $data['first_name'],
            lastName: (string) $data['last_name'],
            birthDate: (string) $data['birth_date'],
            email: $this->nullableString($data['email'] ?? null),
            schoolClassId: $this->nullableString($data['school_class_id'] ?? null),
            leavingOn: $this->nullableString($data['leaving_on'] ?? null),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'library_number' => trim((string) $this->input('library_number', '')),
            'first_name' => trim((string) $this->input('first_name', '')),
            'last_name' => trim((string) $this->input('last_name', '')),
            'email' => $this->normalizeNullable($this->input('email')),
            'school_class_id' => $this->normalizeNullable($this->input('school_class_id')),
            'leaving_on' => $this->normalizeNullable($this->input('leaving_on')),
        ]);
    }

    private function normalizeNullable(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
