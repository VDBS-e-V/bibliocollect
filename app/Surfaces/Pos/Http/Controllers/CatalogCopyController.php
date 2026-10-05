<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Actions\CreateCopyAction;
use App\Modules\Catalog\Actions\UpdateCopyAction;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Exceptions\DuplicateCopyBarcode;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Surfaces\Pos\Http\Requests\CatalogCopyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class CatalogCopyController
{
    public function store(
        CatalogCopyRequest $request,
        string $editionId,
        CreateCopyAction $create,
    ): RedirectResponse {
        $edition = Edition::query()->findOrFail($editionId);

        try {
            $create->execute($edition, $request->toData());
        } catch (DuplicateCopyBarcode $exception) {
            return redirect()
                ->route('pos.catalog.editions.edit', ['editionId' => $edition->getKey()])
                ->withInput()
                ->withErrors(['barcode' => $exception->getMessage()]);
        }

        return redirect()
            ->route('pos.catalog.editions.edit', ['editionId' => $edition->getKey()])
            ->with('catalog_success', 'Das Exemplar wurde angelegt.');
    }

    public function edit(string $editionId, string $copyId): Response
    {
        $edition = Edition::query()->with('title')->findOrFail($editionId);
        $copy = Copy::query()
            ->where('edition_id', $edition->getKey())
            ->findOrFail($copyId);

        return response()
            ->view('pages.surfaces.pos.catalog.copy-edit', [
                'edition' => $edition,
                'copy' => $copy,
                'copyStatuses' => CopyStatus::cases(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(
        CatalogCopyRequest $request,
        string $editionId,
        string $copyId,
        UpdateCopyAction $update,
    ): RedirectResponse {
        $edition = Edition::query()->findOrFail($editionId);
        $copy = Copy::query()
            ->where('edition_id', $edition->getKey())
            ->findOrFail($copyId);

        try {
            $updated = $update->execute($edition, $copy, $request->toData());
        } catch (DuplicateCopyBarcode $exception) {
            return redirect()
                ->route('pos.catalog.copies.edit', [
                    'editionId' => $edition->getKey(),
                    'copyId' => $copy->getKey(),
                ])
                ->withInput()
                ->withErrors(['barcode' => $exception->getMessage()]);
        }

        return redirect()
            ->route('pos.catalog.editions.edit', ['editionId' => $updated->edition_id])
            ->with('catalog_success', 'Das Exemplar wurde gespeichert.');
    }
}
