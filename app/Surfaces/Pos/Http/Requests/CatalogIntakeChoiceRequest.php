<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Schritt 2 der Erfassung: Treffer übernehmen, Exemplar zu vorhandener Ausgabe ergänzen oder manuell erfassen. */
final class CatalogIntakeChoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'choice' => ['required', 'string', 'regex:/^(?:hit:\d{1,2}|edition:[0-9a-z]{26}|manual)$/'],
        ];
    }

    public function choice(): string
    {
        return (string) $this->validated()['choice'];
    }
}
