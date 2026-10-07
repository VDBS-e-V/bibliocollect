<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Foundation\Support\BusinessClock;
use App\Modules\Catalog\Actions\ApplyInventoryCorrectionsAction;
use App\Modules\Catalog\Actions\RecordInventoryScanAction;
use App\Modules\Catalog\Models\InventoryCount;
use App\Modules\Catalog\Services\CatalogShelfOptions;
use App\Modules\Catalog\Services\InventoryReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Inventur: Regalbrett wählen, die Bücher dort scannen, am Ende gegen den Bestand abgleichen. Geprüft sind nur die
 * Regalbretter, an denen gescannt wurde. Der Bericht nennt, was fehlt, falsch steht oder unbekannt ist.
 */
final class InventoryCountController
{
    public function index(): Response
    {
        return response()
            ->view('pages.surfaces.pos.inventory.index', [
                'open' => InventoryCount::query()->where('status', InventoryCount::OPEN)->withCount('items')->orderByDesc('started_at')->first(),
                'closed' => InventoryCount::query()->where('status', InventoryCount::CLOSED)->withCount('items')->orderByDesc('closed_at')->limit(15)->get(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function start(Request $request, BusinessClock $clock): RedirectResponse
    {
        $data = $request->validate(['name' => ['nullable', 'string', 'max:120']]);

        if (InventoryCount::query()->where('status', InventoryCount::OPEN)->exists()) {
            return redirect()->route('pos.inventory')->withErrors(['name' => 'Es läuft schon eine Inventur. Bitte erst diese abschließen.']);
        }

        $count = InventoryCount::query()->create([
            'name' => trim((string) ($data['name'] ?? '')) !== '' ? trim($data['name']) : 'Inventur '.$clock->now()->format('d.m.Y'),
            'status' => InventoryCount::OPEN,
            'started_by_user_id' => $request->user()?->getAuthIdentifier(),
            'started_at' => now(),
        ]);

        return redirect()->route('pos.inventory.show', ['countId' => $count->getKey()]);
    }

    public function show(Request $request, string $countId, CatalogShelfOptions $shelves): Response
    {
        $count = InventoryCount::query()->findOrFail($countId);
        $options = $shelves->forSelect();
        $shelf = trim((string) $request->query('regalbrett', $request->session()->get('inventory.shelf', '')));

        return response()
            ->view('pages.surfaces.pos.inventory.count', [
                'count' => $count,
                'shelfOptions' => $options,
                'shelf' => isset($options[$shelf]) ? $shelf : '',
                'perShelf' => $count->items()->selectRaw('shelf_code, count(*) as total')->groupBy('shelf_code')->orderBy('shelf_code')->pluck('total', 'shelf_code')->all(),
                'recent' => $count->items()->with('copy.edition.title')->orderByDesc('scanned_at')->limit(10)->get(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function scan(Request $request, string $countId, RecordInventoryScanAction $record, CatalogShelfOptions $shelves): RedirectResponse
    {
        $count = InventoryCount::query()->findOrFail($countId);

        if (! $count->isOpen()) {
            return redirect()->route('pos.inventory.report', ['countId' => $count->getKey()]);
        }

        $data = $request->validate([
            'regalbrett' => ['required', 'string', 'max:40'],
            'code' => ['required', 'string', 'max:80'],
        ], ['regalbrett.required' => 'Bitte zuerst das Regalbrett wählen.', 'code.required' => 'Bitte die Inventarnummer scannen.']);

        $shelf = trim($data['regalbrett']);

        if (! in_array($shelf, $shelves->activeCodes(), true)) {
            return redirect()->route('pos.inventory.show', ['countId' => $count->getKey()])->with('inventory_error', 'Dieses Regalbrett gibt es nicht (mehr).');
        }

        $request->session()->put('inventory.shelf', $shelf);
        $outcome = $record->execute($count, $shelf, $data['code']);
        $title = $outcome['copy']?->edition->title->preferred_title;

        $message = match ($outcome['result']) {
            'ok' => ['inventory_notice', $data['code'].' „'.$title.'“: richtig einsortiert.'],
            'misplaced' => ['inventory_warning', $data['code'].' „'.$title.'“ steht laut System auf '.$outcome['copy']?->shelf_location.', nicht auf '.$shelf.'.'],
            'unplaced' => ['inventory_warning', $data['code'].' „'.$title.'“ hat noch keinen Standort.'],
            'inactive' => ['inventory_warning', $data['code'].' „'.$title.'“ gilt als verloren oder ausgesondert, steht aber im Regal.'],
            default => ['inventory_error', 'Die Inventarnummer „'.trim($data['code']).'“ gibt es nicht. Sie steht im Bericht unter „Unbekannt“.'],
        };

        return redirect()->route('pos.inventory.show', ['countId' => $count->getKey()])->with($message[0], $message[1]);
    }

    public function report(string $countId, InventoryReport $report): Response
    {
        $count = InventoryCount::query()->findOrFail($countId);

        return response()
            ->view('pages.surfaces.pos.inventory.report', ['count' => $count, 'report' => $report->build($count)])
            ->header('Cache-Control', 'private, no-store');
    }

    public function close(string $countId): RedirectResponse
    {
        $count = InventoryCount::query()->findOrFail($countId);

        if ($count->isOpen()) {
            $count->forceFill(['status' => InventoryCount::CLOSED, 'closed_at' => now()])->save();
        }

        return redirect()->route('pos.inventory.report', ['countId' => $count->getKey()])->with('inventory_notice', 'Die Inventur ist abgeschlossen.');
    }

    public function apply(string $countId, ApplyInventoryCorrectionsAction $apply): RedirectResponse
    {
        $count = InventoryCount::query()->findOrFail($countId);
        $applied = $apply->execute($count);

        return redirect()->route('pos.inventory.report', ['countId' => $count->getKey()])->with('inventory_notice', $applied.' Standort(e) wurden korrigiert.');
    }

    public function export(string $countId, InventoryReport $report): StreamedResponse
    {
        $count = InventoryCount::query()->findOrFail($countId);
        $data = $report->build($count);

        return response()->streamDownload(static function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Ergebnis', 'Inventarnummer', 'Titel', 'Regalbrett gefunden', 'Regalbrett laut System'], ';');

            foreach (['ok' => 'Richtig', 'misplaced' => 'Falsch einsortiert', 'unplaced' => 'Ohne Standort', 'inactive' => 'Verloren oder ausgesondert', 'unknown' => 'Unbekannt'] as $key => $label) {
                foreach ($data[$key] as $item) {
                    fputcsv($out, [$label, $item->barcode, $item->copy?->edition->title->preferred_title, $item->shelf_code, $item->copy?->shelf_location], ';');
                }
            }

            foreach ($data['missing'] as $copy) {
                fputcsv($out, ['Fehlt', $copy->barcode, $copy->edition->title->preferred_title, '', $copy->shelf_location], ';');
            }

            fclose($out);
        }, 'inventur.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
