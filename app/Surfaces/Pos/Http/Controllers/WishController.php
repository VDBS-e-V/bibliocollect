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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/** Buchwünsche am Arbeitsplatz: ansehen, selbst erfassen (auch ohne Person) und den Stand setzen. */
final class WishController
{
    public function index(Request $request): Response
    {
        $filter = (string) $request->query('status', 'offen');
        $term = trim((string) $request->query('q', ''));

        $wishes = BookWish::query()
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
            ->orderBy('created_at')
            ->limit(200)
            ->get();

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
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    /** Wunsch vom Tresen erfassen, mit oder ohne Person (Bibliotheksnummer). */
    public function store(Request $request, CreateBookWishAction $create): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:500'],
            'library_number' => ['nullable', 'string', 'max:40'],
        ], ['title.required' => 'Bitte einen Titel angeben.']);

        $patron = null;

        if (($data['library_number'] ?? '') !== '') {
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
