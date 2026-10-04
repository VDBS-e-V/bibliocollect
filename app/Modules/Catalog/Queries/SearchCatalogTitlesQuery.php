<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class SearchCatalogTitlesQuery
{
    /** @return Collection<int, Title> */
    public function execute(string $term, int $limit = 25): Collection
    {
        $term = trim(str_replace(['%', '_'], '', $term));
        $searchableCharacters = preg_replace('/[^\p{L}\p{N}]+/u', '', $term) ?? '';

        if (mb_strlen($searchableCharacters) < 2) {
            /** @var Collection<int, Title> $empty */
            $empty = new Collection;

            return $empty;
        }

        $tokens = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $query = Title::query()->with(['contributions.contributor', 'editions']);

        foreach ($tokens as $token) {
            $like = '%'.$token.'%';

            $query->where(function (Builder $titleQuery) use ($like): void {
                $titleQuery
                    ->whereAny(['preferred_title', 'subtitle', 'sort_title'], 'like', $like)
                    ->orWhereHas('contributions.contributor', function (Builder $contributorQuery) use ($like): void {
                        $contributorQuery->whereAny(['display_name', 'sort_name'], 'like', $like);
                    })
                    ->orWhereHas('editions', function (Builder $editionQuery) use ($like): void {
                        $editionQuery->whereAny(
                            ['isbn', 'publisher_name', 'media_type', 'language_code'],
                            'like',
                            $like,
                        );
                    });
            });
        }

        return $query
            ->orderBy('preferred_title')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }
}
