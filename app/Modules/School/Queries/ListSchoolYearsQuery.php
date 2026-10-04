<?php

declare(strict_types=1);

namespace App\Modules\School\Queries;

use App\Modules\School\Models\SchoolYear;
use Illuminate\Database\Eloquent\Collection;

final class ListSchoolYearsQuery
{
    /** @return Collection<int, SchoolYear> */
    public function execute(): Collection
    {
        return SchoolYear::query()
            ->with('classes')
            ->orderByDesc('starts_on')
            ->get();
    }
}
