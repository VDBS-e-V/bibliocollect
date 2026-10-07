<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Actions\ReturnLoanAction;
use App\Modules\Circulation\Exceptions\LoanStateConflict;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Services\ReservationQueueService;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\Patrons\Services\PatronCardLookup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Ein Scanfeld für alles: Eine Bibliotheksnummer öffnet das Ausleihkonto (dort wird ausgeliehen), der Barcode eines
 * ausgeliehenen Exemplars bucht die Rückgabe.
 */
final class PosScanController
{
    public function __invoke(Request $request, ReturnLoanAction $return, ReservationQueueService $queue, PatronCardLookup $cards): RedirectResponse
    {
        $code = trim((string) $request->validate(['code' => ['required', 'string', 'max:80']], ['code.required' => 'Bitte einen Code scannen oder eingeben.'])['code']);
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        $copy = Copy::query()->where('barcode', $code)->first();

        if ($copy instanceof Copy) {
            $loan = Loan::query()->where('copy_id', $copy->getKey())->whereNull('returned_at')->first();

            if (! $loan instanceof Loan && in_array($copy->status, [CopyStatus::Lost, CopyStatus::Damaged], true)) {
                return redirect()->route('pos.copy-found', ['barcode' => $copy->barcode]);
            }

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

        $card = $cards->find($code);

        if ($card instanceof PatronCard) {
            if ($card->status === CardStatus::Blocked) {
                return redirect()->route('pos.home')->with('workspace_error', 'Dieser Ausweis ist gesperrt'.($card->block_reason ? ' ('.$card->block_reason->label().')' : '').'.');
            }

            if ($card->status === CardStatus::Assigned && $card->patron instanceof Patron && $card->patron->isActive()) {
                $request->session()->forget('pos.pending_card');
                $request->session()->put('pos.terminal', ['patron_id' => (string) $card->patron->getKey(), 'items' => []]);

                return redirect()->route('pos.terminal.person');
            }

            $request->session()->put('pos.pending_card', $card->number);

            return redirect()->route('pos.terminal.card.register');
        }

        $patron = Patron::query()->whereRaw('lower(library_number) = ?', [mb_strtolower($code)])->first();

        if ($patron instanceof Patron) {
            $request->session()->put('pos.terminal', ['patron_id' => (string) $patron->getKey(), 'items' => []]);

            return redirect()->route('pos.terminal.person');
        }

        return redirect()->route('pos.home')->with('workspace_error', "Zu „{$code}“ gibt es kein Exemplar und kein Ausleihkonto.");
    }
}
