<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Übernimmt ausgewählte Änderungen eines Vorschlags. Es werden nur Schlüssel übertragen, nie Werte. */
final class CatalogQualityApplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'changes' => ['required', 'array', 'min:1', 'max:60'],
            'changes.*' => ['required', 'string', 'max:120', 'regex:/^[a-z_]+(?:\.[A-Za-z0-9_]+)+$/'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'changes.required' => 'Bitte wähle mindestens eine Änderung aus, die übernommen werden soll.',
            'changes.min' => 'Bitte wähle mindestens eine Änderung aus, die übernommen werden soll.',
        ];
    }

    /** @return list<string> */
    public function changeKeys(): array
    {
        $keys = $this->validated()['changes'] ?? [];

        return is_array($keys) ? array_values(array_filter($keys, 'is_string')) : [];
    }
}
