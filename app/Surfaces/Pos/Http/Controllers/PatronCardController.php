<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Foundation\Support\CsvExport;
use App\Modules\Patrons\Actions\BlockPatronCardAction;
use App\Modules\Patrons\Actions\GeneratePatronCardsAction;
use App\Modules\Patrons\Enums\CardBlockReason;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\Patrons\Models\PatronCardMotif;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Nicht personalisierte Bibliotheksausweise auf Avery Zweckform C32016 (85 × 54 mm, 2 × 5 je A4-Bogen):
 * Chargen erzeugen, Vorder- und Rückseiten drucken, als CSV für Kartendruck exportieren, Ausweise sperren.
 */
final class PatronCardController
{
    public const PER_SHEET = 10;

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $term = trim((string) $request->query('q', ''));

        $cards = PatronCard::query()
            ->with('patron')
            ->when(CardStatus::tryFrom($status) !== null, static fn ($query) => $query->where('status', $status))
            ->when($term !== '', static function ($query) use ($term): void {
                $like = '%'.$term.'%';
                $query->where(static function ($inner) use ($like): void {
                    $inner->where('number', 'like', $like)->orWhereHas('patron', static function ($patron) use ($like): void {
                        $patron->where('last_name', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('library_number', 'like', $like);
                    });
                });
            })
            ->orderByDesc('batch')
            ->orderBy('number')
            ->limit(100)
            ->get();

        $batches = [];

        foreach (DB::table('patron_cards')->whereNotNull('batch')->groupBy('batch', 'status')->selectRaw('batch, status, count(*) as total, min(created_at) as created_at')->get() as $row) {
            $batches[(int) $row->batch]['counts'][(string) $row->status] = (int) $row->total;
            $batches[(int) $row->batch]['created_at'] = (string) $row->created_at;
        }

        krsort($batches);

        return response()
            ->view('pages.surfaces.pos.labels.cards-index', [
                'cards' => $cards,
                'batches' => $batches,
                'totals' => PatronCard::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
                'status' => $status,
                'term' => $term,
                'statuses' => CardStatus::cases(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function generate(Request $request, GeneratePatronCardsAction $action): RedirectResponse
    {
        $data = $request->validate(['count' => ['required', 'integer', 'between:1,1000']], [
            'count.required' => 'Bitte die Anzahl angeben.',
            'count.integer' => 'Die Anzahl muss eine ganze Zahl sein.',
            'count.between' => 'Die Anzahl muss zwischen 1 und 1000 liegen.',
        ]);

        $batch = $action->execute((int) $data['count']);

        return redirect()->route('pos.labels.cards')->with('status', "Charge {$batch} mit {$data['count']} Ausweisen erzeugt. Jetzt drucken oder exportieren.");
    }

    /** Druckeinstellungen einer Charge: Vorder- und Rückseiten mit Startposition und Verteilung der Motive. */
    public function batch(int $batch): Response|RedirectResponse
    {
        $free = $this->unassigned($batch)->count();

        if (PatronCard::query()->where('batch', $batch)->doesntExist()) {
            return redirect()->route('pos.labels.cards')->withErrors(['batch' => "Charge {$batch} gibt es nicht."]);
        }

        $motifs = PatronCardMotif::query()->usable()->get();

        return response()
            ->view('pages.surfaces.pos.labels.cards-batch', [
                'batch' => $batch,
                'free' => $free,
                'motifs' => $motifs,
                'unassigned' => $this->unassigned($batch)->whereNull('motif_id')->count(),
                'assigned' => $this->unassigned($batch)->whereNotNull('motif_id')->count(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * Anteile der Motive in Prozent nach der Wahl für diesen Druck (normal = 1, mehr = 2, auslassen = 0).
     *
     * @param  Collection<int, PatronCardMotif>  $motifs
     * @param  array<string, string>  $choices  Motiv-Id zu normal|more|skip, fehlende Motive zählen als normal
     * @return array<string, float>
     */
    public static function shares(Collection $motifs, array $choices = []): array
    {
        $weights = $motifs->mapWithKeys(static fn (PatronCardMotif $motif): array => [(string) $motif->getKey() => PatronCardMotif::WEIGHTS[$choices[(string) $motif->getKey()] ?? 'normal'] ?? 1]);
        $total = $weights->sum();

        if ($total === 0) {
            return [];
        }

        return $weights->filter(static fn (int $weight): bool => $weight > 0)->map(static fn (int $weight): float => $weight * 100 / $total)->all();
    }

    /**
     * Druck einer Charge: beidseitig (je Bogen eine Vorder- und eine Rückseite hintereinander, für den Duplexdruck mit Wenden an der
     * langen Kante) oder einseitig (erst alle Vorderseiten, dann alle Rückseiten, der Bogen wird von Hand gewendet). Jeder Ausweis hat ein Motiv für beide Seiten: Es wird beim ersten Druck
     * nach der Wahl im Druckmenü (je Motiv normal, mehr, auslassen; gilt nur für diesen Druck) zugeteilt und gespeichert, damit Vorder-, Rückseite und Nachdrucke
     * übereinstimmen. Drucken der Vorderseite markiert neue Ausweise als „Im Druck“.
     */
    public function print(Request $request, int $batch): Response|RedirectResponse
    {
        $data = $request->validate([
            'side' => ['required', Rule::in(['beide', 'einseitig', 'vorder', 'rueck'])],
            'start' => ['nullable', 'integer', 'between:1,'.self::PER_SHEET],
            'neu' => ['nullable', 'boolean'],
            'verteilung' => ['nullable', 'array'],
            'verteilung.*' => ['nullable', Rule::in(array_keys(PatronCardMotif::DISTRIBUTIONS))],
        ]);

        $cards = $this->unassigned($batch)->orderBy('number')->with('motif')->get();

        if ($cards->isEmpty()) {
            return redirect()->route('pos.labels.cards')->withErrors(['batch' => "In Charge {$batch} gibt es keine freien Ausweise zum Drucken."]);
        }

        // Ausweise mit Motiv behalten es, außer „neu verteilen“ ist gewählt (zum Beispiel nach einem Probedruck).
        $open = $request->boolean('neu') ? $cards : $cards->whereNull('motif_id');
        $motifs = PatronCardMotif::query()->usable()->get();
        $shares = self::shares($motifs, array_map('strval', (array) ($data['verteilung'] ?? [])));

        if ($open->isNotEmpty() && $shares === [] && $motifs->isNotEmpty()) {
            return redirect()->route('pos.labels.cards.batch', ['batch' => $batch])->withErrors(['batch' => 'Mindestens ein Motiv muss vorkommen: Es dürfen nicht alle Motive ausgelassen werden.']);
        }

        if ($open->isNotEmpty() && $shares !== []) {
            $queue = $this->designQueue($motifs->keyBy(static fn (PatronCardMotif $motif): string => (string) $motif->getKey()), $shares, $open->count());

            DB::transaction(static function () use ($open, $queue): void {
                foreach ($open as $card) {
                    $motif = array_shift($queue);
                    $card->forceFill(['motif_id' => $motif?->getKey()])->save();
                    $card->setRelation('motif', $motif);
                }
            });
        }

        $skip = max(0, ((int) ($data['start'] ?? 1)) - 1);

        // Platzbelegung: vorn Ausweis plus Motiv, hinten nur das Motiv derselben Karte.
        $slots = array_fill(0, $skip, null);

        foreach ($cards as $card) {
            $slots[] = ['card' => $card, 'motif' => $card->motif];
        }

        $sheets = array_map(static fn (array $sheet): array => array_pad($sheet, self::PER_SHEET, null), array_chunk($slots, self::PER_SHEET));
        $sides = in_array($data['side'], ['beide', 'einseitig'], true) ? ['vorder', 'rueck'] : [$data['side']];
        $pages = [];

        if ($data['side'] === 'einseitig') {
            // Einseitiger Druck: erst alle Vorderseiten, dann alle Rückseiten (Bogen von Hand wenden).
            foreach ($sides as $side) {
                foreach ($sheets as $sheet) {
                    $pages[] = ['side' => $side, 'slots' => $side === 'vorder' ? $sheet : $this->mirrored($sheet)];
                }
            }
        } else {
            foreach ($sheets as $sheet) {
                foreach ($sides as $side) {
                    $pages[] = ['side' => $side, 'slots' => $side === 'vorder' ? $sheet : $this->mirrored($sheet)];
                }
            }
        }

        if (in_array('vorder', $sides, true)) {
            $this->unassigned($batch)->where('status', CardStatus::Generated->value)->update(['status' => CardStatus::InPrint->value, 'printed_at' => now()]);
        }

        return response()
            ->view('pages.surfaces.pos.labels.cards-print', [
                'pages' => $pages,
                'mode' => $data['side'],
                'count' => $cards->count(),
                'batch' => $batch,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * Rückseite eines Bogens: Wenden an der langen Kante, links und rechts tauschen. Bedruckt wird nur, was vorn einen Ausweis hat.
     *
     * @param  list<array{card: PatronCard, motif: PatronCardMotif|null}|null>  $sheet
     * @return list<array{card: PatronCard, motif: PatronCardMotif|null}|null>
     */
    private function mirrored(array $sheet): array
    {
        $mirrored = array_fill(0, self::PER_SHEET, null);

        foreach ($sheet as $index => $slot) {
            $mirrored[$index ^ 1] = $slot;
        }

        return $mirrored;
    }

    /** Nummernliste für einen Kartendruck. Markiert die erzeugten Ausweise der Charge als „Im Druck“. */
    public function export(int $batch): StreamedResponse|RedirectResponse
    {
        $cards = PatronCard::query()->where('batch', $batch)->orderBy('number')->get();

        if ($cards->isEmpty()) {
            return redirect()->route('pos.labels.cards')->withErrors(['batch' => "Charge {$batch} gibt es nicht."]);
        }

        PatronCard::query()->where('batch', $batch)->where('status', CardStatus::Generated->value)->update(['status' => CardStatus::InPrint->value, 'printed_at' => now()]);

        $cards = PatronCard::query()->where('batch', $batch)->orderBy('number')->get();

        return response()->streamDownload(static function () use ($cards): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            CsvExport::put($out, ['Ausweisnummer', 'Charge', 'Status'], ';');

            foreach ($cards as $card) {
                CsvExport::put($out, [$card->number, $card->batch, $card->status->label()], ';');
            }

            fclose($out);
        }, "ausweise-charge-{$batch}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Die Ausweise sind da (zum Beispiel Plastikkarten aus dem Druck): „Im Druck“ wird zu „Verfügbar“. */
    public function available(int $batch): RedirectResponse
    {
        $updated = PatronCard::query()->where('batch', $batch)->whereIn('status', [CardStatus::Generated->value, CardStatus::InPrint->value])->update(['status' => CardStatus::Available->value]);

        return redirect()->route('pos.labels.cards')->with('status', "{$updated} Ausweise der Charge {$batch} sind jetzt verfügbar.");
    }

    public function block(Request $request, string $cardId, BlockPatronCardAction $action): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', Rule::in(['lost', 'defective', 'withdrawn'])]], ['reason.required' => 'Bitte einen Grund wählen.']);
        $card = PatronCard::query()->findOrFail($cardId);

        $action->execute($card, CardBlockReason::from($data['reason']));

        return back()->with('status', 'Ausweis '.$card->number.' ist gesperrt.');
    }

    /**
     * Motive für $total Plätze: Anteile in Prozent, Rundung nach größtem Rest, dann gemischt.
     *
     * @param  Collection<string, PatronCardMotif>  $designs  nach Id
     * @param  array<string, float>  $shares
     * @return list<PatronCardMotif|null>
     */
    private function designQueue(Collection $designs, array $shares, int $total): array
    {
        if ($designs->isEmpty()) {
            return array_fill(0, $total, null);
        }

        $counts = [];
        $remainders = [];

        foreach ($shares as $id => $percent) {
            $exact = $total * $percent / 100;
            $counts[$id] = (int) floor($exact);
            $remainders[$id] = $exact - $counts[$id];
        }

        arsort($remainders);

        foreach (array_keys($remainders) as $id) {
            if (array_sum($counts) >= $total) {
                break;
            }

            if ($shares[$id] > 0) {
                $counts[$id]++;
            }
        }

        $queue = [];

        foreach ($counts as $id => $count) {
            for ($i = 0; $i < $count; $i++) {
                $queue[] = $designs->get($id);
            }
        }

        shuffle($queue);

        return $queue;
    }

    /** @return Builder<PatronCard> */
    private function unassigned(int $batch)
    {
        return PatronCard::query()->where('batch', $batch)->whereIn('status', CardStatus::unassignedValues());
    }
}
