<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Circulation\Actions\CheckoutCopyAction;
use App\Modules\Circulation\Actions\RenewLoanAction;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\CounterTransactionFailed;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\LoanTransaction;
use App\Modules\Patrons\Models\Patron;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Vorgang am Tresen: Positionen (Ausleihe, Verlängerung, Rückgabe) werden gesammelt und vorab geprüft, gebucht wird erst
 * beim Bestätigen, dann alles in einer Transaktion (erst Rückgaben, dann Verlängerungen, dann Ausleihen).
 *
 * Eine Position ist ein Array mit `type` (checkout, renew, return), `barcode`, `title` und bei Verlängerung und Rückgabe `loan_id`.
 */
final readonly class CounterTransactionService
{
    public const CHECKOUT = 'checkout';

    public const RENEW = 'renew';

    public const RETURN = 'return';

    public function __construct(
        private BusinessClock $clock,
        private CirculationRuleEvaluator $rules,
        private LoanPolicy $policy,
        private LoanDueDateService $dueDates,
        private ReservationBlockChecker $reservations,
        private ReservationQueueService $queue,
        private CheckoutCopyAction $checkout,
        private RenewLoanAction $renew,
        private ReturnLoanAction $return,
        private AuditRecorder $audit,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     *
     * @throws CirculationRuleViolation
     */
    public function checkoutItem(?Patron $patron, string $barcode, array $items): array
    {
        if (! $patron instanceof Patron) {
            throw new CirculationRuleViolation(['Bitte zuerst eine Person wählen.']);
        }

        $copy = $this->copy($barcode);
        $this->assertNotInDraft($items, $copy->barcode);

        $edition = Edition::query()->with('title')->findOrFail($copy->edition_id);
        $returning = $this->hasItem($items, self::RETURN, $copy->barcode);
        $openLoan = Loan::query()->where('copy_id', $copy->getKey())->whereNull('returned_at')->exists();
        $hold = $this->queue->holdFor($copy);

        $returns = count(array_filter($items, static fn (array $item): bool => $item['type'] === self::RETURN));
        $checkouts = count(array_filter($items, static fn (array $item): bool => $item['type'] === self::CHECKOUT));

        $violations = $this->rules->checkoutViolations(
            $patron,
            $copy,
            $edition,
            $openLoan && ! $returning,
            $hold !== null && $hold->patron_id !== (string) $patron->getKey(),
            max(0, $this->policy->openLoanCount($patron) - $returns) + $checkouts,
            $this->policy->maxOpenLoans($patron),
        );

        if ($violations !== []) {
            throw new CirculationRuleViolation($violations);
        }

        $due = $this->dueDates->forCheckoutAt($this->clock->now(), $this->policy->periodDays($patron, $edition));

        return ['type' => self::CHECKOUT, 'barcode' => $copy->barcode, 'title' => $edition->title->preferred_title, 'due_on' => $due->toDateString()];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     *
     * @throws CirculationRuleViolation
     */
    public function returnItem(?Patron $patron, string $barcode, array $items): array
    {
        $copy = $this->copy($barcode);
        $this->assertNotInDraft($items, $copy->barcode);

        $loan = Loan::query()->where('copy_id', $copy->getKey())->whereNull('returned_at')->first();

        if (! $loan instanceof Loan) {
            throw new CirculationRuleViolation(["Das Exemplar {$copy->barcode} ist nicht ausgeliehen."]);
        }

        if ($patron instanceof Patron && $loan->patron_id !== (string) $patron->getKey()) {
            throw new CirculationRuleViolation(["Das Exemplar {$copy->barcode} ist auf ein anderes Ausleihkonto ausgeliehen. Rückgaben anderer Personen bitte in einem eigenen Vorgang ohne Personenauswahl buchen."]);
        }

        $title = Edition::query()->with('title')->findOrFail($copy->edition_id)->title->preferred_title;

        return ['type' => self::RETURN, 'barcode' => $copy->barcode, 'title' => $title, 'loan_id' => (string) $loan->getKey()];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     *
     * @throws CirculationRuleViolation
     */
    public function renewItem(?Patron $patron, string $loanId, array $items): array
    {
        if (! $patron instanceof Patron) {
            throw new CirculationRuleViolation(['Bitte zuerst eine Person wählen.']);
        }

        $loan = Loan::query()->where('patron_id', $patron->getKey())->whereNull('returned_at')->with('copy.edition.title')->find($loanId);

        if (! $loan instanceof Loan) {
            throw new CirculationRuleViolation(['Diese Ausleihe gibt es für das gewählte Ausleihkonto nicht (mehr).']);
        }

        $this->assertNotInDraft($items, $loan->copy->barcode);

        $violations = $this->rules->renewalViolations($loan, $patron, $loan->copy, $this->reservations->blocksRenewal($loan, $loan->copy));

        if ($violations !== []) {
            throw new CirculationRuleViolation($violations);
        }

        $due = $this->dueDates->forRenewalAt(
            $this->clock->now(),
            CarbonImmutable::parse($loan->due_on->toDateString(), $this->clock->timezone()),
            $this->policy->periodDays($patron, $loan->copy->edition),
        );

        return ['type' => self::RENEW, 'barcode' => $loan->copy->barcode, 'title' => $loan->copy->edition->title->preferred_title, 'loan_id' => (string) $loan->getKey(), 'due_on' => $due->toDateString()];
    }

    /**
     * Verlängerung über den Barcode eines Exemplars.
     *
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function renewItemByBarcode(?Patron $patron, string $barcode, array $items): array
    {
        $copy = $this->copy($barcode);
        $loan = Loan::query()->where('copy_id', $copy->getKey())->whereNull('returned_at')->first();

        if (! $loan instanceof Loan) {
            throw new CirculationRuleViolation(["Das Exemplar {$copy->barcode} ist nicht ausgeliehen."]);
        }

        return $this->renewItem($patron, (string) $loan->getKey(), $items);
    }

    /**
     * Bucht alle Positionen. Scheitert eine, wird nichts gebucht.
     *
     * @param  list<array<string, mixed>>  $items
     *
     * @throws CounterTransactionFailed
     */
    public function confirm(?Patron $patron, array $items, User $actor): LoanTransaction
    {
        if ($items === []) {
            throw CounterTransactionFailed::empty();
        }

        $order = [self::RETURN => 0, self::RENEW => 1, self::CHECKOUT => 2];
        $positions = [];

        foreach ($items as $index => $item) {
            $positions[] = ['position' => $index + 1, 'item' => $item];
        }

        usort($positions, static fn (array $a, array $b): int => [$order[$a['item']['type']], $a['position']] <=> [$order[$b['item']['type']], $b['position']]);

        return DB::transaction(function () use ($patron, $positions, $actor): LoanTransaction {
            $results = [];

            foreach ($positions as ['position' => $position, 'item' => $item]) {
                try {
                    $results[$position] = $this->book($patron, $item, $actor);
                } catch (CirculationRuleViolation|LoanStateConflict $exception) {
                    throw CounterTransactionFailed::atPosition($position, (string) ($item['title'] ?? $item['barcode']), $exception->getMessage());
                }
            }

            ksort($results);
            $results = array_values($results);
            $count = static fn (string $type): int => count(array_filter($results, static fn (array $result): bool => $result['type'] === $type));

            $transaction = LoanTransaction::query()->create([
                'number' => $this->nextNumber(),
                'patron_id' => $patron?->getKey(),
                'created_by_user_id' => $actor->getKey(),
                'items' => $results,
                'checked_out_count' => $count(self::CHECKOUT),
                'renewed_count' => $count(self::RENEW),
                'returned_count' => $count(self::RETURN),
            ]);

            $this->audit->record(
                'circulation.transaction.confirmed',
                "Vorgang {$transaction->number} bestätigt: {$transaction->checked_out_count} ausgeliehen, {$transaction->renewed_count} verlängert, {$transaction->returned_count} zurückgegeben.",
                $transaction,
                ['patron_id' => $patron?->getKey() !== null ? (string) $patron->getKey() : null],
                (int) $actor->getKey(),
            );

            return $transaction;
        });
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function book(?Patron $patron, array $item, User $actor): array
    {
        $result = ['type' => $item['type'], 'barcode' => $item['barcode'], 'title' => $item['title']];

        switch ($item['type']) {
            case self::RETURN:
                $loan = Loan::query()->findOrFail($item['loan_id']);
                $this->return->execute($loan, $actor);
                $result['returned_on'] = $this->clock->now()->toDateString();
                $notice = trim($this->queue->holdNotice(Copy::query()->findOrFail($loan->copy_id)));
                $result['note'] = $notice !== '' ? $notice : null;

                return $result;

            case self::RENEW:
                $renewed = $this->renew->execute(Loan::query()->findOrFail($item['loan_id']), $actor);
                $result['due_on'] = $renewed->due_on->toDateString();

                return $result;

            default:
                if (! $patron instanceof Patron) {
                    throw new CirculationRuleViolation(['Für eine Ausleihe fehlt die Person.']);
                }

                $loan = $this->checkout->execute($patron, (string) $item['barcode'], $actor);
                $result['due_on'] = $loan->due_on->toDateString();

                return $result;
        }
    }

    private function copy(string $barcode): Copy
    {
        $barcode = trim($barcode);

        if ($barcode === '') {
            throw new CirculationRuleViolation(['Bitte einen Barcode scannen oder eingeben.']);
        }

        return Copy::query()->where('barcode', $barcode)->first() ?? throw CirculationRuleViolation::copyNotFound($barcode);
    }

    /** @param  list<array<string, mixed>>  $items */
    private function assertNotInDraft(array $items, string $barcode): void
    {
        foreach ($items as $item) {
            if (($item['barcode'] ?? null) === $barcode) {
                throw new CirculationRuleViolation(["Das Exemplar {$barcode} ist schon im Vorgang."]);
            }
        }
    }

    /** @param  list<array<string, mixed>>  $items */
    private function hasItem(array $items, string $type, string $barcode): bool
    {
        foreach ($items as $item) {
            if ($item['type'] === $type && $item['barcode'] === $barcode) {
                return true;
            }
        }

        return false;
    }

    private function nextNumber(): string
    {
        $prefix = 'V-'.$this->clock->now()->format('Ymd').'-';
        $counter = LoanTransaction::query()->where('number', 'like', $prefix.'%')->count() + 1;

        while (LoanTransaction::query()->where('number', $prefix.str_pad((string) $counter, 3, '0', STR_PAD_LEFT))->exists()) {
            $counter++;
        }

        return $prefix.str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
    }
}
