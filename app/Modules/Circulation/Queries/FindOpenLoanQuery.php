<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Queries;

use App\Modules\Circulation\Models\Loan;
use App\Modules\Patrons\Models\Patron;

final class FindOpenLoanQuery
{
    public function forPatron(string $loanId, Patron $patron): Loan
    {
        return Loan::query()
            ->where('patron_id', $patron->getKey())
            ->whereNull('returned_at')
            ->findOrFail($loanId);
    }
}
