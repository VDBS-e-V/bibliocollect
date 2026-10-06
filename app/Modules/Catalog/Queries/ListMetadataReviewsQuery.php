<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Enums\MetadataIssue;
use App\Modules\Catalog\Enums\MetadataReviewStatus;
use App\Modules\Catalog\Models\CatalogMetadataReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Liste und Zähler der Metadaten-Prüffälle.
 *
 * Ohne Problemfilter zeigen offene Fälle nur echte Mängel (Schwere > 0). Reine Anreicherung
 * (fehlende Zusammenfassung oder Schlagwörter) erscheint nur, wenn man gezielt danach filtert.
 */
final class ListMetadataReviewsQuery
{
    /**
     * @param  array{status: string, problem: string|null, q: string|null}  $filters
     * @return LengthAwarePaginator<int, CatalogMetadataReview>
     */
    public function paginate(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        return $this->base($filters)
            ->with('edition.title')
            ->paginate(max(1, min($perPage, 100)), ['catalog_metadata_reviews.*'], 'page', max(1, $page));
    }

    /**
     * IDs aller offenen Mängel in der Reihenfolge der Liste; Grundlage für "nächster Fall".
     *
     * @return list<string>
     */
    public function openDefectIds(): array
    {
        return $this->base(['status' => MetadataReviewStatus::Open->value, 'problem' => null, 'q' => null])
            ->pluck('catalog_metadata_reviews.id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all();
    }

    /** Nächster offener Mangel nach dem angegebenen Fall (ohne ihn selbst), sonst null. */
    public function nextOpenDefectId(string $afterId): ?string
    {
        $ids = $this->openDefectIds();
        $position = array_search($afterId, $ids, true);

        if ($position === false) {
            return $ids[0] ?? null;
        }

        return $ids[$position + 1] ?? null;
    }

    /** @return array{open_defects: int, open_enrichment: int, dismissed: int, resolved: int, by_problem: array<string, int>} */
    public function counts(): array
    {
        $byProblem = [];

        foreach (MetadataIssue::cases() as $issue) {
            $byProblem[$issue->value] = CatalogMetadataReview::query()
                ->where('status', MetadataReviewStatus::Open->value)
                ->whereJsonContains('issues', $issue->value)
                ->count();
        }

        $open = static fn (): Builder => CatalogMetadataReview::query()->where('status', MetadataReviewStatus::Open->value);

        return [
            'open_defects' => $open()->where('severity', '>', 0)->count(),
            'open_enrichment' => $open()->where('severity', 0)->count(),
            'dismissed' => CatalogMetadataReview::query()->where('status', MetadataReviewStatus::Dismissed->value)->count(),
            'resolved' => CatalogMetadataReview::query()->where('status', MetadataReviewStatus::Resolved->value)->count(),
            'by_problem' => $byProblem,
        ];
    }

    /**
     * @param  array{status: string, problem: string|null, q: string|null}  $filters
     * @return Builder<CatalogMetadataReview>
     */
    private function base(array $filters): Builder
    {
        $query = CatalogMetadataReview::query()
            ->join('catalog_editions as editions', 'editions.id', '=', 'catalog_metadata_reviews.edition_id')
            ->join('catalog_titles as titles', 'titles.id', '=', 'editions.title_id')
            ->select('catalog_metadata_reviews.*')
            ->where('catalog_metadata_reviews.status', $filters['status']);

        if ($filters['problem'] !== null) {
            $query->whereJsonContains('catalog_metadata_reviews.issues', $filters['problem']);
        } elseif ($filters['status'] === MetadataReviewStatus::Open->value) {
            $query->where('catalog_metadata_reviews.severity', '>', 0);
        }

        if ($filters['q'] !== null && $filters['q'] !== '') {
            $like = '%'.addcslashes($filters['q'], '%_\\').'%';

            $query->where(static function (Builder $search) use ($like): void {
                $search->where('titles.preferred_title', 'like', $like)
                    ->orWhere('titles.subtitle', 'like', $like)
                    ->orWhere('editions.isbn', 'like', $like)
                    ->orWhere('editions.publisher_name', 'like', $like);
            });
        }

        return $query
            ->orderByDesc('catalog_metadata_reviews.severity')
            ->orderBy('titles.preferred_title')
            ->orderBy('catalog_metadata_reviews.id');
    }
}
