<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Requests;

use App\Modules\School\DTOs\SchoolClassData;
use App\Modules\School\Models\SchoolClass;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SchoolClassUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $schoolClassId = (string) $this->route('schoolClassId');
        $schoolClass = SchoolClass::query()->findOrFail($schoolClassId);

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('school_classes', 'name')
                    ->where(static fn (Builder $query): Builder => $query->where('school_year_id', $schoolClass->school_year_id))
                    ->ignore($schoolClassId),
            ],
            'grade_level' => ['required', 'integer', 'between:1,13'],
            'is_active' => ['required', 'boolean'],
            'homeroom_teacher' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function toData(): SchoolClassData
    {
        $data = $this->validated();

        return new SchoolClassData(
            name: (string) $data['name'],
            gradeLevel: (int) $data['grade_level'],
            isActive: (bool) $data['is_active'],
            homeroomTeacher: is_string($data['homeroom_teacher'] ?? null) && trim($data['homeroom_teacher']) !== '' ? trim($data['homeroom_teacher']) : null,
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
