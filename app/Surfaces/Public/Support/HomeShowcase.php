<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Support;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogCoverService;
use App\Modules\Catalog\Services\CatalogHoldingService;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Die Schaufenster der Startseite: empfohlene Medien, neue Medien und empfohlene Themen.
 *
 * Empfohlen ist, was die Bibliothek von Hand markiert hat; was fehlt, füllt das System automatisch auf (meistausgeliehene Titel,
 * Themen mit den meisten Titeln). Es erscheinen nur Titel mit mindestens einem vorhandenen Exemplar. Das Ergebnis ist kurz
 * zwischengespeichert, damit die Startseite auf einfachem Hosting schnell bleibt.
 */
final readonly class HomeShowcase
{
    public const CACHE_KEY = 'home-showcase.v1';

    private const RECOMMENDED_TITLES = 6;

    private const NEW_TITLES = 8;

    private const TOPICS = 6;

    private const NEW_DAYS = 60;

    private const LOAN_DAYS = 90;

    public function __construct(
        private CatalogCoverService $covers,
        private CatalogHoldingService $holdings,
        private CopyAvailabilityService $availability,
        private PublicCatalogPresenter $presenter,
    ) {}

    /** @return array{recommended: list<array<string, mixed>>, new: list<array<string, mixed>>, topics: list<array<string, mixed>>} */
    public function build(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), fn (): array => [
            'recommended' => $this->tiles($this->recommendedTitleIds(), self::RECOMMENDED_TITLES),
            'new' => $this->tiles($this->newTitleIds(), self::NEW_TITLES),
            'topics' => $this->topics(),
        ]);
    }

    /** @return list<string> */
    private function presentStatuses(): array
    {
        return [CopyStatus::Active->value, CopyStatus::Damaged->value];
    }

    /** Titel-IDs mit vorhandenem Exemplar, optional nur aus der Menge $only. */
    private function presentTitleQuery(): Builder
    {
        return DB::table('catalog_titles')
            ->join('catalog_editions', 'catalog_editions.title_id', '=', 'catalog_titles.id')
            ->join('catalog_copies', 'catalog_copies.edition_id', '=', 'catalog_editions.id')
            ->whereIn('catalog_copies.status', $this->presentStatuses());
    }

    /** @return list<string> */
    private function recommendedTitleIds(): array
    {
        $manual = $this->presentTitleQuery()
            ->whereNotNull('catalog_titles.featured_position')
            ->groupBy('catalog_titles.id', 'catalog_titles.featured_position')
            ->orderBy('catalog_titles.featured_position')
            ->limit(self::RECOMMENDED_TITLES)
            ->pluck('catalog_titles.id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        $missing = self::RECOMMENDED_TITLES - count($manual);

        if ($missing <= 0) {
            return $manual;
        }

        $popular = $this->presentTitleQuery()
            ->join('circulation_loans', 'circulation_loans.copy_id', '=', 'catalog_copies.id')
            ->where('circulation_loans.checked_out_at', '>=', now()->subDays(self::LOAN_DAYS))
            ->when($manual !== [], static fn ($query) => $query->whereNotIn('catalog_titles.id', $manual))
            ->groupBy('catalog_titles.id')
            ->orderByRaw('count(*) desc')
            ->limit($missing)
            ->pluck('catalog_titles.id')
            ->map(static fn ($id): string => (string) $id)
            ->all();

        return array_merge($manual, $popular);
    }

    /** @return list<string> */
    private function newTitleIds(): array
    {
        return $this->presentTitleQuery()
            ->where('catalog_copies.shelved_at', '>=', now()->subDays(self::NEW_DAYS))
            ->groupBy('catalog_titles.id')
            ->orderByRaw('max(catalog_copies.shelved_at) desc')
            ->limit(self::NEW_TITLES)
            ->pluck('catalog_titles.id')
            ->map(static fn ($id): string => (string) $id)
            ->all();
    }

    /**
     * @param  list<string>  $ids
     * @return list<array<string, mixed>>
     */
    private function tiles(array $ids, int $limit): array
    {
        if ($ids === []) {
            return [];
        }

        $titles = Title::query()->with(['contributions.contributor', 'editions.copies'])->whereIn('id', $ids)->get()->keyBy(static fn (Title $title): string => (string) $title->getKey());
        $availabilities = $this->availability->forTitles($ids);
        $tiles = [];

        foreach ($ids as $id) {
            $title = $titles->get($id);

            if (! $title instanceof Title) {
                continue;
            }

            $holding = $this->holdings->summarizeTitle($title);
            $availability = $availabilities[$id];

            $tiles[] = [
                'id' => $id,
                'title' => $title->preferred_title,
                'authors' => $title->contributions->pluck('contributor.display_name')->filter()->take(2)->join(', '),
                'cover' => $this->covers->localUrlForTitle($title) ?? asset('brand/vdbs/catalog-cover-placeholder.svg'),
                'badge' => $this->presenter->availabilityLabel($holding, $availability),
                'variant' => $this->presenter->availabilityVariant($holding, $availability),
            ];

            if (count($tiles) >= $limit) {
                break;
            }
        }

        return $tiles;
    }

    /** @return list<array<string, mixed>> */
    private function topics(): array
    {
        $counts = $this->presentTitleQuery()
            ->whereNotNull('catalog_editions.local_classification')
            ->groupBy(DB::raw('lower(catalog_editions.local_classification)'))
            ->selectRaw('lower(catalog_editions.local_classification) as name_key, count(distinct catalog_titles.id) as titles')
            ->pluck('titles', 'name_key');

        $topics = CatalogTopic::query()->get();
        $rows = [];

        foreach ($topics as $topic) {
            $count = (int) ($counts[mb_strtolower($topic->name)] ?? 0);

            if ($count === 0) {
                continue;
            }

            $rows[] = ['topic' => $topic, 'count' => $count, 'position' => $topic->featured_position];
        }

        usort($rows, static function (array $a, array $b): int {
            $aManual = $a['position'] !== null;
            $bManual = $b['position'] !== null;

            return match (true) {
                $aManual && $bManual => $a['position'] <=> $b['position'],
                $aManual => -1,
                $bManual => 1,
                default => $b['count'] <=> $a['count'] ?: strcmp($a['topic']->name, $b['topic']->name),
            };
        });

        return array_map(static fn (array $row): array => [
            'name' => $row['topic']->name,
            'description' => $row['topic']->description,
            'count' => $row['count'],
            'key' => $row['topic']->publicSlug(),
            'featured' => $row['position'] !== null,
        ], array_slice($rows, 0, self::TOPICS));
    }
}
