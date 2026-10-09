<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Catalog\DTOs\HoldingSummary;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Queries\CatalogSearchFilterOptionsQuery;
use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use App\Modules\Catalog\Services\CatalogClassificationService;
use App\Modules\Catalog\Services\CatalogCoverService;
use App\Modules\Catalog\Services\CatalogHoldingService;
use App\Modules\Circulation\Services\CopyAvailabilityService;
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
        CatalogClassificationService $classification,
        CatalogCoverService $covers,
        CopyAvailabilityService $availability,
        PublicCatalogPresenter $presenter,
    ): Response {
        $criteria = $request->toCriteria();
        $titles = $search->paginate($criteria);
        $validated = $request->validated();
        unset($validated['page']);
        $titles->appends($validated);

        /** @var array<string, HoldingSummary> $holdingSummaries */
        $holdingSummaries = [];
        $availabilities = $availability->forTitles(array_map(
            static fn (Title $title): string => (string) $title->getKey(),
            $titles->items(),
        ));
        /** @var array<string, string> $coverUrls */
        $coverUrls = [];
        /** @var array<string, list<string>> $topicNames */
        $topicNames = [];

        /** @var Title $title */
        foreach ($titles->items() as $title) {
            $titleId = (string) $title->getKey();
            $holdingSummaries[$titleId] = $holdings->summarizeTitle($title);
            $coverUrls[$titleId] = $covers->localUrlForTitle($title)
                ?? asset('brand/vdbs/catalog-cover-placeholder.svg');

            $names = [];

            /** @var Edition $edition */
            foreach ($title->editions as $edition) {
                $names = array_merge($names, $classification->topicNamesForEdition($edition));
            }

            $names = array_values(array_unique($names));
            sort($names, SORT_NATURAL | SORT_FLAG_CASE);
            $topicNames[$titleId] = $names;
        }

        $shelf = $criteria->shelf !== null
            ? CatalogShelf::query()->with(['topics', 'rack.parent.parent'])->whereRaw('LOWER(code) = ?', [mb_strtolower($criteria->shelf)])->first()
            : null;

        $theme = $criteria->theme !== null
            ? CatalogTopic::query()->with(['shelves.rack.parent.parent'])->whereRaw('LOWER(name) = ?', [mb_strtolower($criteria->theme)])->first()
            : null;

        return response()->view('pages.surfaces.public.catalog.index', [
            'shelf' => $shelf,
            'theme' => $theme,
            'criteria' => $criteria,
            'titles' => $titles,
            'filterOptions' => $filterOptions->execute(),
            'holdingSummaries' => $holdingSummaries,
            'availabilities' => $availabilities,
            'coverUrls' => $coverUrls,
            'topicNames' => $topicNames,
            'queryParameters' => $validated,
            'presenter' => $presenter,
        ]);
    }
}
