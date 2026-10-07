<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Exceptions\PaperEntryRejected;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Patrons\Models\Patron;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\DB;

/**
 * Trägt Ausleihen und Rückgaben nach, die während eines Ausfalls auf Papier festgehalten wurden. Alles oder nichts:
 * Sind Zeilen fehlerhaft, wird nichts gebucht und jede fehlerhafte Zeile genannt. Die üblichen Regeln gelten weiter.
 */
final readonly class RecordPaperTransactionsAction
{
    /** So weit darf ein Eintrag zurückliegen. */
    public const MAX_DAYS_BACK = 365;

    public function __construct(
        private BusinessClock $clock,
        private CheckoutCopyAction $checkout,
        private ReturnLoanAction $return,
    ) {}

    /**
     * @param  list<string>  $barcodes
     * @return int Anzahl gebuchter Ausleihen
     *
     * @throws PaperEntryRejected
     */
    public function loans(Patron $patron, string $date, array $barcodes, User $actor): int
    {
        $at = $this->moment($date);

        return $this->all($this->clean($barcodes), function (string $barcode) use ($patron, $at, $actor): void {
            $this->checkout->execute($patron, $barcode, $actor, $at);
        });
    }

    /**
     * @param  list<string>  $barcodes
     * @return int Anzahl gebuchter Rückgaben
     *
     * @throws PaperEntryRejected
     */
    public function returns(string $date, array $barcodes, User $actor): int
    {
        $at = $this->moment($date);

        return $this->all($this->clean($barcodes), function (string $barcode) use ($at, $actor): void {
            $loan = Loan::query()
                ->whereNull('returned_at')
                ->whereHas('copy', static fn ($query) => $query->where('barcode', $barcode))
                ->first();

            if (! $loan instanceof Loan) {
                throw new CirculationRuleViolation(['Dieses Exemplar ist nicht ausgeliehen (oder die Nummer ist unbekannt).']);
            }

            $checkedOutOn = $loan->checked_out_at->timezone($this->clock->timezone());

            if ($at->toDateString() < $checkedOutOn->toDateString()) {
                throw new CirculationRuleViolation(['Das Rückgabedatum liegt vor dem Ausleihdatum ('.$checkedOutOn->format('d.m.Y').').']);
            }

            $this->return->execute($loan, $actor, $at);
        });
    }

    /**
     * @param  list<string>  $barcodes
     * @param  callable(string): void  $book
     */
    private function all(array $barcodes, callable $book): int
    {
        if ($barcodes === []) {
            throw new PaperEntryRejected(['Bitte mindestens eine Inventarnummer eintragen.']);
        }

        $problems = [];

        DB::beginTransaction();

        try {
            foreach ($barcodes as $barcode) {
                try {
                    DB::transaction(static fn () => $book($barcode));
                } catch (CirculationRuleViolation|LoanStateConflict $exception) {
                    $problems[] = $barcode.': '.$exception->getMessage();
                }
            }
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        if ($problems !== []) {
            DB::rollBack();

            throw new PaperEntryRejected($problems);
        }

        DB::commit();

        return count($barcodes);
    }

    /** @throws PaperEntryRejected */
    private function moment(string $date): CarbonImmutable
    {
        $date = trim($date);
        $now = $this->clock->now();

        try {
            $day = CarbonImmutable::createFromFormat('!Y-m-d', $date, $this->clock->timezone());
        } catch (InvalidFormatException) {
            $day = false;
        }

        if ($day === false || $day->format('Y-m-d') !== $date) {
            throw new PaperEntryRejected(['Bitte ein gültiges Datum angeben.']);
        }

        if ($day->toDateString() > $now->toDateString()) {
            throw new PaperEntryRejected(['Das Datum darf nicht in der Zukunft liegen.']);
        }

        if ($day->lessThan($now->startOfDay()->subDays(self::MAX_DAYS_BACK))) {
            throw new PaperEntryRejected(['Das Datum liegt mehr als '.self::MAX_DAYS_BACK.' Tage zurück.']);
        }

        // Heute gilt die aktuelle Uhrzeit, frühere Tage zählen mittags.
        return $day->toDateString() === $now->toDateString() ? $now : $day->setTime(12, 0);
    }

    /**
     * @param  list<string>  $barcodes
     * @return list<string>
     */
    private function clean(array $barcodes): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (string $barcode): string => trim($barcode), $barcodes),
            static fn (string $barcode): bool => $barcode !== '',
        )));
    }
}
