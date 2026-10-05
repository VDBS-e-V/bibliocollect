<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\DTOs\CopyData;
use App\Modules\Catalog\Enums\CopyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CatalogCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'barcode' => ['required', 'string', 'max:80'],
            'shelf_location' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::enum(CopyStatus::class)],
        ];
    }

    public function toData(): CopyData
    {
        $data = $this->validated();

        return new CopyData(
            barcode: (string) $data['barcode'],
            shelfLocation: $this->nullableString($data['shelf_location'] ?? null),
            status: CopyStatus::from((string) $data['status']),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'barcode' => trim((string) $this->input('barcode', '')),
            'shelf_location' => $this->normalizeNullable($this->input('shelf_location')),
            'status' => mb_strtolower(trim((string) $this->input('status', ''))),
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
