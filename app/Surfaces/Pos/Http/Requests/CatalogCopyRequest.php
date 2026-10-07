<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\DTOs\CopyData;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Services\CatalogInventoryNumber;
use App\Modules\Catalog\Services\CatalogShelfOptions;
use Illuminate\Contracts\Validation\Validator;
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
        $current = $this->currentCopy();

        // Beim Bearbeiten darf ein bisheriger, nicht mehr gelisteter Standort stehen bleiben.
        $allowed = app(CatalogShelfOptions::class)->activeCodes();

        if ($current instanceof Copy && is_string($current->shelf_location) && $current->shelf_location !== '') {
            $allowed[] = $current->shelf_location;
        }

        return [
            'barcode' => ['required', 'string', 'max:80'],
            'shelf_location' => ['nullable', 'string', 'max:120', Rule::in($allowed)],
            'status' => ['required', Rule::enum(CopyStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['shelf_location.in' => 'Bitte ein Regalbrett aus der Liste wählen.'];
    }

    /** Neue Inventarnummern bestehen aus 7 Ziffern; bei einem bestehenden Exemplar gilt das nur, wenn die Nummer geändert wird. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $barcode = (string) $this->input('barcode', '');
            $current = $this->currentCopy();

            if (($current === null || $current->barcode !== $barcode) && ! CatalogInventoryNumber::isValid($barcode)) {
                $validator->errors()->add('barcode', CatalogInventoryNumber::MESSAGE);
            }
        });
    }

    public function toData(): CopyData
    {
        $data = $this->validated();

        return new CopyData(
            barcode: (string) $data['barcode'],
            // Ein neues Exemplar bekommt seinen Standort erst beim Einsortieren; nur beim Bearbeiten wird er hier gesetzt.
            shelfLocation: $this->isMethod('POST') ? null : $this->nullableString($data['shelf_location'] ?? null),
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

    private function currentCopy(): ?Copy
    {
        $id = $this->route('copyId');

        return is_string($id) ? Copy::query()->find($id) : null;
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
