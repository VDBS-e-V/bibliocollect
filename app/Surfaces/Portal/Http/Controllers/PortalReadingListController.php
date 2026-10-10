<?php

declare(strict_types=1);

namespace App\Surfaces\Portal\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use App\Modules\Catalog\Services\CatalogCoverService;
use App\Modules\Catalog\Services\CatalogHoldingService;
use App\Modules\Circulation\Models\Bookmark;
use App\Modules\Circulation\Models\ReadingList;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\SchoolClass;
use App\Surfaces\Public\Support\PublicCatalogPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Leselisten: Lehrkräfte stellen Titel für eine Klasse zusammen, die Schüler:innen der Klasse sehen die Liste in ihrem Konto.
 * Eine Liste ist nur für die eigene Klasse sichtbar, nie öffentlich.
 */
final class PortalReadingListController
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $patron = $this->patron($user);
        $hasClass = $patron instanceof Patron && $patron->school_class_id !== null;
        $mine = Gate::allows('reading_lists.manage')
            ? ReadingList::query()->with('schoolClass')->withCount('items')->where('user_id', $user->getKey())->orderByDesc('created_at')->get()
            : collect();
        $forClass = $hasClass
            ? ReadingList::query()->with('schoolClass')->withCount('items')->where('school_class_id', $patron->school_class_id)->where('is_published', true)
                ->where('user_id', '!=', $user->getKey())->orderByDesc('created_at')->get()->filter(static fn (ReadingList $list): bool => $list->isCurrent())->values()
            : collect();

        return response()
            ->view('pages.surfaces.portal-reading-lists', [
                'mine' => $mine,
                'forClass' => $forClass,
                'hasClass' => $hasClass,
                'classes' => $this->classes(),
                'canManage' => Gate::allows('reading_lists.manage'),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user($request);
        abort_unless(Gate::allows('reading_lists.manage'), 403);

        if (ReadingList::query()->where('user_id', $user->getKey())->count() >= ReadingList::LIST_LIMIT) {
            return back()->withInput()->with('portal_error', 'Du hast schon '.ReadingList::LIST_LIMIT.' Leselisten. Lösche zuerst eine alte.');
        }

        $list = ReadingList::query()->create(['user_id' => $user->getKey(), ...$this->validated($request)]);

        return redirect()->route('portal.reading-lists.show', ['listId' => $list->getKey()])->with('portal_success', 'Die Leseliste „'.$list->name.'“ ist angelegt. Füge jetzt Bücher hinzu.');
    }

    public function show(Request $request, string $listId, SearchCatalogTitlesQuery $search, CatalogHoldingService $holdings, CatalogCoverService $covers, CopyAvailabilityService $availability, PublicCatalogPresenter $presenter): Response
    {
        $user = $this->user($request);
        $list = ReadingList::query()->with('schoolClass')->findOrFail($listId);
        $owner = $this->owns($user, $list);

        abort_unless($owner || $this->classMemberSees($user, $list), 404);

        $ids = $list->titleIds();
        $rows = $this->rows($ids, $holdings, $covers, $availability, $presenter);
        $term = trim((string) $request->query('q', ''));
        $results = [];
        $fromBookmarks = [];

        if ($owner) {
            if ($term !== '') {
                $found = $search->execute($term, 10)->map(static fn (Title $title): string => (string) $title->getKey())->all();
                $results = $this->rows(array_values(array_diff($found, $ids)), $holdings, $covers, $availability, $presenter);
            }

            $bookmarked = Bookmark::query()->where('user_id', $user->getKey())->orderByDesc('created_at')->pluck('title_id')->map(static fn ($id): string => (string) $id)->all();
            $fromBookmarks = array_slice($this->rows(array_values(array_diff($bookmarked, $ids)), $holdings, $covers, $availability, $presenter), 0, 20);
        }

        return response()
            ->view('pages.surfaces.portal-reading-list', [
                'list' => $list,
                'rows' => $rows,
                'owner' => $owner,
                'term' => $term,
                'results' => $results,
                'fromBookmarks' => $fromBookmarks,
                'classes' => $this->classes(),
                'marked' => Bookmark::markedBy((int) $user->getKey(), array_column($rows, 'id')),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $listId): RedirectResponse
    {
        $list = $this->ownList($request, $listId);
        $list->update($this->validated($request));

        return redirect()->route('portal.reading-lists.show', ['listId' => $list->getKey()])->with('portal_success', 'Die Angaben sind gespeichert.');
    }

    public function destroy(Request $request, string $listId): RedirectResponse
    {
        $list = $this->ownList($request, $listId);
        $name = $list->name;
        $list->delete();

        return redirect()->route('portal.reading-lists')->with('portal_success', 'Die Leseliste „'.$name.'“ ist gelöscht.');
    }

    public function addItem(Request $request, string $listId, string $titleId): RedirectResponse
    {
        $list = $this->ownList($request, $listId);
        $title = Title::query()->findOrFail($titleId);

        if ($list->items()->count() >= ReadingList::ITEM_LIMIT) {
            return back()->with('portal_error', 'Eine Leseliste fasst höchstens '.ReadingList::ITEM_LIMIT.' Titel.');
        }

        $list->items()->firstOrCreate(['title_id' => $title->getKey()]);
        $list->touch();

        return redirect()->route('portal.reading-lists.show', ['listId' => $list->getKey(), 'q' => $request->input('q')])->with('portal_success', '„'.$title->preferred_title.'“ steht jetzt auf der Liste.');
    }

    public function removeItem(Request $request, string $listId, string $titleId): RedirectResponse
    {
        $list = $this->ownList($request, $listId);
        $list->items()->where('title_id', $titleId)->delete();
        $list->touch();

        return redirect()->route('portal.reading-lists.show', ['listId' => $list->getKey()])->with('portal_success', 'Der Titel ist von der Liste entfernt.');
    }

    /** @return array{name: string, school_class_id: ?string, description: ?string, ends_on: ?string, is_published: bool} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'school_class_id' => ['nullable', 'string', 'exists:school_classes,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'ends_on' => ['nullable', 'date'],
        ]);

        return [
            'name' => trim($data['name']),
            'school_class_id' => ($data['school_class_id'] ?? null) ?: null,
            'description' => isset($data['description']) && trim($data['description']) !== '' ? trim($data['description']) : null,
            'ends_on' => ($data['ends_on'] ?? null) ?: null,
            'is_published' => $request->boolean('is_published'),
        ];
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function patron(User $user): ?Patron
    {
        return $user->patron_id !== null ? Patron::query()->find($user->patron_id) : null;
    }

    private function owns(User $user, ReadingList $list): bool
    {
        return (int) $list->user_id === (int) $user->getKey() && Gate::allows('reading_lists.manage');
    }

    private function classMemberSees(User $user, ReadingList $list): bool
    {
        $patron = $this->patron($user);

        return $list->isVisibleToClass() && $patron instanceof Patron && $patron->school_class_id !== null && (string) $patron->school_class_id === (string) $list->school_class_id;
    }

    private function ownList(Request $request, string $listId): ReadingList
    {
        $user = $this->user($request);
        abort_unless(Gate::allows('reading_lists.manage'), 403);

        return ReadingList::query()->where('user_id', $user->getKey())->findOrFail($listId);
    }

    /** @return list<array{id: string, name: string}> */
    private function classes(): array
    {
        return SchoolClass::query()->where('is_active', true)->orderBy('grade_level')->orderBy('name')->get()
            ->map(static fn (SchoolClass $class): array => ['id' => (string) $class->getKey(), 'name' => $class->name])->all();
    }

    /**
     * Titel mit Cover und Verfügbarkeit; ausgesonderte Titel (ohne Exemplar im Bestand) bleiben draußen.
     *
     * @param  list<string>  $ids  in der gewünschten Reihenfolge
     * @return list<array{id: string, title: string, authors: string, cover: string, badge: string, variant: string}>
     */
    private function rows(array $ids, CatalogHoldingService $holdings, CatalogCoverService $covers, CopyAvailabilityService $availability, PublicCatalogPresenter $presenter): array
    {
        if ($ids === []) {
            return [];
        }

        $titles = Title::query()
            ->with(['contributions.contributor', 'editions.copies'])
            ->whereIn('id', $ids)
            ->whereHas('editions.copies', static fn ($copies) => $copies->whereIn('status', [CopyStatus::Active->value, CopyStatus::Damaged->value]))
            ->get()
            ->keyBy(static fn (Title $title): string => (string) $title->getKey());
        $availabilities = $availability->forTitles($titles->keys()->map(static fn ($id): string => (string) $id)->all());
        $rows = [];

        foreach ($ids as $id) {
            $title = $titles->get($id);

            if (! $title instanceof Title) {
                continue;
            }

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

        return $rows;
    }
}
