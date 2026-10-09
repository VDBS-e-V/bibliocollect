<?php

declare(strict_types=1);

namespace App\Surfaces\Portal\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogCoverService;
use App\Modules\Catalog\Services\CatalogHoldingService;
use App\Modules\Circulation\Actions\ToggleBookmarkAction;
use App\Modules\Circulation\Exceptions\BookmarkLimitReached;
use App\Modules\Circulation\Models\Bookmark;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use App\Surfaces\Public\Support\PublicCatalogPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Merkliste im Onlinekonto: Titel merken, entfernen und ansehen. */
final class PortalBookmarkController
{
    public function index(Request $request, CatalogHoldingService $holdings, CatalogCoverService $covers, CopyAvailabilityService $availability, PublicCatalogPresenter $presenter): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $titles = Title::query()
            ->with(['contributions.contributor', 'editions.copies'])
            ->whereIn('id', Bookmark::query()->where('user_id', $user->getKey())->orderByDesc('created_at')->pluck('title_id'))
            ->whereHas('editions.copies', static fn ($copies) => $copies->whereIn('status', [CopyStatus::Active->value, CopyStatus::Damaged->value]))
            ->get();

        $order = Bookmark::query()->where('user_id', $user->getKey())->orderByDesc('created_at')->orderByDesc('id')->pluck('title_id')->map(static fn ($id): string => (string) $id)->flip();
        $titles = $titles->sortBy(static fn (Title $title): int => (int) ($order[(string) $title->getKey()] ?? 0))->values();

        $ids = $titles->map(static fn (Title $title): string => (string) $title->getKey())->all();
        $availabilities = $availability->forTitles($ids);
        $rows = [];

        foreach ($titles as $title) {
            $id = (string) $title->getKey();
            $holding = $holdings->summarizeTitle($title);
            $rows[] = [
                'id' => $id,
                'title' => $title->preferred_title,
                'authors' => $title->contributions->pluck('contributor.display_name')->filter()->take(3)->join(', '),
                'cover' => $covers->localUrlForTitle($title) ?? asset('brand/vdbs/catalog-cover-placeholder.svg'),
                'badge' => $presenter->availabilityLabel($holding, $availabilities[$id]),
                'variant' => $presenter->availabilityVariant($holding, $availabilities[$id]),
            ];
        }

        return response()
            ->view('pages.surfaces.portal-bookmarks', ['rows' => $rows, 'total' => Bookmark::query()->where('user_id', $user->getKey())->count()])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Merken oder entfernen; danach zurück auf die Seite, von der der Knopf kam. */
    public function toggle(Request $request, string $titleId, ToggleBookmarkAction $toggle): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $title = Title::query()->findOrFail($titleId);

        try {
            $marked = $toggle->execute((int) $user->getKey(), $title);
        } catch (BookmarkLimitReached $exception) {
            return back()->with('bookmark_error', $exception->getMessage());
        }

        return back()->with('bookmark_notice', $marked ? '„'.$title->preferred_title.'“ steht auf deiner Merkliste.' : '„'.$title->preferred_title.'“ ist nicht mehr auf deiner Merkliste.');
    }
}
