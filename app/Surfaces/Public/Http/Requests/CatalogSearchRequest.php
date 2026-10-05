<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Requests;

use App\Modules\Catalog\DTOs\CatalogSearchCriteria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CatalogSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'media_type' => ['nullable', 'string', 'max:80'],
            'language_code' => ['nullable', 'string', 'max:16'],
            'active_only' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['title', 'recent'])],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function toCriteria(): CatalogSearchCriteria
    {
        $data = $this->validated();
        $sort = $data['sort'] ?? 'title';

        return new CatalogSearchCriteria(
            term: $this->nullableString($data['q'] ?? null),
            mediaType: $this->nullableString($data['media_type'] ?? null),
            languageCode: $this->nullableString($data['language_code'] ?? null),
            activeCopiesOnly: $this->boolean('active_only'),
            sort: is_string($sort) ? $sort : 'title',
            perPage: 12,
            page: isset($data['page']) ? (int) $data['page'] : 1,
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->normalizeNullable($this->input('q')),
            'media_type' => $this->normalizeLowercaseNullable($this->input('media_type')),
            'language_code' => $this->normalizeLowercaseNullable($this->input('language_code')),
            'sort' => $this->normalizeNullable($this->input('sort')),
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

    private function normalizeLowercaseNullable(mixed $value): ?string
    {
        $value = $this->normalizeNullable($value);

        return $value === null ? null : mb_strtolower($value);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
