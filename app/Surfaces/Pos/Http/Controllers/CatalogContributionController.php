<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Modules\Catalog\Actions\CreateTitleContributionAction;
use App\Modules\Catalog\Actions\RemoveTitleContributionAction;
use App\Modules\Catalog\Actions\UpdateTitleContributionAction;
use App\Modules\Catalog\Exceptions\DuplicateTitleContribution;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Surfaces\Pos\Http\Requests\CatalogContributionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class CatalogContributionController
{
    public function store(
        CatalogContributionRequest $request,
        string $titleId,
        CreateTitleContributionAction $create,
    ): RedirectResponse {
        $title = Title::query()->findOrFail($titleId);

        try {
            $create->execute($title, $request->toData());
        } catch (DuplicateTitleContribution $exception) {
            return redirect()
                ->route('pos.catalog.titles.show', ['titleId' => $title->getKey()])
                ->withInput()
                ->with('catalog_error', $exception->getMessage());
        }

        return redirect()
            ->route('pos.catalog.titles.show', ['titleId' => $title->getKey()])
            ->with('catalog_success', 'Die verantwortliche Person oder Körperschaft wurde hinzugefügt.');
    }

    public function edit(string $titleId, string $contributionId): Response
    {
        $title = Title::query()->findOrFail($titleId);
        $contribution = TitleContribution::query()
            ->with('contributor')
            ->where('title_id', $title->getKey())
            ->findOrFail($contributionId);

        $usedOnTitleCount = $contribution->contributor
            ->contributions()
            ->distinct()
            ->count('title_id');

        return response()
            ->view('pages.surfaces.pos.catalog.contribution-edit', [
                'title' => $title,
                'contribution' => $contribution,
                'usedOnTitleCount' => $usedOnTitleCount,
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(
        CatalogContributionRequest $request,
        string $titleId,
        string $contributionId,
        UpdateTitleContributionAction $update,
    ): RedirectResponse {
        $title = Title::query()->findOrFail($titleId);
        $contribution = TitleContribution::query()
            ->where('title_id', $title->getKey())
            ->findOrFail($contributionId);

        try {
            $update->execute($title, $contribution, $request->toData());
        } catch (DuplicateTitleContribution $exception) {
            return redirect()
                ->route('pos.catalog.contributions.edit', [
                    'titleId' => $title->getKey(),
                    'contributionId' => $contribution->getKey(),
                ])
                ->withInput()
                ->with('catalog_error', $exception->getMessage());
        }

        return redirect()
            ->route('pos.catalog.titles.show', ['titleId' => $title->getKey()])
            ->with('catalog_success', 'Die Verantwortlichkeit wurde gespeichert.');
    }

    public function destroy(
        string $titleId,
        string $contributionId,
        RemoveTitleContributionAction $remove,
    ): RedirectResponse {
        $title = Title::query()->findOrFail($titleId);
        $contribution = TitleContribution::query()
            ->where('title_id', $title->getKey())
            ->findOrFail($contributionId);

        $remove->execute($title, $contribution);

        return redirect()
            ->route('pos.catalog.titles.show', ['titleId' => $title->getKey()])
            ->with('catalog_success', 'Die Verantwortlichkeit wurde vom Titel entfernt.');
    }
}
