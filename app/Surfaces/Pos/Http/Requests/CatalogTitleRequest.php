<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\DTOs\TitleData;
use Illuminate\Foundation\Http\FormRequest;

final class CatalogTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'preferred_title' => ['required', 'string', 'max:500'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'sort_title' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function toData(): TitleData
    {
        $data = $this->validated();

        return new TitleData(
            preferredTitle: (string) $data['preferred_title'],
            subtitle: $this->nullableString($data['subtitle'] ?? null),
            sortTitle: $this->nullableString($data['sort_title'] ?? null),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'preferred_title' => trim((string) $this->input('preferred_title', '')),
            'subtitle' => $this->normalizeNullable($this->input('subtitle')),
            'sort_title' => $this->normalizeNullable($this->input('sort_title')),
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
