<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Actions\CreateEditionAction;
use App\Modules\Catalog\Actions\UpdateEditionAction;
use App\Modules\Catalog\Enums\CopyAccess;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Surfaces\Pos\Http\Requests\CatalogEditionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class CatalogEditionController
{
    public function store(
        CatalogEditionRequest $request,
        string $titleId,
        CreateEditionAction $create,
    ): RedirectResponse {
        $title = Title::query()->findOrFail($titleId);
        $create->execute($title, $request->toData());

        return redirect()
            ->route('pos.catalog.titles.show', ['titleId' => $title->getKey()])
            ->with('catalog_success', 'Die Ausgabe wurde angelegt.');
    }

    public function edit(string $editionId): Response
    {
        $edition = Edition::query()
            ->with(['title', 'copies'])
            ->findOrFail($editionId);

        return response()
            ->view('pages.surfaces.pos.catalog.edition-edit', [
                'edition' => $edition,
                'copyStatuses' => CopyStatus::cases(),
                'accessOptions' => CopyAccess::options(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(
        CatalogEditionRequest $request,
        string $editionId,
        UpdateEditionAction $update,
    ): RedirectResponse {
        $edition = Edition::query()->findOrFail($editionId);
        $updated = $update->execute($edition, $request->toData());

        return redirect()
            ->route('pos.catalog.titles.show', ['titleId' => $updated->title_id])
            ->with('catalog_success', 'Die Ausgabe wurde gespeichert.');
    }
}
