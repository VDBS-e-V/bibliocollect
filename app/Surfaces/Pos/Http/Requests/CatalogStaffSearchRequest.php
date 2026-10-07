<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\DTOs\CatalogSearchCriteria;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CatalogStaffSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:160'],
            'title' => ['nullable', 'string', 'max:255'],
            'contributor' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:160'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'publication_place' => ['nullable', 'string', 'max:255'],
            'series' => ['nullable', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:255'],
            'classification' => ['nullable', 'string', 'max:160'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'source_record_id' => ['nullable', 'string', 'max:120'],
            'year_from' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'year_to' => ['nullable', 'integer', 'min:1000', 'max:2100', Rule::when($this->filled('year_from'), ['gte:year_from'])],
            'media_type' => ['nullable', 'string', 'max:80'],
            'language_code' => ['nullable', 'string', 'max:16'],
            'active_only' => ['nullable', 'boolean'],
            'available_only' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['title', 'title_desc', 'year_desc', 'year_asc', 'recent'])],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    public function toCriteria(): CatalogSearchCriteria
    {
        $data = $this->validated();
        $sort = $data['sort'] ?? 'title';

        return new CatalogSearchCriteria(
            term: $this->nullableString($data['q'] ?? null),
            title: $this->nullableString($data['title'] ?? null),
            contributor: $this->nullableString($data['contributor'] ?? null),
            subject: $this->nullableString($data['subject'] ?? null),
            identifier: $this->nullableString($data['identifier'] ?? null),
            publisher: $this->nullableString($data['publisher'] ?? null),
            publicationPlace: $this->nullableString($data['publication_place'] ?? null),
            series: $this->nullableString($data['series'] ?? null),
            topic: $this->nullableString($data['topic'] ?? null),
            classification: $this->nullableString($data['classification'] ?? null),
            targetAudience: $this->nullableString($data['target_audience'] ?? null),
            sourceRecordId: $this->nullableString($data['source_record_id'] ?? null),
            yearFrom: isset($data['year_from']) ? (int) $data['year_from'] : null,
            yearTo: isset($data['year_to']) ? (int) $data['year_to'] : null,
            mediaType: $this->nullableString($data['media_type'] ?? null),
            languageCode: $this->nullableString($data['language_code'] ?? null),
            activeCopiesOnly: $this->boolean('active_only'),
            availableNowOnly: $this->boolean('available_only'),
            sort: is_string($sort) ? $sort : 'title',
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 20,
            page: isset($data['page']) ? (int) $data['page'] : 1,
        );
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'q',
            'title',
            'contributor',
            'subject',
            'identifier',
            'publisher',
            'publication_place',
            'series',
            'topic',
            'classification',
            'target_audience',
            'source_record_id',
            'sort',
        ] as $field) {
            $this->merge([$field => $this->normalizeNullable($this->input($field))]);
        }

        $this->merge([
            'media_type' => $this->normalizeLowercaseNullable($this->input('media_type')),
            'language_code' => $this->normalizeLowercaseNullable($this->input('language_code')),
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

    private function normalizeLowercaseNullable(mixed $value): ?string
    {
        $value = $this->normalizeNullable($value);

        return $value === null ? null : mb_strtolower($value);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
