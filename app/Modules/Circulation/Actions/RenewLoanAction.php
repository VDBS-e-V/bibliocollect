<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Services\CirculationRuleEvaluator;
use App\Modules\Circulation\Services\LoanDueDateService;
use App\Modules\Circulation\Services\ReservationBlockChecker;
use App\Modules\Patrons\Models\Patron;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class RenewLoanAction
{
    public function __construct(
        private BusinessClock $clock,
        private CirculationRuleEvaluator $rules,
        private LoanDueDateService $dueDates,
        private ReservationBlockChecker $reservations,
    ) {}

    public function execute(Loan $loan, User $actor): Loan
    {
        return DB::transaction(function () use ($loan, $actor): Loan {
            $snapshot = Loan::query()->findOrFail($loan->getKey());

            // Gleiche Sperrreihenfolge wie bei Ausleihe und Rückgabe: erst Patron und Exemplar, dann die Ausleihe.
            $patron = Patron::query()->whereKey($snapshot->patron_id)->lockForUpdate()->firstOrFail();
            $copy = Copy::query()->whereKey($snapshot->copy_id)->lockForUpdate()->firstOrFail();
            $lockedLoan = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedLoan->returned_at !== null) {
                throw LoanStateConflict::alreadyReturned();
            }

            $violations = $this->rules->renewalViolations(
                $lockedLoan,
                $patron,
                $copy,
                $this->reservations->blocksRenewal($lockedLoan, $copy),
            );

            if ($violations !== []) {
                throw new CirculationRuleViolation($violations);
            }

            $renewedAt = $this->clock->now();

            $lockedLoan->forceFill([
                'due_on' => $this->dueDates->forRenewalAt(
                    $renewedAt,
                    CarbonImmutable::parse($lockedLoan->due_on->toDateString(), $this->clock->timezone()),
                )->toDateString(),
                'renewal_count' => $lockedLoan->renewal_count + 1,
                'last_renewed_at' => $renewedAt,
                'last_renewed_by_user_id' => $actor->getKey(),
            ])->save();

            return $lockedLoan->load('copy.edition.title');
        });
    }
}
