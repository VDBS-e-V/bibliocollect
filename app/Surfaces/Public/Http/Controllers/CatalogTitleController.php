<?php

declare(strict_types=1);

namespace App\Surfaces\Public\Http\Controllers;

use App\Models\User;
use App\Modules\Catalog\DTOs\HoldingSummary;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogClassificationService;
use App\Modules\Catalog\Services\CatalogCoverService;
use App\Modules\Catalog\Services\CatalogHoldingService;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Models\Bookmark;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use App\Surfaces\Public\Support\PublicCatalogPresenter;
use Illuminate\Http\Response;

final class CatalogTitleController
{
    public function __invoke(
        string $titleId,
        CatalogHoldingService $holdings,
        CatalogClassificationService $classification,
        CatalogCoverService $covers,
        CopyAvailabilityService $availability,
        PublicCatalogPresenter $presenter,
    ): Response {
        $title = Title::query()
            ->with(['contributions.contributor', 'editions.copies.shelf.topics', 'editions.series'])
            ->findOrFail($titleId);

        // Öffentlich zählen nur vorhandene Exemplare (nicht ausgesondert, nicht verloren); Ausgaben ohne solche Exemplare entfallen.
        $present = [CopyStatus::Active, CopyStatus::Damaged];

        foreach ($title->editions as $edition) {
            $edition->setRelation('copies', $edition->copies->filter(static fn (Copy $copy): bool => in_array($copy->status, $present, true))->values());
        }

        $title->setRelation('editions', $title->editions->filter(static fn (Edition $edition): bool => $edition->copies->isNotEmpty())->values());

        abort_if($title->editions->isEmpty(), 404);

        /** @var array<string, HoldingSummary> $editionSummaries */
        $editionSummaries = [];
        /** @var array<string, list<string>> $editionTopics */
        $editionTopics = [];

        /** @var Edition $edition */
        foreach ($title->editions as $edition) {
            $editionSummaries[(string) $edition->getKey()] = $holdings->summarizeEdition($edition);
            $editionTopics[(string) $edition->getKey()] = $classification->topicNamesForEdition($edition);
        }

        $editionAvailabilities = $availability->forEditions(array_keys($editionSummaries));
        $titleAvailability = $availability->forTitles([(string) $title->getKey()])[(string) $title->getKey()];

        $copyIds = [];

        foreach ($title->editions as $edition) {
            foreach ($edition->copies as $copy) {
                $copyIds[] = (string) $copy->getKey();
            }
        }

        $copyStates = $availability->forCopies($copyIds);

        // Vormerken geht immer (bei freiem Exemplar als „Zurücklegen lassen“). Die Seite sagt, warum der Knopf fehlt.
        $reserveState = null;

        if ($titleAvailability->hasActiveCopies()) {
            $user = auth()->user();

            if (! $user instanceof User) {
                $reserveState = 'login';
            } elseif ($user->patron_id !== null) {
                $maximum = max(0, (int) config('circulation.max_open_reservations', 5));
                $own = Reservation::query()->where('patron_id', $user->patron_id)->whereIn('status', ReservationStatus::openValues());
                $reservedHere = (clone $own)->where('title_id', (string) $title->getKey())->exists();
                $queue = $titleAvailability->waitingReservations + $titleAvailability->heldCopies;

                $reserveState = match (true) {
                    $reservedHere => 'reserved',
                    $maximum === 0 => 'off',
                    (clone $own)->count() >= $maximum => 'limit',
                    ! $titleAvailability->isAvailable() && $queue >= $titleAvailability->activeCopies * max(1, (int) config('circulation.max_reservations_per_copy', 1)) => 'full',
                    default => 'ready',
                };
            }
        }

        $user = auth()->user();

        return response()->view('pages.surfaces.public.catalog.show', [
            'bookmarked' => $user instanceof User ? Bookmark::markedBy((int) $user->getKey(), [(string) $title->getKey()]) !== [] : false,
            'title' => $title,
            'titleSummary' => $holdings->summarizeTitle($title),
            'editionSummaries' => $editionSummaries,
            'copyStates' => $copyStates,
            'reserveState' => $reserveState,
            'editionAvailabilities' => $editionAvailabilities,
            'titleAvailability' => $titleAvailability,
            'editionTopics' => $editionTopics,
            'coverUrl' => $covers->localUrlForTitle($title) ?? asset('brand/vdbs/catalog-cover-placeholder.svg'),
            'presenter' => $presenter,
        ]);
    }
}
