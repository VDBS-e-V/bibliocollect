<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\Actions\ApplyMetadataProposalAction;
use App\Modules\Catalog\Actions\DismissMetadataReviewAction;
use App\Modules\Catalog\Actions\ReopenMetadataReviewAction;
use App\Modules\Catalog\Actions\ScanCatalogMetadataQualityAction;
use App\Modules\Catalog\DTOs\MetadataProposal;
use App\Modules\Catalog\Enums\MetadataIssue;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Exceptions\MetadataProposalOutdated;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use App\Modules\Catalog\Quality\MetadataFingerprint;
use App\Modules\Catalog\Queries\ListMetadataReviewsQuery;
use App\Modules\Catalog\Services\MetadataProposalService;
use App\Surfaces\Pos\Http\Requests\CatalogQualityApplyRequest;
use App\Surfaces\Pos\Http\Requests\CatalogQualityIndexRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

/**
 * Prüfliste für unvollständige oder fehlerhafte Metadaten. Vorschläge werden erst nach ausdrücklicher
 * Bestätigung durch eine Person in den Katalog übernommen.
 */
final class CatalogQualityController
{
    public function index(CatalogQualityIndexRequest $request, ListMetadataReviewsQuery $query): Response
    {
        $filters = $request->filters();
        $reviews = $query->paginate($filters, $request->perPage(), $request->page());
        $queryParameters = $request->queryParameters();
        $reviews->appends($queryParameters);

        return $this->view('index', [
            'reviews' => $reviews,
            'filters' => $filters,
            'queryParameters' => $queryParameters,
            'counts' => $query->counts(),
            'defectIssues' => MetadataIssue::defects(),
            'enrichmentIssues' => array_values(array_filter(MetadataIssue::cases(), static fn (MetadataIssue $issue): bool => $issue->isEnrichment())),
        ]);
    }

    public function scan(ScanCatalogMetadataQualityAction $scan): RedirectResponse
    {
        $summary = $scan->execute();

        return redirect()
            ->route('pos.catalog.quality.index')
            ->with('catalog_success', sprintf(
                '%d Ausgaben geprüft: %d neue Fälle, %d aktualisiert, %d wieder geöffnet, %d erledigt.',
                $summary['scanned'],
                $summary['created'],
                $summary['updated'],
                $summary['reopened'],
                $summary['resolved'],
            ));
    }

    public function show(
        string $reviewId,
        MetadataProposalService $proposals,
        MetadataFingerprint $fingerprint,
        ListMetadataReviewsQuery $query,
    ): Response {
        $review = $this->review($reviewId);
        $edition = $review->edition;
        $edition->load('title.contributions.contributor');

        // Ein Vorschlag wird erst beim Öffnen geholt und gilt nur für den Datenstand, auf dem er berechnet wurde.
        $stale = $review->proposal === null
            || ($review->proposal['fingerprint'] ?? null) !== $fingerprint->for($edition);

        if ($stale && $review->status === MetadataReviewStatus::Open) {
            $review = $proposals->propose($review);
        }

        $userIds = collect($review->history ?? [])->pluck('user_id')->filter()->unique()->values()->all();

        return $this->view('show', [
            'userNames' => $userIds === [] ? [] : User::query()->whereIn('id', $userIds)->pluck('name', 'id')->all(),
            'review' => $review,
            'edition' => $edition,
            'issues' => array_values(array_filter(array_map(MetadataIssue::tryFrom(...), $review->issues))),
            'proposal' => $review->proposal !== null ? MetadataProposal::fromArray($review->proposal) : null,
            'nextId' => $query->nextOpenDefectId($reviewId),
        ]);
    }

    public function refresh(string $reviewId, MetadataProposalService $proposals): RedirectResponse
    {
        $proposals->propose($this->review($reviewId));

        return redirect()
            ->route('pos.catalog.quality.show', ['reviewId' => $reviewId])
            ->with('catalog_success', 'Der Vorschlag wurde neu abgefragt.');
    }

    public function apply(
        CatalogQualityApplyRequest $request,
        string $reviewId,
        ApplyMetadataProposalAction $apply,
        ListMetadataReviewsQuery $query,
    ): RedirectResponse {
        $review = $this->review($reviewId);
        $next = $query->nextOpenDefectId($reviewId);

        try {
            $apply->execute($review, $request->changeKeys(), $this->userId($request));
        } catch (MetadataProposalOutdated|InvalidArgumentException $exception) {
            return redirect()
                ->route('pos.catalog.quality.show', ['reviewId' => $reviewId])
                ->withErrors(['changes' => $exception->getMessage()]);
        }

        return $this->continueWith($next, sprintf('%d Änderung(en) wurden übernommen.', count($request->changeKeys())));
    }

    public function dismiss(Request $request, string $reviewId, DismissMetadataReviewAction $dismiss, ListMetadataReviewsQuery $query): RedirectResponse
    {
        $review = $this->review($reviewId);
        $next = $query->nextOpenDefectId($reviewId);

        $dismiss->execute($review, $this->userId($request));

        return $this->continueWith($next, 'Der Fall wurde als „kein Handlungsbedarf“ vermerkt.');
    }

    public function reopen(Request $request, string $reviewId, ReopenMetadataReviewAction $reopen): RedirectResponse
    {
        $reopen->execute($this->review($reviewId), $this->userId($request));

        return redirect()
            ->route('pos.catalog.quality.show', ['reviewId' => $reviewId])
            ->with('catalog_success', 'Der Fall ist wieder offen.');
    }

    public function skip(string $reviewId, ListMetadataReviewsQuery $query): RedirectResponse
    {
        $next = $query->nextOpenDefectId($reviewId);

        return $next !== null
            ? redirect()->route('pos.catalog.quality.show', ['reviewId' => $next])
            : redirect()->route('pos.catalog.quality.index')->with('catalog_success', 'Das war der letzte offene Fall.');
    }

    private function continueWith(?string $nextId, string $message): RedirectResponse
    {
        $stillOpen = $nextId !== null
            && CatalogMetadataReview::query()
                ->whereKey($nextId)
                ->where('status', MetadataReviewStatus::Open->value)
                ->exists();

        if ($stillOpen) {
            return redirect()
                ->route('pos.catalog.quality.show', ['reviewId' => $nextId])
                ->with('catalog_success', $message.' Weiter mit dem nächsten Fall.');
        }

        return redirect()->route('pos.catalog.quality.index')->with('catalog_success', $message);
    }

    private function review(string $reviewId): CatalogMetadataReview
    {
        return CatalogMetadataReview::query()->with('edition.title')->findOrFail($reviewId);
    }

    private function userId(Request $request): int
    {
        return (int) $request->user()?->getKey();
    }

    /** @param array<string, mixed> $data */
    private function view(string $page, array $data): Response
    {
        return response()
            ->view('pages.surfaces.pos.catalog.quality.'.$page, $data)
            ->header('Cache-Control', 'private, no-store');
    }
}
