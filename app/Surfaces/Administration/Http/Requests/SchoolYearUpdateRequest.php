<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Requests;

use App\Modules\School\DTOs\SchoolYearData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SchoolYearUpdateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:80', Rule::unique('school_years', 'name')->ignore($schoolYearId)],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ];
    }

    public function toData(): SchoolYearData
    {
        $data = $this->validated();

        return new SchoolYearData(
            name: (string) $data['name'],
            startsOn: (string) $data['starts_on'],
            endsOn: (string) $data['ends_on'],
        );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name', '')),
        ]);
    }
}
