<?php

declare(strict_types=1);

namespace App\Surfaces\Portal\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Queries\SearchCatalogTitlesQuery;
use App\Modules\Circulation\Models\Bookmark;
use App\Modules\Circulation\Models\ReadingList;
use App\Modules\Patrons\Models\Patron;
use App\Modules\School\Models\SchoolClass;
use App\Surfaces\Public\Support\ReadingListPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Leselisten: Lehrkräfte stellen Titel für eine oder mehrere Klassen zusammen. Die Schüler:innen der Klassen sehen die Liste in ihrem
 * Konto; jede Liste hat außerdem einen öffentlichen Link, der ohne Konto funktioniert (siehe ReadingListLinkController).
 */
final class PortalReadingListController
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $patron = $this->patron($user);
        $hasClass = $patron instanceof Patron && $patron->school_class_id !== null;
        $mine = Gate::allows('reading_lists.manage')
            ? ReadingList::query()->with('classes')->withCount('items')->where('user_id', $user->getKey())->orderByDesc('created_at')->get()
            : collect();
        $forClass = $hasClass
            ? ReadingList::query()->with('classes')->withCount('items')->whereHas('classes', static fn ($classes) => $classes->where('school_classes.id', $patron->school_class_id))
                ->where('is_published', true)->where('user_id', '!=', $user->getKey())->orderByDesc('created_at')->get()->filter(static fn (ReadingList $list): bool => $list->isCurrent())->values()
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

        $data = $this->validated($request);
        $classIds = $data['class_ids'];
        unset($data['class_ids']);

        $list = ReadingList::query()->create(['user_id' => $user->getKey(), ...$data]);
        $list->classes()->sync($classIds);

        return redirect()->route('portal.reading-lists.show', ['listId' => $list->getKey()])->with('portal_success', 'Die Leseliste „'.$list->name.'“ ist angelegt. Füge jetzt Bücher hinzu.');
    }

    public function show(Request $request, string $listId, SearchCatalogTitlesQuery $search, ReadingListPresenter $presenter): Response
    {
        $user = $this->user($request);
        $list = ReadingList::query()->with('classes')->findOrFail($listId);
        $owner = $this->owns($user, $list);

        abort_unless($owner || $this->classMemberSees($user, $list), 404);

        $ids = $list->titleIds();
        $rows = $presenter->rows($ids);
        $term = trim((string) $request->query('q', ''));
        $results = [];
        $fromBookmarks = [];

        if ($owner) {
            if ($term !== '') {
                $found = $search->execute($term, 10)->map(static fn (Title $title): string => (string) $title->getKey())->all();
                $results = $presenter->rows(array_values(array_diff($found, $ids)));
            }

            $bookmarked = Bookmark::query()->where('user_id', $user->getKey())->orderByDesc('created_at')->pluck('title_id')->map(static fn ($id): string => (string) $id)->all();
            $fromBookmarks = array_slice($presenter->rows(array_values(array_diff($bookmarked, $ids))), 0, 20);
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
        $data = $this->validated($request);
        $list->classes()->sync($data['class_ids']);
        unset($data['class_ids']);
        $list->update($data);

        return redirect()->route('portal.reading-lists.show', ['listId' => $list->getKey()])->with('portal_success', 'Die Angaben sind gespeichert.');
    }

    public function destroy(Request $request, string $listId): RedirectResponse
    {
        $list = $this->ownList($request, $listId);
        $name = $list->name;
        $list->delete();

        return redirect()->route('portal.reading-lists')->with('portal_success', 'Die Leseliste „'.$name.'“ ist gelöscht.');
    }

    public function renewLink(Request $request, string $listId): RedirectResponse
    {
        $list = $this->ownList($request, $listId);
        $list->regenerateToken();

        return redirect()->route('portal.reading-lists.show', ['listId' => $list->getKey()])->with('portal_success', 'Der Link ist erneuert. Der alte Link funktioniert nicht mehr.');
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

    /** @return array{name: string, class_ids: list<string>, description: ?string, ends_on: ?string, is_published: bool} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'school_class_ids' => ['nullable', 'array', 'max:40'],
            'school_class_ids.*' => ['string', 'exists:school_classes,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'ends_on' => ['nullable', 'date'],
        ]);

        return [
            'name' => trim($data['name']),
            'class_ids' => array_values(array_unique(array_map('strval', $data['school_class_ids'] ?? []))),
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

        return $list->isActive() && $patron instanceof Patron && $patron->school_class_id !== null
            && $list->classes->contains(static fn (SchoolClass $class): bool => (string) $class->getKey() === (string) $patron->school_class_id);
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
}
