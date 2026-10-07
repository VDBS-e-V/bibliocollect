<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Patrons\Actions\BlockPatronCardAction;
use App\Modules\Patrons\Actions\GeneratePatronCardsAction;
use App\Modules\Patrons\Enums\CardBlockReason;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\Patrons\Models\PatronCardDesign;
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

        $front = PatronCardDesign::query()->forSide(PatronCardDesign::FRONT)->where('is_active', true)->get();
        $back = PatronCardDesign::query()->forSide(PatronCardDesign::BACK)->where('is_active', true)->get();

        return response()
            ->view('pages.surfaces.pos.labels.cards-batch', [
                'batch' => $batch,
                'free' => $free,
                'front' => $front,
                'back' => $back,
                'frontShares' => self::evenShares($front),
                'backShares' => self::evenShares($back),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /**
     * Gleichverteilung in ganzen Prozent, die zusammen 100 ergeben (bei 3 Motiven 34 / 33 / 33).
     *
     * @param  Collection<int, PatronCardDesign>  $designs
     * @return array<string, int>
     */
    public static function evenShares(Collection $designs): array
    {
        $count = $designs->count();

        if ($count === 0) {
            return [];
        }

        $shares = [];
        $rest = 100 % $count;

        foreach ($designs->values() as $index => $design) {
            $shares[(string) $design->getKey()] = intdiv(100, $count) + ($index < $rest ? 1 : 0);
        }

        return $shares;
    }

    /** Vorder- oder Rückseiten einer Charge. Drucken der Vorderseite markiert neue Ausweise als „Im Druck“. */
    public function print(Request $request, int $batch): Response|RedirectResponse
    {
        $data = $request->validate([
            'side' => ['required', Rule::in(['vorder', 'rueck'])],
            'start' => ['nullable', 'integer', 'between:1,'.self::PER_SHEET],
            'motiv' => ['nullable', 'array'],
            'motiv.*' => ['nullable', 'integer', 'between:0,100'],
        ], [
            'motiv.*.integer' => 'Die Prozentwerte der Motive müssen ganze Zahlen sein.',
            'motiv.*.between' => 'Die Prozentwerte der Motive müssen zwischen 0 und 100 liegen.',
        ]);

        $front = $data['side'] === 'vorder';
        $designs = PatronCardDesign::query()->forSide($front ? PatronCardDesign::FRONT : PatronCardDesign::BACK)->where('is_active', true)->get();

        // Ohne Angabe gleichverteilt; sonst genau die eingegebenen Prozentwerte (einmalig, sie werden nicht gespeichert).
        $shares = isset($data['motiv'])
            ? $designs->mapWithKeys(static fn (PatronCardDesign $design): array => [(string) $design->getKey() => (int) ($data['motiv'][$design->getKey()] ?? 0)])->all()
            : self::evenShares($designs);

        if ($designs->isNotEmpty() && array_sum($shares) !== 100) {
            return redirect()->route('pos.labels.cards.batch', ['batch' => $batch])->withErrors(['batch' => 'Die Verteilung der Motive muss zusammen 100 % ergeben (aktuell '.array_sum($shares).' %).']);
        }

        $cards = $this->unassigned($batch)->orderBy('number')->get();

        if ($cards->isEmpty()) {
            return redirect()->route('pos.labels.cards')->withErrors(['batch' => "In Charge {$batch} gibt es keine freien Ausweise zum Drucken."]);
        }

        $skip = max(0, ((int) ($data['start'] ?? 1)) - 1);
        $queue = $this->designQueue($designs->keyBy(static fn (PatronCardDesign $design): string => (string) $design->getKey()), $shares, $cards->count());

        // Platzbelegung: vorn Ausweis plus Motiv, hinten nur das Motiv.
        $slots = array_fill(0, $skip, null);

        foreach ($cards as $card) {
            $slots[] = ['card' => $card, 'design' => array_shift($queue)];
        }

        $sheets = array_map(static fn (array $sheet): array => array_pad($sheet, self::PER_SHEET, null), array_chunk($slots, self::PER_SHEET));

        if ($front) {
            $this->unassigned($batch)->where('status', CardStatus::Generated->value)->update(['status' => CardStatus::InPrint->value, 'printed_at' => now()]);
        } else {
            // Wenden an der langen Kante: links und rechts tauschen. Bedruckt wird nur, was vorn einen Ausweis hat.
            // Die Motive der Rückseite sind unabhängig von den Vorderseiten, deshalb neu verteilt.
            $backQueue = $this->designQueue($designs->keyBy(static fn (PatronCardDesign $design): string => (string) $design->getKey()), $shares, $cards->count());

            foreach ($sheets as $sheetIndex => $sheet) {
                $mirrored = array_fill(0, self::PER_SHEET, null);

                foreach ($sheet as $index => $slot) {
                    $mirrored[$index ^ 1] = $slot !== null ? ['design' => array_shift($backQueue)] : null;
                }

                $sheets[$sheetIndex] = $mirrored;
            }
        }

        return response()
            ->view('pages.surfaces.pos.labels.cards-print', [
                'sheets' => $sheets,
                'side' => $data['side'],
                'count' => $cards->count(),
                'batch' => $batch,
            ])
            ->header('Cache-Control', 'private, no-store');
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
            fputcsv($out, ['Ausweisnummer', 'Charge', 'Status'], ';');

            foreach ($cards as $card) {
                fputcsv($out, [$card->number, $card->batch, $card->status->label()], ';');
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
     * @param  Collection<string, PatronCardDesign>  $designs  nach Id
     * @param  array<string, int>  $shares
     * @return list<PatronCardDesign|null>
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
