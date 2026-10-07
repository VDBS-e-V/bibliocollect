<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Services\ReservationQueueService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class ReturnLoanAction
{
    public function __construct(
        private BusinessClock $clock,
        private ReservationQueueService $reservations,
        private AuditRecorder $audit,
    ) {}

    /** Mit $at wird eine Rückgabe nachgetragen, die am Tag $at stattfand (Notbetrieb mit Papierliste). */
    public function execute(Loan $loan, User $actor, ?CarbonImmutable $at = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $at): Loan {
            $loanSnapshot = Loan::query()->findOrFail($loan->getKey());

            $copy = Copy::query()
                ->whereKey($loanSnapshot->copy_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedLoan = Loan::query()
                ->whereKey($loan->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedLoan->returned_at !== null) {
                throw LoanStateConflict::alreadyReturned();
            }

            $lockedLoan->forceFill([
                'returned_at' => $at ?? $this->clock->now(),
                'returned_by_user_id' => $actor->getKey(),
                'outcome' => 'returned',
            ])->save();

            $this->audit->record(
                'circulation.loan.returned',
                $at !== null ? "Exemplar {$copy->barcode} nachgetragen (zurückgegeben am {$at->format('d.m.Y')})." : "Exemplar {$copy->barcode} zurückgegeben.",
                $lockedLoan,
                ['patron_id' => (string) $lockedLoan->patron_id, 'copy_id' => (string) $copy->getKey(), 'backdated' => $at !== null],
                (int) $actor->getKey(),
            );

            // Wartet jemand auf den Titel, wird das zurückgegebene Exemplar für die erste Vormerkung zurückgelegt.
            $this->reservations->promoteForCopy($copy);

            return $lockedLoan->load('copy.edition.title');
        });
    }
}
