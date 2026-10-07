<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Modules\Catalog\Models\Edition;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Patrons\Models\Patron;

/**
 * Leihfrist und Obergrenze gleichzeitiger Ausleihen. Die Werte stehen in `config/circulation.php` und gelten je Art
 * des Ausleihkontos (Schüler:in, Lehrkraft, Mitarbeiter:in); die Leihfrist lässt sich zusätzlich je Medientyp festlegen.
 */
final class LoanPolicy
{
    /** Leihfrist in Tagen. Der Medientyp hat Vorrang vor der Art des Ausleihkontos. */
    public function periodDays(Patron $patron, ?Edition $edition = null): int
    {
        $mediaType = $edition?->media_type;
        $byMedia = (array) config('circulation.loan_periods.by_media_type', []);

        if (is_string($mediaType) && isset($byMedia[mb_strtolower($mediaType)])) {
            return max(1, (int) $byMedia[mb_strtolower($mediaType)]);
        }

        $byKind = (array) config('circulation.loan_periods.by_kind', []);

        return max(1, (int) ($byKind[$patron->kind->value] ?? config('circulation.default_loan_period_days', 14)));
    }

    /**
     * Wartezeit-Grenze einer Vormerkung: Leihfrist + eine Verlängerung + Puffer (alles aus den Regeln, also dynamisch).
     *
     * @return array{loan: int, renewal: int, buffer: int, total: int}
     */
    public function reservationWindow(Patron $patron, ?Edition $edition = null): array
    {
        $loan = $this->periodDays($patron, $edition);
        $renewal = max(1, (int) (config('circulation.renewal_period_days') ?? $loan));
        $buffer = max(0, (int) config('circulation.reservation_buffer_days', 7));

        return ['loan' => $loan, 'renewal' => $renewal, 'buffer' => $buffer, 'total' => $loan + $renewal + $buffer];
    }

    /** Höchstzahl gleichzeitig ausgeliehener Medien; 0 oder weniger bedeutet „unbegrenzt“. */
    public function maxOpenLoans(Patron $patron): int
    {
        $byKind = (array) config('circulation.max_open_loans.by_kind', []);

        return (int) ($byKind[$patron->kind->value] ?? config('circulation.max_open_loans.default', 5));
    }

    public function openLoanCount(Patron $patron): int
    {
        return Loan::query()->where('patron_id', $patron->getKey())->whereNull('returned_at')->count();
    }
}
