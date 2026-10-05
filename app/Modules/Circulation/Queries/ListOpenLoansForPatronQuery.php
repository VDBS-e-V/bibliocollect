<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Queries;

use App\Modules\Circulation\Models\Loan;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Database\Eloquent\Collection;

final class ListOpenLoansForPatronQuery
{
    /** @return Collection<int, Loan> */
    public function execute(Patron $patron): Collection
    {
        return Loan::query()
            ->where('patron_id', $patron->getKey())
            ->whereNull('returned_at')
            ->with('copy.edition.title')
            ->orderBy('due_on')
            ->orderBy('checked_out_at')
            ->get();
    }
}
