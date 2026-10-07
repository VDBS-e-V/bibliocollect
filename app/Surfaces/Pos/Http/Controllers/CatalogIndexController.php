<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Queries\CatalogSearchFilterOptionsQuery;
use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use App\Surfaces\Pos\Http\Requests\CatalogStaffSearchRequest;
use Illuminate\Http\Response;

final class CatalogIndexController
{
    public function __invoke(
        CatalogStaffSearchRequest $request,
        SearchCatalogTitlesQuery $search,
        CatalogSearchFilterOptionsQuery $filterOptions,
        CopyAvailabilityService $availability,
    ): Response {
        $criteria = $request->toCriteria();
        $validated = $request->validated();
        unset($validated['page']);

        // Ohne Suchbegriff erscheinen gleich die Titel (A–Z), damit man direkt stöbern kann.
        $titles = $search->paginate($criteria);
        $titles->appends($validated);

        $copyIds = [];

        foreach ($titles->items() as $title) {
            foreach ($title->editions as $edition) {
                foreach ($edition->copies as $copy) {
                    $copyIds[] = (string) $copy->getKey();
                }
            }
        }

        return response()
            ->view('pages.surfaces.pos.catalog.index', [
                'criteria' => $criteria,
                'titles' => $titles,
                'copyStates' => $availability->forCopies($copyIds),
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
