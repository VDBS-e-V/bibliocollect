<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Actions\CommitCatalogImportAction;
use App\Modules\Catalog\Actions\CreateCatalogImportBatchAction;
use App\Modules\Catalog\Actions\PreviewCatalogImportAction;
use App\Modules\Catalog\Enums\CatalogImportField;
use App\Modules\Catalog\Exceptions\CatalogImportCommitException;
use App\Modules\Catalog\Exceptions\CatalogImportSourceException;
use App\Modules\Catalog\Import\CsvCatalogImportSource;
use App\Modules\Catalog\Queries\FindCatalogImportBatchQuery;
use App\Modules\Catalog\Queries\ListCatalogImportBatchesQuery;
use App\Surfaces\Pos\Http\Requests\CatalogImportCommitRequest;
use App\Surfaces\Pos\Http\Requests\CatalogImportMappingRequest;
use App\Surfaces\Pos\Http\Requests\CatalogImportUploadRequest;
use App\Surfaces\Pos\Support\CatalogImportPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

final class CatalogImportController
{
    public function create(
        ListCatalogImportBatchesQuery $list,
        CatalogImportPresenter $presenter,
    ): Response {
        return response()
            ->view('pages.surfaces.pos.catalog.import.create', [
                'batches' => $list->execute(),
                'presenter' => $presenter,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(
        CatalogImportUploadRequest $request,
        CreateCatalogImportBatchAction $create,
    ): RedirectResponse {
        $file = $request->file('catalog_file');

        if (! $file instanceof UploadedFile) {
            return back()->withErrors(['catalog_file' => 'Die hochgeladene CSV-Datei konnte nicht gelesen werden.']);
        }

        $path = $file->getRealPath();

        if ($path === false) {
            return back()->withErrors(['catalog_file' => 'Die hochgeladene CSV-Datei konnte nicht gelesen werden.']);
        }

        $user = $request->user();

        try {
            $batch = $create->execute(
                new CsvCatalogImportSource($path),
                $user !== null ? (int) $user->getAuthIdentifier() : null,
                basename(str_replace('\\', '/', $file->getClientOriginalName())),
            );
        } catch (CatalogImportSourceException $exception) {
            return back()->withInput()->withErrors(['catalog_file' => $exception->getMessage()]);
        }

        return redirect()
            ->route('pos.catalog.import.show', ['batchId' => $batch->getKey()])
            ->with('catalog_success', 'CSV wurde eingelesen. Bitte prüfe jetzt das Feldmapping und die Vorschau.');
    }

    public function show(
        string $batchId,
        FindCatalogImportBatchQuery $find,
        CatalogImportPresenter $presenter,
    ): Response {
        return response()
            ->view('pages.surfaces.pos.catalog.import.show', [
                'batch' => $find->execute($batchId),
                'fields' => CatalogImportField::cases(),
                'presenter' => $presenter,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function preview(
        CatalogImportMappingRequest $request,
        string $batchId,
        FindCatalogImportBatchQuery $find,
        PreviewCatalogImportAction $preview,
    ): RedirectResponse {
        $batch = $find->execute($batchId);
        $preview->execute($batch, $request->toMapping());

        return redirect()
            ->route('pos.catalog.import.show', ['batchId' => $batch->getKey()])
            ->with('catalog_success', 'Mapping und Vorschau wurden neu ausgewertet.');
    }

    public function commit(
        CatalogImportCommitRequest $request,
        string $batchId,
        FindCatalogImportBatchQuery $find,
        CommitCatalogImportAction $commit,
    ): RedirectResponse {
        $batch = $find->execute($batchId);

        try {
            $commit->execute($batch);
        } catch (CatalogImportCommitException $exception) {
            return redirect()
                ->route('pos.catalog.import.show', ['batchId' => $batch->getKey()])
                ->withErrors(['confirm_import' => $exception->getMessage()]);
        }

        return redirect()
            ->route('pos.catalog.import.show', ['batchId' => $batch->getKey()])
            ->with('catalog_success', 'Der Katalogimport wurde vollständig übernommen.');
    }
}
