<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Circulation\Services\CirculationRuleEvaluator;
use App\Modules\Circulation\Services\LoanDueDateService;
use App\Modules\Circulation\Services\ReservationQueueService;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Support\Facades\DB;

final readonly class CheckoutCopyAction
{
    public function __construct(
        private BusinessClock $clock,
        private CirculationRuleEvaluator $rules,
        private LoanDueDateService $dueDates,
        private ReservationQueueService $reservations,
    ) {}

    public function execute(Patron $patron, string $barcode, User $actor): Loan
    {
        $normalizedBarcode = trim($barcode);

        if ($normalizedBarcode === '') {
            throw new CirculationRuleViolation(['Der Exemplar-Barcode fehlt.']);
        }

        return DB::transaction(function () use ($patron, $normalizedBarcode, $actor): Loan {
            $lockedPatron = Patron::query()
                ->whereKey($patron->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $copy = Copy::query()
                ->where('barcode', $normalizedBarcode)
                ->lockForUpdate()
                ->first();

            if (! $copy instanceof Copy) {
                throw CirculationRuleViolation::copyNotFound($normalizedBarcode);
            }

            $edition = Edition::query()
                ->whereKey($copy->edition_id)
                ->lockForUpdate()
                ->firstOrFail();

            $openLoan = Loan::query()
                ->where('copy_id', $copy->getKey())
                ->whereNull('returned_at')
                ->lockForUpdate()
                ->first();

            $hold = $this->reservations->holdFor($copy);

            $violations = $this->rules->checkoutViolations(
                $lockedPatron,
                $copy,
                $edition,
                $openLoan instanceof Loan,
                $hold instanceof Reservation && $hold->patron_id !== (string) $lockedPatron->getKey(),
            );

            if ($violations !== []) {
                throw new CirculationRuleViolation($violations);
            }

            $checkedOutAt = $this->clock->now();
            $dueOn = $this->dueDates->forCheckoutAt($checkedOutAt);

            $loan = Loan::query()->create([
                'patron_id' => $lockedPatron->getKey(),
                'copy_id' => $copy->getKey(),
                'checked_out_at' => $checkedOutAt,
                'due_on' => $dueOn->toDateString(),
                'checked_out_by_user_id' => $actor->getKey(),
            ]);

            $this->fulfilReservation($lockedPatron, $edition->title_id, $copy, $loan, $actor);

            return $loan->load('copy.edition.title');
        });
    }

    /**
     * Schließt eine offene Vormerkung der Person für diesen Titel ab. War für sie ein anderes Exemplar zurückgelegt,
     * wird dieses wieder frei und geht an die nächste wartende Person.
     */
    private function fulfilReservation(Patron $patron, string $titleId, Copy $copy, Loan $loan, User $actor): void
    {
        $reservation = Reservation::query()
            ->where('patron_id', $patron->getKey())
            ->where('title_id', $titleId)
            ->whereIn('status', ReservationStatus::openValues())
            ->lockForUpdate()
            ->first();

        if (! $reservation instanceof Reservation) {
            return;
        }

        $otherCopyId = $reservation->ready_copy_id !== null && $reservation->ready_copy_id !== (string) $copy->getKey()
            ? $reservation->ready_copy_id
            : null;

        $reservation->forceFill([
            'status' => ReservationStatus::Fulfilled,
            'loan_id' => $loan->getKey(),
            'closed_at' => $this->clock->now(),
            'closed_by_user_id' => $actor->getKey(),
        ])->save();

        if ($otherCopyId !== null) {
            $otherCopy = Copy::query()->whereKey($otherCopyId)->lockForUpdate()->first();

            if ($otherCopy instanceof Copy) {
                $this->reservations->promoteForCopy($otherCopy);
            }
        }
    }
}
