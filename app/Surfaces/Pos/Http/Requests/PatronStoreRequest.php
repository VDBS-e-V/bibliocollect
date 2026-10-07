<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Patrons\DTOs\PatronCreateData;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\School\Rules\ActiveSchoolClassRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PatronStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'library_number' => ['nullable', 'string', 'max:80', Rule::unique('patrons', 'library_number')],
            'card_number' => ['required', 'string', 'max:20'],
            'kind' => ['required', Rule::enum(PatronKind::class)],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'school_class_id' => ['nullable', 'string', new ActiveSchoolClassRule],
            'leaving_on' => ['nullable', 'date', 'after:birth_date'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'card_number.required' => 'Bitte den Ausweis scannen, den die Person bekommt. Neue Konten werden nur mit Ausweis angelegt.',
        ];
    }

    public function cardNumber(): string
    {
        return trim((string) $this->validated('card_number'));
    }

    public function toData(): PatronCreateData
    {
        $data = $this->validated();
        $kind = PatronKind::from((string) $data['kind']);

        return new PatronCreateData(
            libraryNumber: (string) ($data['library_number'] ?? ''),
            kind: $kind,
            firstName: (string) $data['first_name'],
            lastName: (string) $data['last_name'],
            birthDate: (string) $data['birth_date'],
            email: $this->nullableString($data['email'] ?? null),
            schoolClassId: $kind === PatronKind::Student ? $this->nullableString($data['school_class_id'] ?? null) : null,
            leavingOn: $this->nullableString($data['leaving_on'] ?? null),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'library_number' => $this->normalizeNullable($this->input('library_number')),
            'card_number' => trim((string) $this->input('card_number', '')),
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
