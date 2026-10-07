<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Exceptions\CounterTransactionFailed;
use App\Modules\Circulation\Mail\TransactionReceiptMail;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\LoanTransaction;
use App\Modules\Circulation\Services\CirculationRuleEvaluator;
use App\Modules\Circulation\Services\CounterTransactionService;
use App\Modules\Circulation\Services\LoanPolicy;
use App\Modules\Circulation\Services\ReservationBlockChecker;
use App\Modules\Patrons\Actions\AssignPatronCardAction;
use App\Modules\Patrons\Actions\BlockPatronCardAction;
use App\Modules\Patrons\Enums\CardBlockReason;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Exceptions\PatronCardConflict;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\Patrons\Services\PatronCardLookup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Ausleihterminal wie eine Kasse in zwei Bildschirmen.
 *
 * Start: eine Person scannen (weiter zum Personenbildschirm) oder Rückgaben scannen (ohne Person sammeln).
 * Person: Übersicht aller ausgeliehenen Medien mit Verlängern und Zurückgeben, Scanfeld für weitere Ausleihen,
 * gemeinsam bestätigen. Bis zum Bestätigen liegt der Vorgang nur in der Sitzung; danach gibt es einen Beleg.
 */
final class PosTerminalController
{
    private const SESSION_KEY = 'pos.terminal';

    /** Ein gescannter, noch freier Ausweis wartet darauf, einer Person zugeordnet zu werden. */
    private const PENDING_CARD = 'pos.pending_card';

    // ---- Bildschirm 1: Start -----------------------------------------------------------------------------------

    public function start(Request $request): Response|RedirectResponse
    {
        $draft = $this->draft($request);
        $request->session()->forget(self::PENDING_CARD);

        if ($draft['patron_id'] !== null && $this->patron($draft) instanceof Patron) {
            return redirect()->route('pos.terminal.person');
        }

        $term = trim((string) $request->query('suche', ''));

        $results = $term !== ''
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
            ->view('pages.surfaces.pos.terminal.start', ['items' => $draft['items'], 'results' => $results, 'term' => $term])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Ein Scanfeld: Bibliotheksnummer (Person), Exemplar-Barcode (Rückgabe) oder ein Name zum Suchen. */
    public function startScan(Request $request, CounterTransactionService $service, PatronCardLookup $cards): RedirectResponse
    {
        $code = trim((string) $request->validate(['code' => ['required', 'string', 'max:80']], ['code.required' => 'Bitte einen Ausweis oder Barcode scannen oder einen Namen eingeben.'])['code']);
        $draft = $this->draft($request);

        $card = $cards->find($code);

        if ($card instanceof PatronCard) {
            return $this->scanCard($request, $draft, $card);
        }

        $byNumber = Patron::query()->whereRaw('lower(library_number) = ?', [mb_strtolower($code)])->first();

        if ($byNumber instanceof Patron) {
            return $this->activate($request, $draft, $byNumber);
        }

        if (Copy::query()->where('barcode', $code)->exists()) {
            try {
                $draft['items'][] = $service->returnItem(null, $code, $draft['items']);
            } catch (CirculationRuleViolation $exception) {
                return redirect()->route('pos.terminal')->with('terminal_error', $exception->getMessage());
            }

            $request->session()->put(self::SESSION_KEY, $draft);

            return redirect()->route('pos.terminal');
        }

        return redirect()->route('pos.terminal', ['suche' => $code]);
    }

    public function selectPatron(Request $request): RedirectResponse
    {
        $id = (string) $request->validate(['patron_id' => ['required', 'string', 'max:40']])['patron_id'];
        $patron = Patron::query()->find($id);

        if (! $patron instanceof Patron || $patron->status !== PatronStatus::Active) {
            return redirect()->route('pos.terminal')->with('terminal_error', 'Diese Person gibt es nicht oder ihr Ausleihkonto ist nicht aktiv.');
        }

        return $this->activate($request, $this->draft($request), $patron);
    }

    /** @param  array{patron_id: ?string, items: list<array<string, mixed>>}  $draft */
    private function scanCard(Request $request, array $draft, PatronCard $card): RedirectResponse
    {
        if ($card->status === CardStatus::Blocked) {
            $request->session()->forget(self::PENDING_CARD);

            return redirect()->route('pos.terminal')->with('terminal_error', 'Dieser Ausweis ist gesperrt'.($card->block_reason ? ' ('.$card->block_reason->label().')' : '').'. Bitte einen anderen verwenden.');
        }

        if ($card->status === CardStatus::Assigned && $card->patron instanceof Patron) {
            $request->session()->forget(self::PENDING_CARD);

            return $this->activate($request, $draft, $card->patron);
        }

        $request->session()->put(self::PENDING_CARD, $card->number);

        return redirect()->route('pos.terminal.card.register');
    }

    /** Registrierung eines neuen Ausweises: Person suchen und auswählen. */
    public function register(Request $request): Response|RedirectResponse
    {
        $number = $request->session()->get(self::PENDING_CARD);
        $card = is_string($number) ? PatronCard::query()->where('number', $number)->first() : null;

        if (! $card instanceof PatronCard || ! $card->status->isUnassigned()) {
            $request->session()->forget(self::PENDING_CARD);

            return redirect()->route('pos.terminal')->with('terminal_error', 'Der Ausweis ist nicht mehr frei. Bitte erneut scannen.');
        }

        $term = trim((string) $request->query('q', ''));

        $results = $term !== ''
            ? Patron::query()
                ->with('schoolClass')
                ->where('status', PatronStatus::Active->value)
                ->where(static function ($query) use ($term): void {
                    $like = '%'.$term.'%';
                    $query->where('last_name', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('library_number', 'like', $like);
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(15)
                ->get()
            : collect();

        $current = PatronCard::query()
            ->whereIn('patron_id', $results->pluck('id')->all())
            ->where('status', CardStatus::Assigned->value)
            ->pluck('number', 'patron_id')
            ->all();

        return response()
            ->view('pages.surfaces.pos.terminal.card-register', ['card' => $card, 'term' => $term, 'results' => $results, 'current' => $current])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Ordnet den gescannten Ausweis der gewählten Person zu und öffnet deren Ausleihbildschirm. */
    public function claimCard(Request $request): RedirectResponse
    {
        $id = (string) $request->validate(['patron_id' => ['required', 'string', 'max:40']])['patron_id'];
        $number = $request->session()->get(self::PENDING_CARD);
        $patron = Patron::query()->find($id);

        if (! is_string($number)) {
            return redirect()->route('pos.terminal')->with('terminal_error', 'Es wartet kein neuer Ausweis auf die Zuordnung. Bitte den Ausweis erneut scannen.');
        }

        if (! $patron instanceof Patron || $patron->status !== PatronStatus::Active) {
            return redirect()->route('pos.terminal.card.register')->with('terminal_error', 'Diese Person gibt es nicht oder ihr Ausleihkonto ist nicht aktiv.');
        }

        $request->session()->forget(self::PENDING_CARD);

        return $this->activate($request, $this->draft($request), $patron, $number);
    }

    // ---- Bildschirm 2: Person ----------------------------------------------------------------------------------

    public function person(
        Request $request,
        LoanPolicy $policy,
        CirculationRuleEvaluator $rules,
        ReservationBlockChecker $blocks,
    ): Response|RedirectResponse {
        $draft = $this->draft($request);
        $patron = $this->patron($draft);

        if (! $patron instanceof Patron) {
            return redirect()->route('pos.terminal');
        }

        $openLoans = Loan::query()
            ->where('patron_id', $patron->getKey())
            ->whereNull('returned_at')
            ->with('copy.edition.title')
            ->orderBy('due_on')
            ->get();

        $renewable = [];

        foreach ($openLoans as $loan) {
            $renewable[(string) $loan->getKey()] = $rules->renewalViolations($loan, $patron, $loan->copy, $blocks->blocksRenewal($loan, $loan->copy));
        }

        $queued = [];

        foreach ($draft['items'] as $item) {
            if (isset($item['loan_id'])) {
                $queued[(string) $item['loan_id']] = $item['type'];
            }
        }

        return response()
            ->view('pages.surfaces.pos.terminal.person', [
                'patron' => $patron,
                'items' => $draft['items'],
                'openLoans' => $openLoans,
                'renewable' => $renewable,
                'queued' => $queued,
                'maxOpenLoans' => $policy->maxOpenLoans($patron),
                'card' => PatronCard::query()->where('patron_id', $patron->getKey())->where('status', CardStatus::Assigned->value)->first(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Scan auf dem Personenbildschirm: eigene Ausleihe → Rückgabe, freies Exemplar → Ausleihe. */
    public function scan(Request $request, CounterTransactionService $service): RedirectResponse
    {
        $code = trim((string) $request->validate(['code' => ['required', 'string', 'max:80']], ['code.required' => 'Bitte einen Barcode scannen oder eingeben.'])['code']);
        $draft = $this->draft($request);
        $patron = $this->patron($draft);

        if (! $patron instanceof Patron) {
            return redirect()->route('pos.terminal');
        }

        try {
            $draft['items'][] = $service->scanItem($patron, $code, $draft['items']);
        } catch (CirculationRuleViolation $exception) {
            return redirect()->route('pos.terminal.person')->with('terminal_error', $exception->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, $draft);

        return redirect()->route('pos.terminal.person');
    }

    public function renew(Request $request, string $loanId, CounterTransactionService $service): RedirectResponse
    {
        $draft = $this->draft($request);

        try {
            $draft['items'][] = $service->renewItem($this->patron($draft), $loanId, $draft['items']);
        } catch (CirculationRuleViolation $exception) {
            return redirect()->route('pos.terminal.person')->with('terminal_error', $exception->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, $draft);

        return redirect()->route('pos.terminal.person');
    }

    public function returnLoan(Request $request, string $loanId, CounterTransactionService $service): RedirectResponse
    {
        $draft = $this->draft($request);
        $patron = $this->patron($draft);
        $loan = $patron instanceof Patron
            ? Loan::query()->where('patron_id', $patron->getKey())->whereNull('returned_at')->with('copy')->find($loanId)
            : null;

        if (! $loan instanceof Loan) {
            return redirect()->route('pos.terminal.person')->with('terminal_error', 'Diese Ausleihe gibt es für das gewählte Ausleihkonto nicht (mehr).');
        }

        try {
            $draft['items'][] = $service->returnItem($patron, $loan->copy->barcode, $draft['items']);
        } catch (CirculationRuleViolation $exception) {
            return redirect()->route('pos.terminal.person')->with('terminal_error', $exception->getMessage());
        }

        $request->session()->put(self::SESSION_KEY, $draft);

        return redirect()->route('pos.terminal.person');
    }

    /** Einen (neuen) Ausweis der Person am Bildschirm zuordnen; frühere Ausweise werden gesperrt. */
    public function assignCard(Request $request, AssignPatronCardAction $assign): RedirectResponse
    {
        $number = trim((string) $request->validate(['code' => ['required', 'string', 'max:40']], ['code.required' => 'Bitte den Ausweis scannen oder die Nummer eingeben.'])['code']);
        $patron = $this->patron($this->draft($request));

        if (! $patron instanceof Patron) {
            return redirect()->route('pos.terminal');
        }

        try {
            $replaced = $assign->execute($number, $patron);
        } catch (PatronCardConflict $exception) {
            return redirect()->route('pos.terminal.person')->with('terminal_error', $exception->getMessage());
        }

        return redirect()->route('pos.terminal.person')->with('terminal_notice', 'Ausweis '.$number.' ist zugeordnet.'.($replaced > 0 ? ' Der frühere Ausweis wurde gesperrt.' : ''));
    }

    /** Ausweis verloren: sperren. Die Person bekommt danach sofort einen neuen. */
    public function lostCard(Request $request, BlockPatronCardAction $block): RedirectResponse
    {
        $patron = $this->patron($this->draft($request));

        if (! $patron instanceof Patron) {
            return redirect()->route('pos.terminal');
        }

        $cards = PatronCard::query()->where('patron_id', $patron->getKey())->where('status', CardStatus::Assigned->value)->get();

        foreach ($cards as $card) {
            $block->execute($card, CardBlockReason::Lost);
        }

        return redirect()->route('pos.terminal.person')->with('terminal_notice', $cards->isEmpty()
            ? 'Es ist kein Ausweis zugeordnet.'
            : 'Der Ausweis ist gesperrt. Bitte jetzt einen neuen Ausweis scannen und zuordnen.');
    }

    public function cancelCard(Request $request): RedirectResponse
    {
        $request->session()->forget(self::PENDING_CARD);

        return redirect()->route('pos.terminal');
    }

    // ---- gemeinsam --------------------------------------------------------------------------------------------

    public function removeItem(Request $request, int $index): RedirectResponse
    {
        $draft = $this->draft($request);
        unset($draft['items'][$index]);
        $draft['items'] = array_values($draft['items']);
        $request->session()->put(self::SESSION_KEY, $draft);

        return $this->back($draft);
    }

    /** Verwirft den Vorgang und geht zurück zum Start. */
    public function discard(Request $request): RedirectResponse
    {
        $hadItems = $this->draft($request)['items'] !== [];
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('pos.terminal')->with('terminal_notice', $hadItems ? 'Der Vorgang wurde verworfen. Es wurde nichts gebucht.' : null);
    }

    public function confirm(Request $request, CounterTransactionService $service): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $draft = $this->draft($request);

        try {
            $transaction = $service->confirm($this->patron($draft), $draft['items'], $actor);
        } catch (CounterTransactionFailed $exception) {
            return $this->back($draft)->with('terminal_error', $exception->getMessage());
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

    /** @param  array{patron_id: ?string, items: list<array<string, mixed>>}  $draft */
    private function activate(Request $request, array $draft, Patron $patron, ?string $claimCard = null): RedirectResponse
    {
        if ($patron->status !== PatronStatus::Active) {
            return redirect()->route('pos.terminal')->with('terminal_error', 'Das Ausleihkonto ist nicht aktiv.');
        }

        // Schon gescannte Rückgaben gehen mit, wenn sie dieser Person gehören; sonst bleiben sie getrennt.
        if ($draft['items'] !== []) {
            $loanIds = array_filter(array_map(static fn (array $item): ?string => $item['loan_id'] ?? null, $draft['items']));
            $own = Loan::query()->whereIn('id', $loanIds)->where('patron_id', $patron->getKey())->count();

            if ($own !== count($draft['items'])) {
                return redirect()->route('pos.terminal')->with('terminal_error', 'Im Vorgang liegen noch Rückgaben anderer Personen. Bitte zuerst bestätigen oder verwerfen.');
            }
        }

        $notices = [];

        if ($claimCard !== null) {
            try {
                $replaced = app(AssignPatronCardAction::class)->execute($claimCard, $patron);
                $notices[] = 'Ausweis '.$claimCard.' ist jetzt '.$patron->displayName().' zugeordnet.'.($replaced > 0 ? ' Der frühere Ausweis wurde gesperrt.' : '');
            } catch (PatronCardConflict $exception) {
                return redirect()->route('pos.terminal')->with('terminal_error', $exception->getMessage());
            }
        }

        $request->session()->put(self::SESSION_KEY, ['patron_id' => (string) $patron->getKey(), 'items' => $draft['items']]);

        if ($patron->blocked_at !== null) {
            $notices[] = 'Achtung: Das Ausleihkonto ist gesperrt'.($patron->blocked_reason ? ' ('.$patron->blocked_reason.')' : '').'.';
        }

        return redirect()->route('pos.terminal.person')->with('terminal_notice', $notices === [] ? null : implode(' ', $notices));
    }

    /** @param  array{patron_id: ?string, items: list<array<string, mixed>>}  $draft */
    private function back(array $draft): RedirectResponse
    {
        return redirect()->route($draft['patron_id'] !== null ? 'pos.terminal.person' : 'pos.terminal');
    }

    /** @return array{patron_id: ?string, items: list<array<string, mixed>>} */
    private function draft(Request $request): array
    {
        $draft = $request->session()->get(self::SESSION_KEY);

        return [
            'patron_id' => is_array($draft) && is_string($draft['patron_id'] ?? null) ? $draft['patron_id'] : null,
            'items' => is_array($draft) && is_array($draft['items'] ?? null) ? array_values($draft['items']) : [],
        ];
    }

    /** @param  array{patron_id: ?string, items: list<array<string, mixed>>}  $draft */
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
