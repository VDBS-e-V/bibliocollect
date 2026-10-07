<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\Enums\CopyAccess;
use App\Modules\Catalog\Enums\CopyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Schritt 5 der Erfassung: Zustand und Zugänglichkeit des Exemplars. Der Standort kommt später beim Einsortieren ins Regal. */
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
            'access_status' => ['required', Rule::enum(CopyAccess::class)],
        ];
    }

    public function access(): CopyAccess
    {
        return CopyAccess::from((string) $this->validated()['access_status']);
    }

    public function status(): CopyStatus
    {
        return CopyStatus::from((string) $this->validated()['status']);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => mb_strtolower(trim((string) $this->input('status', ''))),
            'access_status' => trim((string) $this->input('access_status', CopyAccess::Free->value)),
        ]);
    }
}
