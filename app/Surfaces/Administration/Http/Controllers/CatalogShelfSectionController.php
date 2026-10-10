<?php

declare(strict_types=1);

namespace App\Surfaces\Administration\Http\Controllers;

use App\Modules\Catalog\Actions\DeleteCatalogShelfSectionAction;
use App\Modules\Catalog\Actions\SaveCatalogShelfSectionAction;
use App\Modules\Catalog\Enums\ShelfSectionKind;
use App\Modules\Catalog\Exceptions\CatalogShelfStructureConflict;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Services\CatalogShelfStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Bereichsgruppen, Bereiche und Regale: die Ebenen über den Regalbrettern. */
final class CatalogShelfSectionController
{
    public function store(Request $request, SaveCatalogShelfSectionAction $save): RedirectResponse
    {
        $data = $this->validated($request);
        $kind = ShelfSectionKind::from($data['kind']);

        try {
            $section = $save->execute(null, $kind, $data['parent_id'] ?? null, $data['code'], $data['name'] ?? null, $data['description'] ?? null, (int) ($data['sort_order'] ?? 0));
        } catch (CatalogShelfStructureConflict $exception) {
            return redirect()->route('administration.shelves.index')->withErrors(['shelf' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('administration.shelves.index')->with('shelf_success', $section->display().' ist angelegt.');
    }

    public function update(Request $request, string $sectionId, SaveCatalogShelfSectionAction $save): RedirectResponse
    {
        $section = CatalogShelfSection::query()->findOrFail($sectionId);
        $data = $this->validated($request, $section->kind);

        try {
            $section = $save->execute($section, $section->kind, $section->parent_id, $data['code'], $data['name'] ?? null, $data['description'] ?? null, (int) ($data['sort_order'] ?? 0));
        } catch (CatalogShelfStructureConflict $exception) {
            return redirect()->route('administration.shelves.index')->withErrors(['shelf' => $exception->getMessage()]);
        }

        return redirect()->route('administration.shelves.index')->with('shelf_success', $section->display().' ist gespeichert.');
    }

    public function destroy(Request $request, string $sectionId, DeleteCatalogShelfSectionAction $delete): RedirectResponse
    {
        $section = CatalogShelfSection::query()->findOrFail($sectionId);

        try {
            $result = $delete->execute($section, $request->boolean('cascade'), $request->boolean('release_copies'));
        } catch (CatalogShelfStructureConflict $exception) {
            return redirect()->route('administration.shelves.index')->withErrors(['shelf' => $exception->getMessage()]);
        }

        return redirect()->route('administration.shelves.index')->with('shelf_success', $section->display().' ist gelöscht.'
            .($result['shelves'] > 0 ? ' Mit '.$result['shelves'].' Regalbrett(ern).' : '')
            .($result['copies'] > 0 ? ' '.$result['copies'].' Exemplar(e) haben ihren Standort verloren und stehen wieder zum Einsortieren an.' : ''));
    }

    /** Regalbretter ohne Regal anhand ihres Codes („I. A 1 a“) zuordnen. */
    public function assign(CatalogShelfStructure $structure): RedirectResponse
    {
        $assigned = $structure->assignAll();

        return redirect()->route('administration.shelves.index')->with('shelf_success', $assigned > 0 ? "{$assigned} Regalbretter wurden ihrem Regal zugeordnet." : 'Es gab nichts zuzuordnen.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?ShelfSectionKind $kind = null): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
            'sort_order' => ['nullable', 'integer', 'between:0,9999'],
        ];

        if ($kind === null) {
            $rules['kind'] = ['required', Rule::enum(ShelfSectionKind::class)];
            $rules['parent_id'] = ['nullable', 'string', Rule::exists('catalog_shelf_sections', 'id')];
        }

        return $request->validate($rules, ['code.required' => 'Bitte eine Kennung angeben, zum Beispiel „A“ oder „1“.', 'code.max' => 'Die Kennung darf höchstens 20 Zeichen lang sein.']);
    }
}
