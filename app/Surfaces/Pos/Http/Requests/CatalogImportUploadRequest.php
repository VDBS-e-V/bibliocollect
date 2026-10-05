<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CatalogImportUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'catalog_file' => ['required', 'file', 'extensions:csv', 'max:10240'],
        ];
    }
}
