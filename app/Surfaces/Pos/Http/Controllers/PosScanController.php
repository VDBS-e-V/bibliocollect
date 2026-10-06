<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Services\ReservationQueueService;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Ein Scanfeld für alles: Eine Bibliotheksnummer öffnet das Ausleihkonto (dort wird ausgeliehen), der Barcode eines
 * ausgeliehenen Exemplars bucht die Rückgabe.
 */
final class PosScanController
{
    public function __invoke(Request $request, ReturnLoanAction $return, ReservationQueueService $queue): RedirectResponse
    {
        $code = trim((string) $request->validate(['code' => ['required', 'string', 'max:80']], ['code.required' => 'Bitte einen Code scannen oder eingeben.'])['code']);
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        $copy = Copy::query()->where('barcode', $code)->first();

        if ($copy instanceof Copy) {
            $loan = Loan::query()->where('copy_id', $copy->getKey())->whereNull('returned_at')->first();

            if (! $loan instanceof Loan) {
                return redirect()->route('pos.home')->with('workspace_error', "Das Exemplar {$copy->barcode} ist nicht ausgeliehen.");
            }

            try {
                $return->execute($loan, $actor);
            } catch (LoanStateConflict $exception) {
                return redirect()->route('pos.home')->with('workspace_error', $exception->getMessage());
            }

            return redirect()->route('pos.home')->with('workspace_success', "Rückgabe von {$copy->barcode} erfasst.".$queue->holdNotice($copy));
        }

        $patron = Patron::query()->whereRaw('lower(library_number) = ?', [mb_strtolower($code)])->first();

        if ($patron instanceof Patron) {
            $request->session()->put('pos.terminal', ['patron_id' => (string) $patron->getKey(), 'items' => []]);

            return redirect()->route('pos.terminal.person');
        }

        return redirect()->route('pos.home')->with('workspace_error', "Zu „{$code}“ gibt es kein Exemplar und kein Ausleihkonto.");
    }
}
