<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\DTOs\CatalogIntakeDetails;
use Illuminate\Foundation\Http\FormRequest;

/** Schritt 3 der Erfassung: Titel- und Ausgabedaten bestätigen oder ergänzen. */
final class CatalogIntakeDetailsRequest extends FormRequest
{
    private const STRING_FIELDS = [
        'preferred_title', 'subtitle', 'responsibility_statement', 'isbn', 'publisher_name',
        'publication_place', 'edition_statement', 'physical_extent', 'media_type', 'language_code',
        'original_language_code', 'series_statement', 'target_audience', 'subject_keywords',
        'summary', 'local_classification',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'preferred_title' => ['required', 'string', 'max:500'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'responsibility_statement' => ['nullable', 'string', 'max:2000'],
            'contributors' => ['nullable', 'array', 'max:12'],
            'contributors.*.name' => ['required', 'string', 'max:200'],
            'contributors.*.role' => ['required', 'string', 'max:80', 'regex:/^[a-z][a-z0-9._-]*$/'],
            'contributors.*.gnd_id' => ['nullable', 'string', 'max:80'],
            'isbn' => ['nullable', 'string', 'max:32'],
            'publisher_name' => ['nullable', 'string', 'max:255'],
            'publication_place' => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'between:1000,2100'],
            'edition_statement' => ['nullable', 'string', 'max:255'],
            'physical_extent' => ['nullable', 'string', 'max:255'],
            'media_type' => ['nullable', 'string', 'max:80'],
            'language_code' => ['nullable', 'string', 'max:16'],
            'original_language_code' => ['nullable', 'string', 'max:16'],
            'series_statement' => ['nullable', 'string', 'max:500'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'minimum_age' => ['nullable', 'integer', 'between:0,18'],
            'subject_keywords' => ['nullable', 'string', 'max:5000'],
            'summary' => ['nullable', 'string', 'max:20000'],
            'local_classification' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'preferred_title' => 'Haupttitel',
            'contributors.*.name' => 'Name',
            'contributors.*.role' => 'Rolle',
            'publication_year' => 'Erscheinungsjahr',
            'minimum_age' => 'Mindestalter',
            'isbn' => 'ISBN',
        ];
    }

    public function toDetails(): CatalogIntakeDetails
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return CatalogIntakeDetails::fromArray($data);
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (self::STRING_FIELDS as $field) {
            $values[$field] = $this->nullable($this->input($field));
        }

        $values['publication_year'] = $this->nullable($this->input('publication_year'));
        $values['minimum_age'] = $this->nullable($this->input('minimum_age'));
        $values['contributors'] = $this->contributors($this->input('contributors'));

        $this->merge($values);
    }

    /**
     * Leere Zeilen der Mitwirkenden-Liste werden verworfen; ohne Rollenangabe gilt "Mitwirkende:r".
     *
     * @return list<array{name: string, role: string, gnd_id: string|null}>
     */
    private function contributors(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $contributors = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = $this->nullable($row['name'] ?? null);

            if ($name === null) {
                continue;
            }

            $contributors[] = [
                'name' => $name,
                'role' => $this->nullable($row['role'] ?? null) ?? 'contributor',
                'gnd_id' => $this->nullable($row['gnd_id'] ?? null),
            ];
        }

        return $contributors;
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
