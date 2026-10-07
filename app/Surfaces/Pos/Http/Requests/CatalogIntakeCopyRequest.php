<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\Enums\CopyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Schritt 5 der Erfassung: Zustand des Exemplars. Der Standort kommt später beim Einsortieren ins Regal. */
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
            'status' => ['required', Rule::enum(CopyStatus::class)],
            'signature_id' => ['nullable', 'string', Rule::exists('catalog_signatures', 'id')],
        ];
    }

    public function signatureId(): ?string
    {
        $value = $this->validated()['signature_id'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
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
