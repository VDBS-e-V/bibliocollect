<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Circulation\DTOs\CopyAvailability;
use App\Modules\Circulation\Enums\ReservationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Leitet die Verfügbarkeit aus offenen Ausleihen ab. Gezählt werden nur Exemplare mit Status `active`;
 * eine offene Ausleihe eines beschädigten oder verlorenen Exemplars mindert die Verfügbarkeit nicht.
 */
final class CopyAvailabilityService
{
    /**
     * @param  list<string>  $titleIds
     * @return array<string, CopyAvailability> je Titel-ID
     */
    public function forTitles(array $titleIds): array
    {
        $result = $this->summarize('catalog_editions.title_id', $titleIds, true);

        if ($titleIds !== []) {
            $waiting = DB::table('circulation_reservations')
                ->whereIn('title_id', $titleIds)
                ->where('status', ReservationStatus::Waiting->value)
                ->groupBy('title_id')
                ->selectRaw('title_id, count(*) as waiting')
                ->pluck('waiting', 'title_id');

            foreach ($waiting as $titleId => $count) {
                $current = $result[(string) $titleId] ?? CopyAvailability::none();
                $result[(string) $titleId] = new CopyAvailability(
                    $current->activeCopies,
                    $current->loanedCopies,
                    $current->earliestDueOn,
                    $current->heldCopies,
                    (int) $count,
                );
            }
        }

        return $result;
    }

    /**
     * @param  list<string>  $editionIds
     * @return array<string, CopyAvailability> je Ausgaben-ID
     */
    public function forEditions(array $editionIds): array
    {
        return $this->summarize('catalog_copies.edition_id', $editionIds, false);
    }

    /**
     * @param  list<string>  $ids
     * @return array<string, CopyAvailability>
     */
    private function summarize(string $groupColumn, array $ids, bool $joinEditions): array
    {
        $result = [];

        foreach ($ids as $id) {
            $result[$id] = CopyAvailability::none();
        }

        if ($ids === []) {
            return $result;
        }

        $query = DB::table('catalog_copies')
            ->leftJoin('circulation_loans', static function ($join): void {
                $join->on('circulation_loans.copy_id', '=', 'catalog_copies.id')
                    ->whereNull('circulation_loans.returned_at');
            })
            ->leftJoin('circulation_reservations', static function ($join): void {
                $join->on('circulation_reservations.ready_copy_id', '=', 'catalog_copies.id')
                    ->where('circulation_reservations.status', ReservationStatus::Ready->value);
            })
            ->where('catalog_copies.status', CopyStatus::Active->value)
            ->whereIn($groupColumn, $ids)
            ->groupBy($groupColumn)
            ->selectRaw($groupColumn.' as group_id')
            ->selectRaw('count(distinct catalog_copies.id) as active_copies')
            ->selectRaw('count(distinct circulation_loans.copy_id) as loaned_copies')
            ->selectRaw('count(distinct circulation_reservations.ready_copy_id) as held_copies')
            ->selectRaw('min(circulation_loans.due_on) as earliest_due_on');

        if ($joinEditions) {
            $query->join('catalog_editions', 'catalog_editions.id', '=', 'catalog_copies.edition_id');
        }

        foreach ($query->get() as $row) {
            $result[(string) $row->group_id] = new CopyAvailability(
                (int) $row->active_copies,
                (int) $row->loaned_copies,
                $row->earliest_due_on !== null ? CarbonImmutable::parse((string) $row->earliest_due_on) : null,
                (int) $row->held_copies,
            );
        }

        return $result;
    }
}
