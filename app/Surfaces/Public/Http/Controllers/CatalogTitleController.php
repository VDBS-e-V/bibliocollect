<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Modules\Catalog\DTOs\HoldingSummary;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogHoldingService;
use App\Surfaces\Public\Support\PublicCatalogPresenter;
use Illuminate\Http\Response;

final class CatalogTitleController
{
    public function __invoke(
        string $titleId,
        CatalogHoldingService $holdings,
        PublicCatalogPresenter $presenter,
    ): Response {
        $title = Title::query()
            ->with(['contributions.contributor', 'editions.copies'])
            ->findOrFail($titleId);

        /** @var array<string, HoldingSummary> $editionSummaries */
        $editionSummaries = [];

        /** @var Edition $edition */
        foreach ($title->editions as $edition) {
            $editionSummaries[(string) $edition->getKey()] = $holdings->summarizeEdition($edition);
        }

        return response()->view('pages.surfaces.public.catalog.show', [
            'title' => $title,
            'titleSummary' => $holdings->summarizeTitle($title),
            'editionSummaries' => $editionSummaries,
            'presenter' => $presenter,
        ]);
    }
}
