<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\Enums\CopyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Schritt 4 der Erfassung: Standort und Zustand des Exemplars. Der Barcode stammt aus Schritt 1. */
final class CatalogIntakeCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'shelf_location' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::enum(CopyStatus::class)],
        ];
    }

    public function shelfLocation(): ?string
    {
        $value = $this->validated()['shelf_location'] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    public function status(): CopyStatus
    {
        return CopyStatus::from((string) $this->validated()['status']);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => mb_strtolower(trim((string) $this->input('status', ''))),
        ]);
    }
}
