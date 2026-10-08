<?php

declare(strict_types=1);

namespace App\Surfaces\Pos\Http\Controllers;

use App\Models\User;
use App\Modules\Circulation\Actions\CreateBookWishAction;
use App\Modules\Circulation\Actions\DecideBookWishAction;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Queries\SearchPatronsQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Buchwünsche am Arbeitsplatz: ansehen, selbst erfassen (auch ohne Person) und den Stand setzen. */
final class WishController
{
    public function index(Request $request): Response
    {
        [$filter, $term] = $this->filters($request);

        $wishes = $this->filtered($filter, $term)->limit(200)->get();

        // Wie oft wird derselbe Titel noch gewünscht? Mehrere Wünsche sprechen für eine Anschaffung.
        $similar = [];

        foreach ($wishes as $wish) {
            $similar[(string) $wish->getKey()] = BookWish::query()
                ->whereKeyNot($wish->getKey())
                ->whereIn('status', WishStatus::openValues())
                ->where(static function ($query) use ($wish): void {
                    $query->whereRaw('lower(title) = ?', [mb_strtolower($wish->title)]);

                    if ($wish->isbn !== null) {
                        $query->orWhere('isbn', $wish->isbn);
                    }
                })
                ->count();
        }

        return response()
            ->view('pages.surfaces.pos.wishes.index', [
                'wishes' => $wishes,
                'similar' => $similar,
                'filter' => $filter,
                'term' => $term,
                'statuses' => WishStatus::cases(),
                'counts' => BookWish::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
                'total' => BookWish::query()->count(),
                'matching' => $this->filtered($filter, $term)->count(),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Druckfassung der Liste (Querformat, Briefpapier): alle Wünsche zum gewählten Filter, ohne die 200er-Grenze der Übersicht. */
    public function print(Request $request): Response
    {
        [$filter, $term] = $this->filters($request);
        $wishes = $this->filtered($filter, $term)->get();

        return response()
            ->view('pages.surfaces.pos.wishes.print', [
                'wishes' => $wishes,
                'filterLabel' => match (true) {
                    $filter === 'offen' => 'Offene Wünsche',
                    $filter === 'alle' => 'Alle Wünsche',
                    WishStatus::tryFrom($filter) !== null => WishStatus::from($filter)->label(),
                    default => 'Wünsche',
                },
                'term' => $term,
                'total' => BookWish::query()->count(),
                'letterhead' => (string) $request->query('briefpapier', (string) config('foundation.letterhead', 'farbe')),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** @return array{0: string, 1: string} */
    private function filters(Request $request): array
    {
        return [(string) $request->query('status', 'offen'), trim((string) $request->query('q', ''))];
    }

    /** @return Builder<BookWish> */
    private function filtered(string $filter, string $term): Builder
    {
        return BookWish::query()
            ->with('patron.schoolClass')
            ->when($filter === 'offen', static fn ($query) => $query->whereIn('status', WishStatus::openValues()))
            ->when(WishStatus::tryFrom($filter) !== null, static fn ($query) => $query->where('status', $filter))
            ->when($term !== '', static function ($query) use ($term): void {
                $like = '%'.$term.'%';
                $query->where(static function ($inner) use ($like): void {
                    $inner->where('title', 'like', $like)->orWhere('author', 'like', $like)->orWhere('isbn', 'like', $like);
                });
            })
            ->orderByRaw("case status when 'new' then 0 when 'accepted' then 1 when 'ordered' then 2 else 3 end")
            ->orderBy('created_at');
    }

    /** Eigene Seite zum Erfassen: ISBN-Abfrage und Namenssuche für die Person (ohne Skript über „Person suchen“). */
    public function create(Request $request, SearchPatronsQuery $patrons): Response
    {
        $term = trim((string) $request->query('person', ''));
        $selected = trim((string) $request->query('patron_id', ''));

        return response()
            ->view('pages.surfaces.pos.wishes.create', [
                'term' => $term,
                'matches' => $term !== '' ? $patrons->execute($term, 15)->filter(static fn (Patron $patron): bool => $patron->status === PatronStatus::Active)->values() : collect(),
                'selected' => $selected,
                'searched' => $term !== '',
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Wunsch vom Tresen erfassen, mit oder ohne Person (Auswahl nach Namenssuche oder Bibliotheksnummer). */
    public function store(Request $request, CreateBookWishAction $create): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
            'library_number' => ['nullable', 'string', 'max:40'],
            'patron_id' => ['nullable', 'string', 'max:40'],
        ], ['title.required' => 'Bitte einen Titel angeben.']);

        $patron = null;

        if (($data['patron_id'] ?? '') !== '') {
            $patron = Patron::query()->where('status', PatronStatus::Active->value)->find($data['patron_id']);

            if (! $patron instanceof Patron) {
                return back()->withInput()->withErrors(['person' => 'Diese Person gibt es nicht oder das Ausleihkonto ist nicht aktiv. Bitte neu suchen.']);
            }
        }

        if ($patron === null && ($data['library_number'] ?? '') !== '') {
            $patron = Patron::query()->where('status', PatronStatus::Active->value)->whereRaw('lower(library_number) = ?', [mb_strtolower(trim($data['library_number']))])->first();

            if (! $patron instanceof Patron) {
                return back()->withInput()->withErrors(['library_number' => 'Diese Bibliotheksnummer gibt es nicht oder das Ausleihkonto ist nicht aktiv.']);
            }
        }

        try {
            $create->execute($patron, $data['title'], $data['author'] ?? null, $data['isbn'] ?? null, $data['note'] ?? null);
        } catch (CirculationRuleViolation $exception) {
            return back()->withInput()->withErrors(['title' => $exception->getMessage()]);
        }

        return redirect()->route('pos.wishes.index')->with('wish_notice', 'Der Wunsch „'.trim($data['title']).'“ ist erfasst.');
    }

    public function update(Request $request, string $wishId, DecideBookWishAction $decide): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(static fn (WishStatus $case): string => $case->value, array_filter(WishStatus::cases(), static fn (WishStatus $case): bool => $case->isDecision())))],
            'answer' => ['nullable', 'string', 'max:500'],
        ], ['status.required' => 'Bitte einen Stand wählen.']);

        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $wish = BookWish::query()->findOrFail($wishId);
        $status = WishStatus::from($data['status']);

        $decide->execute($wish, $status, $data['answer'] ?? null, $actor);

        return redirect()->route('pos.wishes.index', array_filter(['status' => $request->query('status'), 'q' => $request->query('q')]))
            ->with('wish_notice', 'Der Wunsch „'.$wish->title.'“ steht jetzt auf „'.$status->label().'“.');
    }
}
