<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\DTOs\CatalogImportMapping;
use App\Modules\Catalog\Enums\CatalogImportField;
use App\Modules\Catalog\Queries\FindCatalogImportBatchQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CatalogImportMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $batch = app(FindCatalogImportBatchQuery::class)->execute((string) $this->route('batchId'));
        $headers = $batch->headers;
        $rules = [
            'mapping' => ['required', 'array'],
        ];

        foreach (CatalogImportField::cases() as $field) {
            $fieldRules = ['nullable', 'string', Rule::in($headers)];

            if (in_array($field, [CatalogImportField::PreferredTitle, CatalogImportField::Barcode], true)) {
                $fieldRules[0] = 'required';
            }

            $rules['mapping.'.$field->value] = $fieldRules;
        }

        return $rules;
    }

    public function toMapping(): CatalogImportMapping
    {
        $validated = $this->validated('mapping');

        return CatalogImportMapping::fromArray(is_array($validated) ? $validated : []);
    }
}
