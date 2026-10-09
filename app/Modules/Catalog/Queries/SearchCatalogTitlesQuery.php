<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\DTOs\CatalogSearchCriteria;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Edition;
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

        return $this->limited(
            new CatalogSearchCriteria(term: $normalized),
            $limit,
        );
    }

    /** @return Collection<int, Title> */
    public function limited(CatalogSearchCriteria $criteria, int $limit = 50): Collection
    {
        $query = $this->baseQuery();
        $this->applyCriteria($query, $criteria);
        $this->applySort($query, $criteria->sort);

        return $query
            ->limit(max(1, min($limit, 100)))
            ->get();
    }

    /** @return LengthAwarePaginator<int, Title> */
    public function paginate(CatalogSearchCriteria $criteria): LengthAwarePaginator
    {
        $query = $this->baseQuery();
        $this->applyCriteria($query, $criteria);
        $this->applySort($query, $criteria->sort);

        return $query->paginate(
            max(1, min($criteria->perPage, 100)),
            ['*'],
            'page',
            max(1, $criteria->page),
        );
    }

    /** @return Builder<Title> */
    private function baseQuery(): Builder
    {
        return Title::query()
            ->with([
                'contributions.contributor',
                'editions.copies.shelf.topics',
            ])
            ->withMax('editions', 'publication_year');
    }

    /** @param Builder<Title> $query */
    private function applyCriteria(Builder $query, CatalogSearchCriteria $criteria): void
    {
        $rawTerm = trim($criteria->term ?? '');
        $normalized = $this->normalizeTerm($criteria->term, true);

        if ($rawTerm !== '' && $normalized === null) {
            $query->whereRaw('1 = 0');
        } elseif ($normalized !== null && $normalized !== '') {
            $this->applyTerm($query, $normalized);
        }

        $this->applyTextFilter(
            $query,
            $criteria->title,
            static function (Builder $titleQuery, string $like): void {
                $titleQuery->whereAny(['preferred_title', 'subtitle', 'sort_title'], 'like', $like);
            },
        );

        $this->applyTextFilter(
            $query,
            $criteria->contributor,
            static function (Builder $titleQuery, string $like): void {
                $titleQuery->whereHas('contributions.contributor', function (Builder $contributorQuery) use ($like): void {
                    $contributorQuery->whereAny(['display_name', 'sort_name', 'gnd_id'], 'like', $like);
                });
            },
        );

        if (! $this->hasEditionFilters($criteria)) {
            return;
        }

        $query->whereHas('editions', function (Builder $editionQuery) use ($criteria): void {
            $this->applyEditionTextFilter($editionQuery, $criteria->subject, [
                'subject_keywords',
                'subject_keywords_system',
                'summary',
            ]);
            $this->applyEditionTextFilter($editionQuery, $criteria->identifier, [
                'isbn',
                'issn',
                'doi_handle',
                'source_record_id',
            ]);
            $this->applyEditionTextFilter($editionQuery, $criteria->publisher, ['publisher_name']);
            $this->applyEditionTextFilter($editionQuery, $criteria->publicationPlace, ['publication_place']);
            $this->applyEditionTextFilter($editionQuery, $criteria->series, ['series_statement']);
            $this->applyEditionTextFilter($editionQuery, $criteria->classification, ['local_classification']);
            $this->applyEditionTextFilter($editionQuery, $criteria->targetAudience, ['target_audience']);
            $this->applyEditionTextFilter($editionQuery, $criteria->sourceRecordId, ['source_record_id']);

            if ($criteria->topic !== null) {
                $topic = $this->normalizeFilter($criteria->topic);

                if ($topic === null) {
                    $editionQuery->whereRaw('1 = 0');
                } else {
                    $like = '%'.$topic.'%';
                    $editionQuery->where(function (Builder $topicOrClassificationQuery) use ($like): void {
                        $topicOrClassificationQuery
                            ->where('local_classification', 'like', $like)
                            ->orWhereHas('copies.shelf.topics', function (Builder $topicQuery) use ($like): void {
                                $topicQuery->where(function (Builder $nested) use ($like): void {
                                    $nested
                                        ->whereAny(['name', 'description'], 'like', $like)
                                        ->orWhere('public_key', 'like', $like);
                                });
                            });
                    });
                }
            }

            if ($criteria->shelf !== null) {
                $shelf = $criteria->shelf;
                $editionQuery->whereHas('copies', static function (Builder $copyQuery) use ($shelf): void {
                    $copyQuery->whereRaw('LOWER(shelf_location) = ?', [mb_strtolower($shelf)]);
                });
            }

            if ($criteria->yearFrom !== null) {
                $editionQuery->where('publication_year', '>=', $criteria->yearFrom);
            }

            if ($criteria->yearTo !== null) {
                $editionQuery->where('publication_year', '<=', $criteria->yearTo);
            }

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

            // „Jetzt verfügbar“: ein aktives Exemplar, das weder ausgeliehen noch für eine Vormerkung zurückgelegt ist.
            // Ausleihen und Vormerkungen gehören zum Modul Ausleihe; hier genügen deren Tabellennamen.
            if ($criteria->availableNowOnly) {
                $editionQuery->whereHas('copies', function (Builder $copyQuery): void {
                    $copyQuery
                        ->where('status', CopyStatus::Active->value)
                        ->whereNotExists(static function ($loans): void {
                            $loans->selectRaw('1')->from('circulation_loans')->whereColumn('circulation_loans.copy_id', 'catalog_copies.id')->whereNull('circulation_loans.returned_at');
                        })
                        ->whereNotExists(static function ($held): void {
                            $held->selectRaw('1')->from('circulation_reservations')->whereColumn('circulation_reservations.ready_copy_id', 'catalog_copies.id')->where('circulation_reservations.status', 'ready');
                        });
                });
            }
        });
    }

    private function hasEditionFilters(CatalogSearchCriteria $criteria): bool
    {
        return $criteria->subject !== null
            || $criteria->identifier !== null
            || $criteria->publisher !== null
            || $criteria->publicationPlace !== null
            || $criteria->series !== null
            || $criteria->topic !== null
            || $criteria->shelf !== null
            || $criteria->classification !== null
            || $criteria->targetAudience !== null
            || $criteria->sourceRecordId !== null
            || $criteria->yearFrom !== null
            || $criteria->yearTo !== null
            || $criteria->mediaType !== null
            || $criteria->languageCode !== null
            || $criteria->activeCopiesOnly
            || $criteria->availableNowOnly;
    }

    /**
     * @param  Builder<Title>  $query
     * @param  callable(Builder<Title>, string): void  $callback
     */
    private function applyTextFilter(Builder $query, ?string $value, callable $callback): void
    {
        if ($value === null) {
            return;
        }

        $normalized = $this->normalizeFilter($value);

        if ($normalized === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $callback($query, '%'.$normalized.'%');
    }

    /**
     * @param  Builder<Edition>  $query
     * @param  list<string>  $columns
     */
    private function applyEditionTextFilter(Builder $query, ?string $value, array $columns): void
    {
        if ($value === null) {
            return;
        }

        $normalized = $this->normalizeFilter($value);

        if ($normalized === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereAny($columns, 'like', '%'.$normalized.'%');
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
                        $editionQuery
                            ->whereAny(
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
                            )
                            ->orWhereHas('copies.shelf.topics', function (Builder $topicQuery) use ($like): void {
                                $topicQuery->whereAny(['name', 'description', 'public_key'], 'like', $like);
                            });
                    });
            });
        }
    }

    /** @param Builder<Title> $query */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'title_desc' => $query->orderByDesc('preferred_title'),
            'year_desc' => $query
                ->orderByRaw('CASE WHEN editions_max_publication_year IS NULL THEN 1 ELSE 0 END')
                ->orderByDesc('editions_max_publication_year')
                ->orderBy('preferred_title'),
            'year_asc' => $query
                ->orderByRaw('CASE WHEN editions_max_publication_year IS NULL THEN 1 ELSE 0 END')
                ->orderBy('editions_max_publication_year')
                ->orderBy('preferred_title'),
            'recent' => $query->orderByDesc('created_at')->orderBy('preferred_title'),
            default => $query->orderBy('preferred_title'),
        };
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

    private function normalizeFilter(string $value): ?string
    {
        $normalized = trim(str_replace(['%', '_'], '', $value));
        $searchableCharacters = preg_replace('/[^\p{L}\p{N}]+/u', '', $normalized) ?? '';

        return $searchableCharacters === '' ? null : $normalized;
    }
}
