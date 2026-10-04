<?php

declare(strict_types=1);

namespace App\Modules\School\Rules;

use App\Modules\School\Models\SchoolClass;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ActiveSchoolClassRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Die ausgewählte Klasse ist ungültig.');

            return;
        }

        $exists = SchoolClass::query()
            ->whereKey($value)
            ->where('is_active', true)
            ->whereRelation('schoolYear', 'is_active', true)
            ->exists();

        if (! $exists) {
            $fail('Die ausgewählte Klasse gehört nicht zu einem aktiven Schuljahr.');
        }
    }
}
