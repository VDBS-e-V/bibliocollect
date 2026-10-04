<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Queries;

use App\Modules\Patrons\Models\Patron;
use Illuminate\Database\Eloquent\Collection;

final class SearchPatronsQuery
{
    /**
     * @return Collection<int, Patron>
     */
    public function execute(string $term, int $limit = 25): Collection
    {
        $term = trim(str_replace(['%', '_'], '', $term));
        $searchableCharacters = preg_replace('/[^\p{L}\p{N}]+/u', '', $term) ?? '';

        if (mb_strlen($searchableCharacters) < 2) {
            /** @var Collection<int, Patron> $empty */
            $empty = new Collection();

            return $empty;
        }

        $query = Patron::query()->with('schoolClass');
        $tokens = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $query->whereAny(
                ['library_number', 'first_name', 'last_name'],
                'like',
                '%'.$token.'%',
            );
        }

        return $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }
}
