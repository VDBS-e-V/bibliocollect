<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\DTOs\CatalogSearchCriteria;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Title;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class SearchCatalogTitlesQuery
{
    /** @return Collection<int, Title> */
    public function execute(string $term, int $limit = 25): Collection
    {
        $normalized = $this->normalizeTerm($term, false);

        if ($normalized === null) {
            /** @var Collection<int, Title> $empty */
            $empty = new Collection;

            return $empty;
        }

        $query = Title::query()->with(['contributions.contributor', 'editions']);
        $this->applyTerm($query, $normalized);

        return $query
            ->orderBy('preferred_title')
            ->limit(max(1, min($limit, 50)))
            ->get();
    }

    /** @return LengthAwarePaginator<int, Title> */
    public function paginate(CatalogSearchCriteria $criteria): LengthAwarePaginator
    {
        $query = Title::query()->with([
            'contributions.contributor',
            'editions.copies',
        ]);

        $normalized = $this->normalizeTerm($criteria->term, true);

        if ($normalized === null) {
            $query->whereRaw('1 = 0');
        } elseif ($normalized !== '') {
            $this->applyTerm($query, $normalized);
        }

        if (
            $criteria->mediaType !== null
            || $criteria->languageCode !== null
            || $criteria->activeCopiesOnly
        ) {
            $query->whereHas('editions', function (Builder $editionQuery) use ($criteria): void {
                if ($criteria->mediaType !== null) {
                    $editionQuery->whereRaw('LOWER(media_type) = ?', [mb_strtolower($criteria->mediaType)]);
                }

                if ($criteria->languageCode !== null) {
                    $editionQuery->whereRaw('LOWER(language_code) = ?', [mb_strtolower($criteria->languageCode)]);
                }

                if ($criteria->activeCopiesOnly) {
                    $editionQuery->whereHas('copies', function (Builder $copyQuery): void {
                        $copyQuery->where('status', CopyStatus::Active->value);
                    });
                }
            });
        }

        if ($criteria->sort === 'recent') {
            $query->orderByDesc('created_at')->orderBy('preferred_title');
        } else {
            $query->orderBy('preferred_title');
        }

        return $query->paginate(
            max(1, min($criteria->perPage, 50)),
            ['*'],
            'page',
            max(1, $criteria->page),
        );
    }

    /** @param Builder<Title> $query */
    private function applyTerm(Builder $query, string $term): void
    {
        $tokens = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $like = '%'.$token.'%';

            $query->where(function (Builder $titleQuery) use ($like): void {
                $titleQuery
                    ->whereAny(['preferred_title', 'subtitle', 'sort_title'], 'like', $like)
                    ->orWhereHas('contributions.contributor', function (Builder $contributorQuery) use ($like): void {
                        $contributorQuery->whereAny(['display_name', 'sort_name', 'gnd_id'], 'like', $like);
                    })
                    ->orWhereHas('editions', function (Builder $editionQuery) use ($like): void {
                        $editionQuery->whereAny(
                            [
                                'isbn',
                                'issn',
                                'doi_handle',
                                'publisher_name',
                                'publication_place',
                                'edition_statement',
                                'edition_number',
                                'series_statement',
                                'responsibility_statement',
                                'media_type',
                                'language_code',
                                'original_language_code',
                                'physical_extent',
                                'local_classification',
                                'subject_keywords',
                                'subject_keywords_system',
                                'target_audience',
                                'summary',
                                'source_record_id',
                            ],
                            'like',
                            $like,
                        );
                    });
            });
        }
    }

    private function normalizeTerm(?string $term, bool $allowEmpty): ?string
    {
        $raw = trim($term ?? '');

        if ($raw === '') {
            return $allowEmpty ? '' : null;
        }

        $normalized = trim(str_replace(['%', '_'], '', $raw));
        $searchableCharacters = preg_replace('/[^\p{L}\p{N}]+/u', '', $normalized) ?? '';

        if (mb_strlen($searchableCharacters) < 2) {
            return null;
        }

        return $normalized;
    }
}
