<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Catalog\Queries\CatalogSearchFilterOptionsQuery;
use App\Surfaces\Public\Http\Requests\CatalogSearchRequest;
use App\Surfaces\Public\Support\PublicCatalogPresenter;
use Illuminate\Http\Response;

final class CatalogAdvancedSearchController
{
    public function __invoke(
        CatalogSearchRequest $request,
        CatalogSearchFilterOptionsQuery $filterOptions,
        PublicCatalogPresenter $presenter,
    ): Response {
        return response()->view('pages.surfaces.public.catalog.advanced', [
            'criteria' => $request->toCriteria(),
            'filterOptions' => $filterOptions->execute(),
            'presenter' => $presenter,
        ]);
    }
}
