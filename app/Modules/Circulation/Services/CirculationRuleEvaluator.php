<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Foundation\Support\BusinessClock;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Circulation\Models\Loan;
use App\Modules\Patrons\Models\Patron;
use Carbon\CarbonImmutable;
use DateTimeInterface;

final readonly class CirculationRuleEvaluator
{
    public function __construct(private BusinessClock $clock) {}

    /**
     * @return list<string>
     */
    public function checkoutViolations(
        Patron $patron,
        Copy $copy,
        Edition $edition,
        bool $copyAlreadyLoaned,
        bool $copyHeldForOtherPatron = false,
    ): array {
        $violations = [];

        if (! $patron->isActive()) {
            $violations[] = 'Das Ausleihkonto ist nicht aktiv.';
        }

        if ($patron->blocked_at !== null) {
            $violations[] = 'Das Ausleihkonto ist für Ausleihen gesperrt.';
        }

        if ($copy->status !== CopyStatus::Active) {
            $violations[] = 'Der aktuelle Exemplarstatus erlaubt keine Ausleihe.';
        }

        if ($copyAlreadyLoaned) {
            $violations[] = 'Dieses Exemplar ist bereits ausgeliehen.';
        }

        $ageViolation = $this->minimumAgeViolation($patron, $edition);

        if ($ageViolation !== null) {
            $violations[] = $ageViolation;
        }

        if ($copyHeldForOtherPatron) {
            $violations[] = 'Dieses Exemplar liegt für eine andere Person zur Abholung bereit.';
        }

        return $violations;
    }

    /** Hinweis, wenn das Mindestalter der Ausgabe nicht erreicht ist oder nicht geprüft werden kann. */
    public function minimumAgeViolation(Patron $patron, Edition $edition): ?string
    {
        $minimumAge = $edition->minimum_age;

        if ($minimumAge === null) {
            return null;
        }

        $birthDate = $patron->getAttribute('birth_date');

        if (! $birthDate instanceof DateTimeInterface) {
            return 'Für die Altersprüfung fehlt ein gültiges Geburtsdatum.';
        }

        $eligibleFrom = CarbonImmutable::instance($birthDate)
            ->setTimezone($this->clock->timezone())
            ->startOfDay()
            ->addYears($minimumAge);

        if ($this->clock->now()->startOfDay()->lessThan($eligibleFrom)) {
            return "Das Mindestalter von {$minimumAge} Jahren ist noch nicht erreicht.";
        }

        return null;
    }

    /**
     * Gründe, aus denen eine offene Ausleihe nicht verlängert werden darf. Leer heißt: erlaubt.
     *
     * @return list<string>
     */
    public function renewalViolations(
        Loan $loan,
        Patron $patron,
        Copy $copy,
        bool $titleHasPendingReservation = false,
    ): array {
        if ($loan->returned_at !== null) {
            return ['Diese Ausleihe wurde bereits zurückgegeben.'];
        }

        $violations = [];

        if (! $patron->isActive()) {
            $violations[] = 'Das Ausleihkonto ist nicht aktiv.';
        }

        if ($patron->blocked_at !== null) {
            $violations[] = 'Das Ausleihkonto ist für Ausleihen gesperrt.';
        }

        if ($copy->status !== CopyStatus::Active) {
            $violations[] = 'Der aktuelle Exemplarstatus erlaubt keine Verlängerung.';
        }

        $maximum = max(0, (int) config('circulation.max_renewals', 2));

        if ($loan->renewal_count >= $maximum) {
            $violations[] = $maximum === 0
                ? 'Verlängerungen sind nicht vorgesehen.'
                : "Die Ausleihe wurde bereits {$loan->renewal_count}-mal verlängert (höchstens {$maximum}).";
        }

        $overdue = CarbonImmutable::parse($loan->due_on->toDateString(), $this->clock->timezone())
            ->lessThan($this->clock->now()->startOfDay());

        if ($overdue && ! (bool) config('circulation.allow_overdue_renewal', false)) {
            $violations[] = 'Die Ausleihe ist überfällig und kann nicht verlängert werden.';
        }

        if ($titleHasPendingReservation) {
            $violations[] = 'Für diesen Titel liegt eine Vormerkung vor.';
        }

        return $violations;
    }
}
