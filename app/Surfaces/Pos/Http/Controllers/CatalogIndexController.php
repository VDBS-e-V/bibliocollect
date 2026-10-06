<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Queries\CatalogSearchFilterOptionsQuery;
use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use App\Surfaces\Pos\Http\Requests\CatalogStaffSearchRequest;
use Illuminate\Http\Response;

final class CatalogIndexController
{
    public function __invoke(
        CatalogStaffSearchRequest $request,
        SearchCatalogTitlesQuery $search,
        CatalogSearchFilterOptionsQuery $filterOptions,
    ): Response {
        $criteria = $request->toCriteria();
        $validated = $request->validated();
        unset($validated['page']);

        $titles = $criteria->hasSearchInput() ? $search->paginate($criteria) : null;
        $titles?->appends($validated);

        return response()
            ->view('pages.surfaces.pos.catalog.index', [
                'criteria' => $criteria,
                'titles' => $titles,
                'filterOptions' => $filterOptions->execute(),
                'queryParameters' => $validated,
                'openQualityCases' => CatalogMetadataReview::query()
                    ->where('status', MetadataReviewStatus::Open->value)
                    ->where('severity', '>', 0)
                    ->count(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }
}
