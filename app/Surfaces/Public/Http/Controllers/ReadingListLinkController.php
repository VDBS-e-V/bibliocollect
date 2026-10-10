<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Models\User;
use App\Modules\Circulation\Models\Bookmark;
use App\Modules\Circulation\Models\ReadingList;
use App\Surfaces\Public\Support\ReadingListPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Öffentlicher Link einer Leseliste: ohne Konto sichtbar, aber nur mit dem nicht erratbaren Kennzeichen und nicht in Suchmaschinen. */
final class ReadingListLinkController
{
    public function __invoke(Request $request, string $token, ReadingListPresenter $presenter): Response
    {
        $list = ReadingList::query()->with('classes')->where('public_token', $token)->first();

        abort_unless($list instanceof ReadingList && $list->isActive(), 404);

        $rows = $presenter->rows($list->titleIds());
        $user = $request->user();

        return response()
            ->view('pages.surfaces.public.reading-list', [
                'list' => $list,
                'rows' => $rows,
                'marked' => $user instanceof User ? Bookmark::markedBy((int) $user->getKey(), array_column($rows, 'id')) : [],
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
