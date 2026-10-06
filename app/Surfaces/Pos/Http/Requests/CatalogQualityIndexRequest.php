<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Requests;

use App\Modules\Catalog\Enums\MetadataIssue;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CatalogQualityIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(MetadataReviewStatus::class)],
            'problem' => ['nullable', Rule::enum(MetadataIssue::class)],
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 20, 50, 100])],
            'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }

    /** @return array{status: string, problem: string|null, q: string|null} */
    public function filters(): array
    {
        $data = $this->validated();
        $q = isset($data['q']) && is_string($data['q']) ? trim($data['q']) : '';

        return [
            'status' => isset($data['status']) && is_string($data['status']) ? $data['status'] : MetadataReviewStatus::Open->value,
            'problem' => isset($data['problem']) && is_string($data['problem']) ? $data['problem'] : null,
            'q' => $q === '' ? null : $q,
        ];
    }

    public function perPage(): int
    {
        return isset($this->validated()['per_page']) ? (int) $this->validated()['per_page'] : 20;
    }

    public function page(): int
    {
        return isset($this->validated()['page']) ? (int) $this->validated()['page'] : 1;
    }

    /**
     * Filter für Links und Seitennavigation (ohne Seitenzahl, ohne leere Werte).
     *
     * @return array<string, mixed>
     */
    public function queryParameters(): array
    {
        $data = $this->validated();
        unset($data['page']);

        return array_filter($data, static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
