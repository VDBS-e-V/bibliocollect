<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Catalog\Actions\DeleteCatalogShelfAction;
use App\Modules\Catalog\Actions\ImportCatalogShelvesFromSignaturesAction;
use App\Modules\Catalog\Actions\SaveCatalogShelfAction;
use App\Modules\Catalog\Exceptions\CatalogShelfInUse;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogSignature;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Services\CatalogSignatureOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Regalbretter: die Liste, aus der beim Erfassen und Bearbeiten der Standort eines Exemplars gewählt wird. */
final class CatalogShelfController
{
    public function index(): Response
    {
        $shelves = CatalogShelf::query()->with('signature.topics')->orderBy('sort_order')->orderBy('code')->get();

        $counts = Copy::query()->whereNotNull('shelf_location')->selectRaw('shelf_location, count(*) as total')->groupBy('shelf_location')->pluck('total', 'shelf_location')->all();

        return response()
            ->view('pages.surfaces.administration.shelves.index', [
                'shelves' => $shelves,
                'counts' => $counts,
                'nextOrder' => ((int) $shelves->max('sort_order')) + 1,
                'signatureCount' => CatalogSignature::query()->count(),
                'signatureOptions' => app(CatalogSignatureOptions::class)->forSelect(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Zu jeder Signatur ein Regalbrett anlegen (wiederholbar). */
    public function fromSignatures(ImportCatalogShelvesFromSignaturesAction $import): RedirectResponse
    {
        $result = $import->execute();

        return redirect()->route('administration.shelves.index')->with('shelf_success', "Aus den Signaturen übernommen: {$result['created']} neue Regalbretter, {$result['updated']} aktualisiert, {$result['merged']} doppelte Schreibweisen zusammengeführt.");
    }

    public function store(Request $request, SaveCatalogShelfAction $save): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('catalog_shelves', 'code')],
            'label' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
            'signature_id' => ['nullable', 'string', Rule::exists('catalog_signatures', 'id')],
        ], $this->messages());

        $save->execute(null, $data['code'], $data['label'] ?? null, (int) ($data['sort_order'] ?? 0), true, $data['signature_id'] ?? null);

        return redirect()->route('administration.shelves.index')->with('shelf_success', 'Das Regalbrett „'.trim($data['code']).'“ ist angelegt.');
    }

    public function update(Request $request, string $shelfId, SaveCatalogShelfAction $save): RedirectResponse
    {
        $shelf = CatalogShelf::query()->findOrFail($shelfId);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('catalog_shelves', 'code')->ignore($shelf->getKey())],
            'label' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
            'is_active' => ['nullable', 'boolean'],
            'signature_id' => ['nullable', 'string', Rule::exists('catalog_signatures', 'id')],
        ], $this->messages());

        $save->execute($shelf, $data['code'], $data['label'] ?? null, (int) ($data['sort_order'] ?? 0), $request->boolean('is_active'), $data['signature_id'] ?? null);

        return redirect()->route('administration.shelves.index')->with('shelf_success', 'Das Regalbrett „'.trim($data['code']).'“ ist gespeichert.');
    }

    public function destroy(string $shelfId, DeleteCatalogShelfAction $delete): RedirectResponse
    {
        $shelf = CatalogShelf::query()->findOrFail($shelfId);

        try {
            $delete->execute($shelf);
        } catch (CatalogShelfInUse $exception) {
            return redirect()->route('administration.shelves.index')->withErrors(['shelf' => $exception->getMessage()]);
        }

        return redirect()->route('administration.shelves.index')->with('shelf_success', 'Das Regalbrett „'.$shelf->code.'“ ist gelöscht.');
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'code.required' => 'Bitte eine Bezeichnung für das Regalbrett angeben.',
            'code.unique' => 'Ein Regalbrett mit dieser Bezeichnung gibt es schon.',
            'code.max' => 'Die Bezeichnung darf höchstens 40 Zeichen lang sein.',
        ];
    }
}
