<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Services\InventoryLabelPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Etiketten auf Vorrat: Inventarnummern werden im Voraus gedruckt (Vorlauf) und später beim Erfassen den Büchern
 * zugeordnet. Vor jedem Druck prüft das System, welche Nummern es schon gibt, und überspringt sie.
 * Die Option „Lücken füllen“ findet einmalig die Nummern der laufenden Reihe, die noch keinem Exemplar gehören.
 */
final class InventoryLabelController
{
    /** 3 × 8 Etiketten à 70 × 36 mm je Bogen. */
    public const PER_SHEET = 24;

    public function index(Request $request, InventoryLabelPlanner $planner): Response
    {
        $overview = $planner->overview();
        $mode = $request->query('modus') === 'luecken' ? 'luecken' : 'reihe';
        $reprint = $request->boolean('erneut');
        $plan = null;

        if ($request->query->has('plan')) {
            $plan = $this->plan($request, $planner, $mode, $reprint, $overview);
        }

        return response()
            ->view('pages.surfaces.pos.labels.stock', [
                'overview' => $overview,
                'mode' => $mode,
                'reprint' => $reprint,
                'plan' => $plan,
                'values' => [
                    'start' => (int) $request->query('start', ($overview['lastPrinted'] ?? $overview['highest'] ?? 0) + 1 ?: 1),
                    'count' => (int) $request->query('anzahl', 24),
                    'from' => (int) $request->query('von', $overview['lowest'] ?? 1),
                    'to' => (int) $request->query('bis', $overview['highest'] ?? 1),
                    'position' => (int) $request->query('startplatz', 1),
                ],
                'perSheet' => self::PER_SHEET,
                'max' => InventoryLabelPlanner::MAX_LABELS,
                'runs' => $planner->runs(10),
                'printedCount' => $planner->printedCount(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function print(Request $request, InventoryLabelPlanner $planner, AuditRecorder $audit): Response
    {
        $data = $request->validate([
            'modus' => ['required', 'in:reihe,luecken'],
            'start' => ['nullable', 'integer', 'between:1,9999999'],
            'anzahl' => ['nullable', 'integer', 'between:1,'.InventoryLabelPlanner::MAX_LABELS],
            'von' => ['nullable', 'integer', 'between:1,9999999'],
            'bis' => ['nullable', 'integer', 'between:1,9999999'],
            'startplatz' => ['nullable', 'integer', 'between:1,'.self::PER_SHEET],
        ]);

        $request->query->add($data);
        $overview = $planner->overview();
        $plan = $this->plan($request, $planner, $data['modus'], $request->boolean('erneut'), $overview);

        abort_if($plan['numbers'] === [], 422, 'Es gibt keine freien Nummern zum Drucken.');

        // Die Nummern sind jetzt auf Papier: Beim nächsten Vorratsdruck werden sie übersprungen.
        $userId = $request->user()?->getAuthIdentifier() !== null ? (int) $request->user()->getAuthIdentifier() : null;
        $runId = $planner->markPrinted($plan['numbers'], $userId, $data['modus']);
        $audit->record('catalog.labels.stock_printed', count($plan['numbers']).' Etiketten auf Vorrat gedruckt ('.$plan['numbers'][0].' bis '.$plan['numbers'][count($plan['numbers']) - 1].').', null, ['run' => $runId, 'count' => count($plan['numbers'])]);

        return response()
            ->view('pages.surfaces.pos.labels.stock-print', [
                'numbers' => $plan['numbers'],
                'skip' => max(0, ((int) ($data['startplatz'] ?? 1)) - 1),
                'perSheet' => self::PER_SHEET,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Einen Druckauftrag zurücknehmen: Seine Nummern werden wieder frei. */
    public function destroyRun(int $runId, InventoryLabelPlanner $planner, AuditRecorder $audit): RedirectResponse
    {
        $released = $planner->deleteRun($runId);

        abort_if($released < 0, 404);

        $audit->record('catalog.labels.run_deleted', "Druckauftrag {$runId} zurückgenommen, {$released} Nummern wieder frei.", null, ['run' => $runId, 'released' => $released]);

        return redirect()->route('pos.labels.stock')->with('stock_notice', "Der Druckauftrag ist gelöscht. {$released} Nummern gelten nicht mehr als gedruckt und werden wieder vergeben.");
    }

    /** Alle als gedruckt gespeicherten Nummern vergessen. */
    public function clear(InventoryLabelPlanner $planner, AuditRecorder $audit): RedirectResponse
    {
        $released = $planner->clearAll();

        $audit->record('catalog.labels.all_cleared', "Alle als gedruckt gespeicherten Nummern gelöscht ({$released}).", null, ['released' => $released]);

        return redirect()->route('pos.labels.stock')->with('stock_notice', "Alle {$released} als gedruckt gespeicherten Nummern sind gelöscht. Sie werden wieder vergeben.");
    }

    /**
     * @param  array{lowest: ?int, highest: ?int, lastPrinted: ?int, usedCount: int}  $overview
     * @return array{numbers: list<string>, used: list<string>, printed: list<string>, total: int, truncated: bool, last: ?int, sheets: int}
     */
    private function plan(Request $request, InventoryLabelPlanner $planner, string $mode, bool $reprint, array $overview): array
    {
        if ($mode === 'luecken') {
            $gaps = $planner->gaps((int) $request->input('von', $overview['lowest'] ?? 1), (int) $request->input('bis', $overview['highest'] ?? 1), $reprint);

            return ['numbers' => $gaps['numbers'], 'used' => [], 'printed' => $gaps['printed'], 'total' => $gaps['total'], 'truncated' => $gaps['truncated'], 'last' => null, 'sheets' => (int) ceil(count($gaps['numbers']) / self::PER_SHEET)];
        }

        $sequence = $planner->sequence((int) $request->input('start', 1), (int) $request->input('anzahl', 24), $reprint);

        return ['numbers' => $sequence['numbers'], 'used' => $sequence['used'], 'printed' => $sequence['printed'], 'total' => count($sequence['numbers']), 'truncated' => false, 'last' => $sequence['last'], 'sheets' => (int) ceil(count($sequence['numbers']) / self::PER_SHEET)];
    }

    /**
     * Druckt genau diese Nummern (zum Beispiel nach dem Umstellen alter Inventarnummern): Strichcode und Nummer, mehr nicht.
     * Anders als beim Vorratsdruck wird nichts übersprungen und nichts als gedruckt vermerkt, die Nummern gehören schon Exemplaren.
     */
    public function printNumbers(Request $request, AuditRecorder $audit): Response
    {
        $data = $request->validate([
            'numbers' => ['required', 'array', 'min:1', 'max:'.InventoryLabelPlanner::MAX_LABELS],
            'numbers.*' => ['required', 'string', 'regex:/^[0-9]{7}$/'],
            'startplatz' => ['nullable', 'integer', 'between:1,'.self::PER_SHEET],
        ], ['numbers.required' => 'Es gibt keine Nummern zum Drucken.', 'numbers.*.regex' => 'Inventarnummern haben genau 7 Ziffern.']);

        $numbers = array_values(array_unique($data['numbers']));
        sort($numbers, SORT_STRING);

        $audit->record('catalog.labels.numbers_printed', count($numbers).' Etiketten gedruckt ('.$numbers[0].' bis '.$numbers[count($numbers) - 1].').', null, ['count' => count($numbers)]);

        return response()
            ->view('pages.surfaces.pos.labels.stock-print', [
                'numbers' => $numbers,
                'skip' => max(0, ((int) ($data['startplatz'] ?? 1)) - 1),
                'perSheet' => self::PER_SHEET,
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
