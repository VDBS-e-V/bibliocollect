<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Schritt 5 der Erfassung: Speichern, danach weiteres Medium erfassen oder den Titel öffnen. */
final class CatalogIntakeCommitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'next' => ['required', Rule::in(['again', 'open'])],
        ];
    }

    public function wantsAnother(): bool
    {
        return $this->validated()['next'] === 'again';
    }
}
