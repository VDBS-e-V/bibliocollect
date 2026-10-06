<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Beendet eine Ausleihe mit Problem: Das Exemplar kommt beschädigt zurück (`damaged`) oder gilt als verloren (`lost`).
 * Die Ausleihe ist danach nicht mehr offen (zählt also nicht mehr zur Obergrenze), das Exemplar bekommt den
 * entsprechenden Katalogstatus und ist damit nicht mehr ausleihbar.
 */
final readonly class ReportLoanProblemAction
{
    public const DAMAGED = 'damaged';

    public const LOST = 'lost';

    public function __construct(
        private BusinessClock $clock,
        private AuditRecorder $audit,
    ) {}

    public function execute(Loan $loan, string $problem, User $actor): Loan
    {
        if (! in_array($problem, [self::DAMAGED, self::LOST], true)) {
            throw new InvalidArgumentException('Unbekanntes Problem: '.$problem);
        }

        return DB::transaction(function () use ($loan, $problem, $actor): Loan {
            $snapshot = Loan::query()->findOrFail($loan->getKey());

            $copy = Copy::query()->whereKey($snapshot->copy_id)->lockForUpdate()->firstOrFail();
            $locked = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->returned_at !== null) {
                throw LoanStateConflict::alreadyReturned();
            }

            $locked->forceFill([
                'returned_at' => $this->clock->now(),
                'returned_by_user_id' => $actor->getKey(),
                'outcome' => $problem,
            ])->save();

            $copy->forceFill(['status' => $problem === self::LOST ? CopyStatus::Lost : CopyStatus::Damaged])->save();

            $this->audit->record(
                $problem === self::LOST ? 'circulation.loan.lost' : 'circulation.loan.damaged',
                $problem === self::LOST ? "Exemplar {$copy->barcode} als verloren gemeldet." : "Exemplar {$copy->barcode} beschädigt zurückgegeben.",
                $locked,
                ['patron_id' => (string) $locked->patron_id, 'copy_id' => (string) $copy->getKey()],
                (int) $actor->getKey(),
            );

            return $locked->load('copy.edition.title');
        });
    }
}
