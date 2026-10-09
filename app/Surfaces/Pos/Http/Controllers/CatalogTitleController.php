<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Actions\CreateTitleAction;
use App\Modules\Catalog\Actions\ToggleFeaturedAction;
use App\Modules\Catalog\Actions\UpdateTitleAction;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use App\Surfaces\Pos\Http\Requests\CatalogTitleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class CatalogTitleController
{
    public function store(CatalogTitleRequest $request, CreateTitleAction $create): RedirectResponse
    {
        $title = $create->execute($request->toData());

        return redirect()
            ->route('pos.catalog.titles.show', ['titleId' => $title->getKey()])
            ->with('catalog_success', 'Der Titel wurde angelegt.');
    }

    public function show(string $titleId, CopyAvailabilityService $availability): Response
    {
        $title = Title::query()
            ->with(['contributions.contributor', 'editions.copies'])
            ->findOrFail($titleId);

        $copyIds = $title->editions->flatMap(static fn ($edition) => $edition->copies->map(static fn ($copy): string => (string) $copy->getKey()))->all();

        return response()
            ->view('pages.surfaces.pos.catalog.show', ['title' => $title, 'copyStates' => $availability->forCopies($copyIds)])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Titel auf der Startseite empfehlen oder die Empfehlung zurücknehmen. */
    public function feature(string $titleId, ToggleFeaturedAction $toggle): RedirectResponse
    {
        $title = Title::query()->findOrFail($titleId);
        $featured = $toggle->execute($title);

        return redirect()
            ->route('pos.catalog.titles.show', ['titleId' => $title->getKey()])
            ->with('catalog_success', $featured ? 'Der Titel wird auf der Startseite empfohlen.' : 'Die Empfehlung wurde zurückgenommen.');
    }

    public function update(
        CatalogTitleRequest $request,
        string $titleId,
        UpdateTitleAction $update,
    ): RedirectResponse {
        $title = Title::query()->findOrFail($titleId);
        $updated = $update->execute($title, $request->toData());

        return redirect()
            ->route('pos.catalog.titles.show', ['titleId' => $updated->getKey()])
            ->with('catalog_success', 'Die Titeldaten wurden gespeichert.');
    }
}
