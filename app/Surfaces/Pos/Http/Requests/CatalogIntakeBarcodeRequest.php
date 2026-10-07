<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Services\CatalogInventoryNumber;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/** Schritt 1 der Erfassung: nur die Inventarnummer (genau 7 Ziffern, noch nicht vergeben). */
final class CatalogIntakeBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['barcode' => ['required', 'string', 'max:80']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['barcode.required' => 'Bitte die Inventarnummer scannen oder eingeben.'];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! CatalogInventoryNumber::isValid($this->barcode())) {
                $validator->errors()->add('barcode', CatalogInventoryNumber::MESSAGE);

                return;
            }

            if (Copy::query()->where('barcode', $this->barcode())->exists()) {
                $validator->errors()->add('barcode', 'Diese Inventarnummer ist bereits einem Exemplar zugeordnet.');
            }
        });
    }

    public function barcode(): string
    {
        return trim((string) $this->input('barcode', ''));
    }
}
