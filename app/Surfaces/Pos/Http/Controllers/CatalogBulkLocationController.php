<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Copy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Begrenzte, explizit bestätigte Standortänderung auf Exemplarebene.
 * Keine Änderungen von Titel-/Ausgabedaten, Ausleihen, Barcodes oder Status.
 */
final class CatalogBulkLocationController
{
    private const DRAFT = 'catalog.bulk.location.draft';

    public function preview(Request $request): Response
    {
        $data = $request->validate([
            'copies' => ['required', 'array', 'min:1', 'max:50'],
            'copies.*' => ['required', 'string', 'distinct', 'exists:catalog_copies,id'],
            'shelf' => ['required', 'string', 'exists:catalog_shelves,code'],
        ]);

        $ids = array_values($data['copies']);
        $copies = Copy::query()->with('edition.title')->whereIn('id', $ids)->get();
        $shelf = CatalogShelf::query()->where('code', $data['shelf'])->where('is_active', true)->first();

        if ($shelf === null || $copies->count() !== count($ids)) {
            throw ValidationException::withMessages(['shelf' => 'Bitte ein aktives Regalbrett und gültige Exemplare auswählen.']);
        }

        $token = Str::random(40);
        $snapshot = $copies->map(static fn (Copy $copy): array => [
            'id' => (string) $copy->getKey(),
            'from' => $copy->shelf_location,
            'updated_at' => $copy->updated_at?->toISOString(),
        ])->all();

        $request->session()->put(self::DRAFT, [
            'token' => $token, 'shelf' => $shelf->code,
            'copies' => $snapshot, 'expires' => now()->addMinutes(15)->timestamp,
        ]);

        return response()->view('pages.surfaces.pos.catalog.bulk-location', [
            'copies' => $copies, 'shelf' => $shelf, 'token' => $token,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function commit(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:40'],
            'confirm' => ['accepted'],
        ]);

        $draft = $request->session()->get(self::DRAFT);

        if (! is_array($draft) || ! hash_equals((string) ($draft['token'] ?? ''), $data['token'])
            || (int) ($draft['expires'] ?? 0) < now()->timestamp) {
            throw ValidationException::withMessages(['bulk' => 'Die Vorschau ist abgelaufen. Bitte Auswahl erneut prüfen.']);
        }

        // Schon vor der Transaktion ungültig machen, damit derselbe Entwurf
        // nicht mit einem zweiten Request nochmals angewandt werden kann.
        $request->session()->forget(self::DRAFT);
        $snapshots = (array) $draft['copies'];
        $ids = array_column($snapshots, 'id');
        $destination = (string) $draft['shelf'];

        // Derselbe Vorschauschlüssel darf nicht parallel angewendet werden.
        $lock = Cache::lock('catalog.bulk.location.'.hash('sha256', $data['token']), 30);
        if (! $lock->get()) {
            throw ValidationException::withMessages(['bulk' => 'Diese Stapelbearbeitung wird bereits verarbeitet.']);
        }

        try {
            DB::transaction(static function () use ($ids, $snapshots, $destination, $audit): void {
            $shelf = CatalogShelf::query()->where('code', $destination)->where('is_active', true)->first();

            if ($shelf === null) {
                throw ValidationException::withMessages(['bulk' => 'Das Zielregalbrett existiert nicht mehr oder ist inaktiv.']);
            }

            $locked = Copy::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            if ($locked->count() !== count($snapshots)) {
                throw ValidationException::withMessages(['bulk' => 'Die Auswahl hat sich verändert. Bitte Vorschau neu erstellen.']);
            }

            foreach ($snapshots as $snapshot) {
                $copy = $locked->get($snapshot['id']);

                if ($copy === null || $copy->shelf_location !== $snapshot['from']
                    || $copy->updated_at?->toISOString() !== $snapshot['updated_at']) {
                    throw ValidationException::withMessages(['bulk' => 'Ein Exemplar wurde zwischenzeitlich verändert. Keine Änderung übernommen.']);
                }
            }

            foreach ($snapshots as $snapshot) {
                $copy = $locked->get($snapshot['id']);
                $copy->shelf_location = $destination;
                $copy->save();

                $audit->record('catalog.copy.bulk_location', 'Exemplarstandort in Stapelbearbeitung geändert.', $copy, [
                    'from' => $snapshot['from'], 'to' => $destination,
                ]);
            }
            });
        } finally {
            $lock->release();
        }

        return redirect()->route('pos.catalog.index')->with('catalog_success', count($snapshots).' Exemplar(e) wurden auf den Standort '.$destination.' gesetzt.');
    }
}
