<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Requests;

use App\Modules\School\DTOs\SchoolClassData;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SchoolClassStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolYearId = (string) $this->route('schoolYearId');

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('school_classes', 'name')
                    ->where(static fn (Builder $query): Builder => $query->where('school_year_id', $schoolYearId)),
            ],
            'grade_level' => ['required', 'integer', 'between:1,13'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function toData(): SchoolClassData
    {
        $data = $this->validated();

        return new SchoolClassData(
            name: (string) $data['name'],
            gradeLevel: (int) $data['grade_level'],
            isActive: (bool) $data['is_active'],
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
