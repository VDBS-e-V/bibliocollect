<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Support;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogCoverService;
use App\Modules\Catalog\Services\CatalogHoldingService;
use App\Modules\Circulation\Services\CopyAvailabilityService;

/** Zeilen für Leselisten: Titel mit Cover und Verfügbarkeit (im Konto und auf der öffentlichen Seite). */
final readonly class ReadingListPresenter
{
    public function __construct(
        private CatalogHoldingService $holdings,
        private CatalogCoverService $covers,
        private CopyAvailabilityService $availability,
        private PublicCatalogPresenter $presenter,
    ) {}

    /**
     * Ausgesonderte Titel (ohne Exemplar im Bestand) bleiben draußen.
     *
     * @param  list<string>  $ids  in der gewünschten Reihenfolge
     * @return list<array{id: string, title: string, authors: string, cover: string, badge: string, variant: string}>
     */
    public function rows(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $titles = Title::query()
            ->with(['contributions.contributor', 'editions.copies'])
            ->whereIn('id', $ids)
            ->whereHas('editions.copies', static fn ($copies) => $copies->whereIn('status', [CopyStatus::Active->value, CopyStatus::Damaged->value]))
            ->get()
            ->keyBy(static fn (Title $title): string => (string) $title->getKey());
        $availabilities = $this->availability->forTitles($titles->keys()->map(static fn ($id): string => (string) $id)->all());
        $rows = [];

        foreach ($ids as $id) {
            $title = $titles->get($id);

            if (! $title instanceof Title) {
                continue;
            }

            $holding = $this->holdings->summarizeTitle($title);
            $rows[] = [
                'id' => $id,
                'title' => $title->preferred_title,
                'authors' => $title->contributions->pluck('contributor.display_name')->filter()->take(3)->join(', '),
                'cover' => $this->covers->localUrlForTitle($title) ?? asset('brand/vdbs/catalog-cover-placeholder.svg'),
                'badge' => $this->presenter->availabilityLabel($holding, $availabilities[$id]),
                'variant' => $this->presenter->availabilityVariant($holding, $availabilities[$id]),
            ];
        }

        return $rows;
    }
}
