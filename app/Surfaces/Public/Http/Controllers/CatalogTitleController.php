<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Catalog\DTOs\HoldingSummary;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogClassificationService;
use App\Modules\Catalog\Services\CatalogCoverService;
use App\Modules\Catalog\Services\CatalogHoldingService;
use App\Surfaces\Public\Support\PublicCatalogPresenter;
use Illuminate\Http\Response;

final class CatalogTitleController
{
    public function __invoke(
        string $titleId,
        CatalogHoldingService $holdings,
        CatalogClassificationService $classification,
        CatalogCoverService $covers,
        PublicCatalogPresenter $presenter,
    ): Response {
        $title = Title::query()
            ->with(['contributions.contributor', 'editions.copies.signature.topics'])
            ->findOrFail($titleId);

        /** @var array<string, HoldingSummary> $editionSummaries */
        $editionSummaries = [];
        /** @var array<string, list<string>> $editionTopics */
        $editionTopics = [];

        /** @var Edition $edition */
        foreach ($title->editions as $edition) {
            $editionSummaries[(string) $edition->getKey()] = $holdings->summarizeEdition($edition);
            $editionTopics[(string) $edition->getKey()] = $classification->topicNamesForEdition($edition);
        }

        return response()->view('pages.surfaces.public.catalog.show', [
            'title' => $title,
            'titleSummary' => $holdings->summarizeTitle($title),
            'editionSummaries' => $editionSummaries,
            'editionTopics' => $editionTopics,
            'coverUrl' => $covers->localUrlForTitle($title) ?? asset('brand/vdbs/catalog-cover-placeholder.png'),
            'presenter' => $presenter,
        ]);
    }
}
