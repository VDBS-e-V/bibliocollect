<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\DTOs\EditionData;
use Illuminate\Foundation\Http\FormRequest;

final class CatalogEditionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'edition_statement' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:32'],
            'publisher_name' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'between:1000,2100'],
            'media_type' => ['nullable', 'string', 'max:80'],
            'language_code' => ['nullable', 'string', 'max:16'],
            'minimum_age' => ['nullable', 'integer', 'between:0,18'],
            'age_rating_label' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function toData(): EditionData
    {
        $data = $this->validated();

        return new EditionData(
            editionStatement: $this->nullableString($data['edition_statement'] ?? null),
            isbn: $this->nullableString($data['isbn'] ?? null),
            publisherName: $this->nullableString($data['publisher_name'] ?? null),
            publicationYear: isset($data['publication_year']) ? (int) $data['publication_year'] : null,
            mediaType: $this->nullableString($data['media_type'] ?? null),
            languageCode: $this->nullableString($data['language_code'] ?? null),
            minimumAge: isset($data['minimum_age']) ? (int) $data['minimum_age'] : null,
            ageRatingLabel: $this->nullableString($data['age_rating_label'] ?? null),
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'edition_statement' => $this->normalizeNullable($this->input('edition_statement')),
            'isbn' => $this->normalizeNullable($this->input('isbn')),
            'publisher_name' => $this->normalizeNullable($this->input('publisher_name')),
            'publication_year' => $this->normalizeNullable($this->input('publication_year')),
            'media_type' => $this->normalizeNullable($this->input('media_type')),
            'language_code' => $this->normalizeNullable($this->input('language_code')),
            'minimum_age' => $this->normalizeNullable($this->input('minimum_age')),
            'age_rating_label' => $this->normalizeNullable($this->input('age_rating_label')),
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
