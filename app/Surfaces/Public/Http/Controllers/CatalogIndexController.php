<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Catalog\DTOs\HoldingSummary;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Queries\CatalogSearchFilterOptionsQuery;
use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use App\Modules\Catalog\Services\CatalogHoldingService;
use App\Surfaces\Public\Http\Requests\CatalogSearchRequest;
use App\Surfaces\Public\Support\PublicCatalogPresenter;
use Illuminate\Http\Response;

final class CatalogIndexController
{
    public function __invoke(
        CatalogSearchRequest $request,
        SearchCatalogTitlesQuery $search,
        CatalogSearchFilterOptionsQuery $filterOptions,
        CatalogHoldingService $holdings,
        PublicCatalogPresenter $presenter,
    ): Response {
        $criteria = $request->toCriteria();
        $titles = $search->paginate($criteria);
        $validated = $request->validated();
        unset($validated['page']);
        $titles->appends($validated);

        /** @var array<string, HoldingSummary> $holdingSummaries */
        $holdingSummaries = [];

        /** @var Title $title */
        foreach ($titles->items() as $title) {
            $holdingSummaries[(string) $title->getKey()] = $holdings->summarizeTitle($title);
        }

        return response()->view('pages.surfaces.public.catalog.index', [
            'criteria' => $criteria,
            'titles' => $titles,
            'filterOptions' => $filterOptions->execute(),
            'holdingSummaries' => $holdingSummaries,
            'presenter' => $presenter,
        ]);
    }
}
