<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Schritt 2 der Erfassung: ISBN oder Titel/Autor für die externe Abfrage. Die Inventarnummer steht schon im Entwurf. */
final class CatalogIntakeLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'isbn' => ['nullable', 'string', 'max:32'],
            'title' => ['nullable', 'string', 'max:200'],
            'person' => ['nullable', 'string', 'max:200'],
            'action' => ['required', Rule::in(['lookup', 'manual'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $isbn = $this->isbn();

            if ($isbn !== null && ! app(CatalogIsbnNormalizer::class)->isStandardFormat($isbn)) {
                $validator->errors()->add('isbn', 'Bitte eine gültige ISBN-10 oder ISBN-13 eingeben (Ziffern, Bindestriche sind erlaubt).');
            }

            if ($this->wantsLookup() && $isbn === null && $this->searchTitle() === null && $this->searchPerson() === null) {
                $validator->errors()->add('isbn', 'Gib eine ISBN oder einen Titel zur Abfrage ein – oder erfasse das Medium ohne Abfrage.');
            }
        });
    }

    /** Normalisierte ISBN, sofern eine eingegeben wurde. */
    public function isbn(): ?string
    {
        $value = $this->nullable($this->input('isbn'));

        return $value === null ? null : app(CatalogIsbnNormalizer::class)->normalize($value);
    }

    public function searchTitle(): ?string
    {
        return $this->nullable($this->input('title'));
    }

    public function searchPerson(): ?string
    {
        return $this->nullable($this->input('person'));
    }

    public function wantsLookup(): bool
    {
        return $this->input('action') === 'lookup';
    }

    private function nullable(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
