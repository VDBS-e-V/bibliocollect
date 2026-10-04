<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Queries;

use App\Modules\Patrons\Models\Patron;

final class FindPatronQuery
{
    public function byId(string $id): Patron
    {
        return Patron::query()
            ->with('schoolClass')
            ->findOrFail($id);
    }
}
