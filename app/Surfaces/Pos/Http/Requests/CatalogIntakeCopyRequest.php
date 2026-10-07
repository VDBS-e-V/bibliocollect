<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Services\CatalogShelfOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Schritt 5 der Erfassung: Standort (ein Regalbrett aus der Liste) und Zustand des Exemplars. */
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
            'shelf_location' => ['nullable', 'string', 'max:120', Rule::in(app(CatalogShelfOptions::class)->activeCodes())],
            'status' => ['required', Rule::enum(CopyStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['shelf_location.in' => 'Bitte ein Regalbrett aus der Liste wählen.'];
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
