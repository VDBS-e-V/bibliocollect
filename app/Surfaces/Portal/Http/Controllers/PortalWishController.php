<?php

declare(strict_types=1);

namespace App\Surfaces\Portal\Http\Controllers;

use App\Models\User;
use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Circulation\Models\BookWish;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Buchwünsche in „Mein Konto“: eigene Wünsche ansehen, neue abgeben, offene zurückziehen. */
final class PortalWishController
{
    public function index(Request $request): Response
    {
        $patron = $this->patron($request);

        return response()
            ->view('pages.surfaces.portal-wishes', [
                'patron' => $patron,
                'wishes' => $patron instanceof Patron ? BookWish::query()->where('patron_id', $patron->getKey())->orderByDesc('created_at')->limit(50)->get() : collect(),
                'maxOpen' => max(1, (int) config('circulation.max_open_wishes', 3)),
            ])
            ->header('Cache-Control', 'private, no-store');
    }

    public function withdraw(Request $request, string $wishId): RedirectResponse
    {
        $patron = $this->patron($request);
        abort_unless($patron instanceof Patron, 403);

        $wish = BookWish::query()->where('patron_id', $patron->getKey())->findOrFail($wishId);

        if (! $wish->status->isOpen()) {
            return redirect()->route('portal.wishes.index')->with('portal_error', 'Dieser Wunsch ist schon abgeschlossen.');
        }

        $wish->forceFill(['status' => WishStatus::Withdrawn])->save();

        return redirect()->route('portal.wishes.index')->with('portal_success', 'Dein Wunsch wurde zurückgezogen.');
    }

    private function patron(Request $request): ?Patron
    {
        $user = $request->user();

        return $user instanceof User && $user->patron_id !== null ? Patron::query()->find($user->patron_id) : null;
    }
}
