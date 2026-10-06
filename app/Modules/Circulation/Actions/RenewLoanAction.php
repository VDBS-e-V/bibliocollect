<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
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
        private AuditRecorder $audit,
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

            $this->audit->record(
                'circulation.loan.renewed',
                "Ausleihe von Exemplar {$copy->barcode} verlängert, neu fällig am {$lockedLoan->due_on->format('d.m.Y')}.",
                $lockedLoan,
                ['patron_id' => (string) $lockedLoan->patron_id, 'copy_id' => (string) $copy->getKey(), 'renewal_count' => $lockedLoan->renewal_count, 'due_on' => $lockedLoan->due_on->toDateString()],
                (int) $actor->getKey(),
            );

            return $lockedLoan->load('copy.edition.title');
        });
    }
}
