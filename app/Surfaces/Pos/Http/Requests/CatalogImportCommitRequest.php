<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CatalogImportCommitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'confirm_import' => ['required', 'accepted'],
        ];
    }
}
