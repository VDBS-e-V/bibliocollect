<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\CounterTransactionFailed;
use App\Modules\Circulation\Mail\TransactionReceiptMail;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\LoanTransaction;
use App\Modules\Circulation\Services\CirculationRuleEvaluator;
use App\Modules\Circulation\Services\CounterTransactionService;
use App\Modules\Circulation\Services\LoanPolicy;
use App\Modules\Circulation\Services\ReservationBlockChecker;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Ausleihterminal wie eine Kasse: Person wählen, Ausleihen, Verlängerungen und Rückgaben sammeln, dann in einem Schritt
 * bestätigen. Der Vorgang liegt bis dahin nur in der Sitzung; gebucht wird erst beim Bestätigen. Danach gibt es einen Beleg
 * zum Drucken oder Versenden.
 */
final class PosTerminalController
{
    private const SESSION_KEY = 'pos.terminal';

    public function show(
        Request $request,
        LoanPolicy $policy,
        CirculationRuleEvaluator $rules,
        ReservationBlockChecker $blocks,
    ): Response {
        $draft = $this->draft($request);
        $patron = $this->patron($draft);
        $term = trim((string) $request->query('suche', ''));

        $openLoans = collect();
        $renewable = [];

        if ($patron instanceof Patron) {
            $openLoans = Loan::query()
                ->where('patron_id', $patron->getKey())
                ->whereNull('returned_at')
                ->with('copy.edition.title')
                ->orderBy('due_on')
                ->get();

            foreach ($openLoans as $loan) {
                $renewable[(string) $loan->getKey()] = $rules->renewalViolations($loan, $patron, $loan->copy, $blocks->blocksRenewal($loan, $loan->copy));
            }
        }

        $results = $term !== '' && ! $patron instanceof Patron
            ? Patron::query()
                ->with('schoolClass')
                ->where('status', PatronStatus::Active->value)
                ->where(static function ($query) use ($term): void {
                    $like = '%'.$term.'%';
                    $query->where('last_name', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('library_number', 'like', $like);
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(12)
                ->get()
            : collect();

        return response()
            ->view('pages.surfaces.pos.terminal.index', [
                'patron' => $patron,
                'mode' => $draft['mode'],
                'items' => $draft['items'],
                'openLoans' => $openLoans,
                'renewable' => $renewable,
                'results' => $results,
                'term' => $term,
                'maxOpenLoans' => $patron instanceof Patron ? $policy->maxOpenLoans($patron) : 0,
                'inCart' => collect($draft['items'])->pluck('loan_id')->filter()->all(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function selectPatron(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:80'], 'patron_id' => ['nullable', 'string', 'max:40']]);
        $draft = $this->draft($request);

        $patron = null;

        if (! empty($data['patron_id'])) {
            $patron = Patron::query()->find($data['patron_id']);
        } elseif (! empty($data['code'])) {
            $patron = Patron::query()->whereRaw('lower(library_number) = ?', [mb_strtolower(trim((string) $data['code']))])->first();

            if (! $patron instanceof Patron) {
                return redirect()->route('pos.terminal', ['suche' => trim((string) $data['code'])]);
            }
        }

        if (! $patron instanceof Patron || $patron->status !== PatronStatus::Active) {
            return redirect()->route('pos.terminal')->with('terminal_error', 'Diese Person gibt es nicht oder ihr Ausleihkonto ist nicht aktiv.');
        }

        return $this->activate($request, $draft, $patron);
    }

    /** @param  array{patron_id: ?string, mode: string, items: list<array<string, mixed>>}  $draft */
    private function activate(Request $request, array $draft, Patron $patron): RedirectResponse
    {
        if ($draft['patron_id'] !== (string) $patron->getKey()) {
            // Positionen gehören zu einer Person; bei einem Wechsel beginnt ein neuer Vorgang.
            $draft = ['patron_id' => (string) $patron->getKey(), 'mode' => $draft['mode'], 'items' => []];
        }

        $request->session()->put(self::SESSION_KEY, $draft);

        $notice = $patron->blocked_at !== null ? 'Achtung: Das Ausleihkonto ist gesperrt'.($patron->blocked_reason ? ' ('.$patron->blocked_reason.')' : '').'.' : null;

        return redirect()->route('pos.terminal')->with('terminal_notice', $notice);
    }

    public function clearPatron(Request $request): RedirectResponse
    {
        $draft = $this->draft($request);
        $request->session()->put(self::SESSION_KEY, ['patron_id' => null, 'mode' => $draft['mode'], 'items' => []]);

        return redirect()->route('pos.terminal');
    }

    public function mode(Request $request): RedirectResponse
    {
        $mode = $request->validate(['mode' => ['required', 'in:checkout,renew,return']])['mode'];
        $draft = $this->draft($request);
        $draft['mode'] = $mode;
        $request->session()->put(self::SESSION_KEY, $draft);

        return redirect()->route('pos.terminal');
    }

    public function scan(Request $request, CounterTransactionService $service): RedirectResponse
    {
        $code = trim((string) $request->validate(['code' => ['required', 'string', 'max:80']], ['code.required' => 'Bitte einen Barcode scannen oder eingeben.'])['code']);
        $draft = $this->draft($request);
        $patron = $this->patron($draft);

        // Ein Bibliotheksausweis im Scanfeld wählt die Person, auch mitten im Vorgang.
        if (! $patron instanceof Patron || $draft['items'] === []) {
            $byNumber = Patron::query()->whereRaw('lower(library_number) = ?', [mb_strtolower($code)])->first();

            if ($byNumber instanceof Patron && $byNumber->status === PatronStatus::Active) {
                return $this->activate($request, $draft, $byNumber);
            }
        }

        try {
            $item = match ($draft['mode']) {
                'return' => $service->returnItem($patron, $code, $draft['items']),
                'renew' => $service->renewItemByBarcode($patron, $code, $draft['items']),
                default => $service->checkoutItem($patron, $code, $draft['items']),
            };
        } catch (CirculationRuleViolation $exception) {
            return redirect()->route('pos.terminal')->with('terminal_error', $exception->getMessage());
        }

        $draft['items'][] = $item;
        $request->session()->put(self::SESSION_KEY, $draft);

        return redirect()->route('pos.terminal');
    }

    public function renew(Request $request, string $loanId, CounterTransactionService $service): RedirectResponse
    {
        $draft = $this->draft($request);

        try {
            $draft['items'][] = $service->renewItem($this->patron($draft), $loanId, $draft['items']);
        } catch (CirculationRuleViolation $exception) {
            return redirect()->route('pos.terminal')->with('terminal_error', $exception->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, $draft);

        return redirect()->route('pos.terminal');
    }

    public function removeItem(Request $request, int $index): RedirectResponse
    {
        $draft = $this->draft($request);
        unset($draft['items'][$index]);
        $draft['items'] = array_values($draft['items']);
        $request->session()->put(self::SESSION_KEY, $draft);

        return redirect()->route('pos.terminal');
    }

    public function discard(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('pos.terminal')->with('terminal_notice', 'Der Vorgang wurde verworfen. Es wurde nichts gebucht.');
    }

    public function confirm(Request $request, CounterTransactionService $service): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $draft = $this->draft($request);

        try {
            $transaction = $service->confirm($this->patron($draft), $draft['items'], $actor);
        } catch (CounterTransactionFailed $exception) {
            return redirect()->route('pos.terminal')->with('terminal_error', $exception->getMessage());
        }

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('pos.terminal.receipt', ['transactionId' => $transaction->getKey()]);
    }

    public function receipt(string $transactionId): Response
    {
        $transaction = LoanTransaction::query()->with('patron.schoolClass')->findOrFail($transactionId);

        return response()
            ->view('pages.surfaces.pos.terminal.receipt', [
                'transaction' => $transaction,
                'recipient' => $this->recipient($transaction),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function mailReceipt(Request $request, string $transactionId, AuditRecorder $audit): RedirectResponse
    {
        $transaction = LoanTransaction::query()->with('patron')->findOrFail($transactionId);
        $address = $request->validate(['email' => ['required', 'email:rfc', 'max:255']], ['email.required' => 'Bitte eine E-Mail-Adresse angeben.'])['email'];

        try {
            Mail::to($address)->send(new TransactionReceiptMail($transaction));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['email' => 'Die E-Mail konnte nicht verschickt werden. Bitte die Adresse und die Mail-Einstellungen prüfen.']);
        }

        $transaction->forceFill(['emailed_to' => $address, 'emailed_at' => now()])->save();
        $audit->record('circulation.transaction.emailed', "Beleg {$transaction->number} per E-Mail verschickt.", $transaction);

        return redirect()->route('pos.terminal.receipt', ['transactionId' => $transaction->getKey()])->with('terminal_notice', 'Der Beleg wurde an '.$address.' geschickt.');
    }

    /** @return array{patron_id: ?string, mode: string, items: list<array<string, mixed>>} */
    private function draft(Request $request): array
    {
        $draft = $request->session()->get(self::SESSION_KEY);

        return [
            'patron_id' => is_array($draft) && is_string($draft['patron_id'] ?? null) ? $draft['patron_id'] : null,
            'mode' => is_array($draft) && in_array($draft['mode'] ?? null, ['checkout', 'renew', 'return'], true) ? $draft['mode'] : 'checkout',
            'items' => is_array($draft) && is_array($draft['items'] ?? null) ? array_values($draft['items']) : [],
        ];
    }

    /** @param  array{patron_id: ?string, mode: string, items: list<array<string, mixed>>}  $draft */
    private function patron(array $draft): ?Patron
    {
        return $draft['patron_id'] !== null ? Patron::query()->with('schoolClass')->find($draft['patron_id']) : null;
    }

    private function recipient(LoanTransaction $transaction): ?string
    {
        $patron = $transaction->patron;

        if ($patron === null) {
            return null;
        }

        if (is_string($patron->email) && $patron->email !== '') {
            return $patron->email;
        }

        $user = User::query()->where('patron_id', $patron->getKey())->whereNotNull('email_verified_at')->first();

        return $user?->email;
    }
}
