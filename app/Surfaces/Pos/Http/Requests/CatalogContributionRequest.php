<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\DTOs\TitleContributionData;
use Illuminate\Foundation\Http\FormRequest;

final class CatalogContributionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:255'],
            'sort_name' => ['nullable', 'string', 'max:255'],
            'role_key' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9._-]*$/'],
            'position' => ['required', 'integer', 'between:0,9999'],
        ];
    }

    public function toData(): TitleContributionData
    {
        $data = $this->validated();

        return new TitleContributionData(
            displayName: (string) $data['display_name'],
            sortName: $this->nullableString($data['sort_name'] ?? null),
            roleKey: (string) $data['role_key'],
            position: (int) $data['position'],
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'display_name' => trim((string) $this->input('display_name', '')),
            'sort_name' => $this->normalizeNullable($this->input('sort_name')),
            'role_key' => mb_strtolower(trim((string) $this->input('role_key', ''))),
            'position' => trim((string) $this->input('position', '')),
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
