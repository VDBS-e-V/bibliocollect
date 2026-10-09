<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Exceptions\BookmarkLimitReached;
use App\Modules\Circulation\Models\Bookmark;

/** Merkt einen Titel oder nimmt ihn von der Merkliste. */
final class ToggleBookmarkAction
{
    /**
     * @return bool true, wenn der Titel danach gemerkt ist
     *
     * @throws BookmarkLimitReached
     */
    public function execute(int $userId, Title $title): bool
    {
        $existing = Bookmark::query()->where('user_id', $userId)->where('title_id', $title->getKey())->first();

        if ($existing instanceof Bookmark) {
            $existing->delete();

            return false;
        }

        if (Bookmark::query()->where('user_id', $userId)->count() >= Bookmark::LIMIT) {
            throw new BookmarkLimitReached('Deine Merkliste ist voll (höchstens '.Bookmark::LIMIT.' Titel). Entferne zuerst einen Titel.');
        }

        Bookmark::query()->create(['user_id' => $userId, 'title_id' => $title->getKey()]);

        return true;
    }
}
