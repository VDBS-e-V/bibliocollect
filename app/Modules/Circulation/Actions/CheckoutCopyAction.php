<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Services\CirculationRuleEvaluator;
use App\Modules\Circulation\Services\LoanDueDateService;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Support\Facades\DB;

final readonly class CheckoutCopyAction
{
    public function __construct(
        private BusinessClock $clock,
        private CirculationRuleEvaluator $rules,
        private LoanDueDateService $dueDates,
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

            $violations = $this->rules->checkoutViolations(
                $lockedPatron,
                $copy,
                $edition,
                $openLoan instanceof Loan,
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

            return $loan->load('copy.edition.title');
        });
    }
}
