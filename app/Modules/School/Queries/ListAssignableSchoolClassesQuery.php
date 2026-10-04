<?php

declare(strict_types=1);

namespace App\Modules\School\Queries;

use App\Modules\School\Models\SchoolClass;
use Illuminate\Database\Eloquent\Collection;

final class ListAssignableSchoolClassesQuery
{
    /** @return Collection<int, SchoolClass> */
    public function execute(): Collection
    {
        return SchoolClass::query()
            ->with('schoolYear')
            ->where('is_active', true)
            ->whereRelation('schoolYear', 'is_active', true)
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
    }
}
