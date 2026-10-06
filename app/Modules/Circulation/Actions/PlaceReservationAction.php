<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Circulation\Exceptions\CirculationRuleViolation;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Circulation\Models\Reservation;
use App\Modules\Circulation\Services\CirculationRuleEvaluator;
use App\Modules\Circulation\Services\CopyAvailabilityService;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Support\Facades\DB;

/**
 * Merkt einen Titel für ein Ausleihkonto vor. Vorgemerkt wird nur, wenn kein Exemplar frei ist; sonst wird direkt ausgeliehen.
 * Der Titel wird über den Barcode eines Exemplars oder über die ISBN gefunden.
 */
final readonly class PlaceReservationAction
{
    public function __construct(
        private BusinessClock $clock,
        private CirculationRuleEvaluator $rules,
        private CopyAvailabilityService $availability,
        private CatalogIsbnNormalizer $isbn,
        private AuditRecorder $audit,
    ) {}

    public function execute(Patron $patron, string $identifier, User $actor): Reservation
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            throw new CirculationRuleViolation(['Bitte Exemplar-Barcode oder ISBN angeben.']);
        }

        return $this->place($patron, fn (): Title => $this->resolveTitle($identifier), $actor);
    }

    /** Vormerken über die Titel-ID, z. B. von der Titelseite oder aus dem Portal. */
    public function executeForTitle(Patron $patron, string $titleId, User $actor): Reservation
    {
        return $this->place($patron, static function () use ($titleId): Title {
            $title = Title::query()->find($titleId);

            return $title ?? throw new CirculationRuleViolation(['Der Titel wurde nicht gefunden.']);
        }, $actor);
    }

    /** @param  \Closure(): Title  $resolveTitle */
    private function place(Patron $patron, \Closure $resolveTitle, User $actor): Reservation
    {
        return DB::transaction(function () use ($patron, $resolveTitle, $actor): Reservation {
            $lockedPatron = Patron::query()->whereKey($patron->getKey())->lockForUpdate()->firstOrFail();
            $title = $resolveTitle();
            $titleId = (string) $title->getKey();

            $editions = Edition::query()->where('title_id', $titleId)->get();

            // Exemplare des Titels sperren, damit eine parallele Rückgabe die Prüfung „ist etwas frei?“ nicht unterläuft.
            Copy::query()
                ->whereIn('edition_id', $editions->modelKeys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $violations = $this->violations($lockedPatron, $titleId, $editions->all());

            if ($violations !== []) {
                throw new CirculationRuleViolation($violations);
            }

            $reservation = Reservation::query()->create([
                'patron_id' => $lockedPatron->getKey(),
                'title_id' => $titleId,
                'status' => ReservationStatus::Waiting,
                'requested_at' => $this->clock->now(),
                'created_by_user_id' => $actor->getKey(),
            ]);

            $this->audit->record(
                'circulation.reservation.placed',
                'Titel vorgemerkt.',
                $reservation,
                ['patron_id' => (string) $lockedPatron->getKey(), 'title_id' => $titleId],
                (int) $actor->getKey(),
            );

            return $reservation->load('title');
        });
    }

    /**
     * @param  list<Edition>  $editions
     * @return list<string>
     */
    private function violations(Patron $patron, string $titleId, array $editions): array
    {
        $violations = [];

        if (! $patron->isActive()) {
            $violations[] = 'Das Ausleihkonto ist nicht aktiv.';
        }

        if ($patron->blocked_at !== null) {
            $violations[] = 'Das Ausleihkonto ist für Ausleihen gesperrt.';
        }

        $availability = $this->availability->forTitles([$titleId])[$titleId];

        if (! $availability->hasActiveCopies()) {
            $violations[] = 'Für diesen Titel gibt es kein ausleihbares Exemplar.';
        } elseif ($availability->isAvailable()) {
            $violations[] = 'Ein Exemplar dieses Titels ist verfügbar und kann direkt ausgeliehen werden.';
        }

        if (Reservation::query()
            ->where('patron_id', $patron->getKey())
            ->where('title_id', $titleId)
            ->whereIn('status', ReservationStatus::openValues())
            ->exists()) {
            $violations[] = 'Dieser Titel ist für das Ausleihkonto bereits vorgemerkt.';
        }

        $editionIds = array_map(static fn (Edition $edition): string => (string) $edition->getKey(), $editions);

        if (Loan::query()
            ->where('patron_id', $patron->getKey())
            ->whereNull('returned_at')
            ->whereIn('copy_id', Copy::query()->whereIn('edition_id', $editionIds)->select('id'))
            ->exists()) {
            $violations[] = 'Dieser Titel ist auf dem Ausleihkonto bereits ausgeliehen.';
        }

        $maximum = max(1, (int) config('circulation.max_open_reservations', 5));

        if (Reservation::query()
            ->where('patron_id', $patron->getKey())
            ->whereIn('status', ReservationStatus::openValues())
            ->count() >= $maximum) {
            $violations[] = "Es sind höchstens {$maximum} offene Vormerkungen je Ausleihkonto möglich.";
        }

        $activeEditions = array_filter(
            $editions,
            static fn (Edition $edition): bool => $edition->copies()->where('status', CopyStatus::Active->value)->exists(),
        );

        if ($activeEditions !== []) {
            $ageViolations = array_map(
                fn (Edition $edition): ?string => $this->rules->minimumAgeViolation($patron, $edition),
                $activeEditions,
            );

            // Eine Vormerkung ist nur sinnvoll, wenn mindestens eine Ausgabe altersgerecht ist.
            if (! in_array(null, $ageViolations, true)) {
                $violations[] = (string) reset($ageViolations);
            }
        }

        return $violations;
    }

    private function resolveTitle(string $identifier): Title
    {
        $copy = Copy::query()->where('barcode', $identifier)->first();

        if ($copy instanceof Copy) {
            return Title::query()->findOrFail(Edition::query()->whereKey($copy->edition_id)->value('title_id'));
        }

        $normalized = $this->isbn->normalize($identifier);
        $edition = $this->isbn->isStandardFormat($normalized)
            ? Edition::query()->where('isbn', $normalized)->first()
            : null;

        if (! $edition instanceof Edition) {
            throw new CirculationRuleViolation(["Kein Titel zu [{$identifier}] gefunden. Bitte Exemplar-Barcode oder ISBN prüfen."]);
        }

        return Title::query()->findOrFail($edition->title_id);
    }
}
