<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Services;

use App\Foundation\Support\BusinessClock;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
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

        $minimumAge = $edition->minimum_age;

        if ($minimumAge !== null) {
            $birthDate = $patron->getAttribute('birth_date');

            if (! $birthDate instanceof DateTimeInterface) {
                $violations[] = 'Für die Altersprüfung fehlt ein gültiges Geburtsdatum.';
            } else {
                $eligibleFrom = CarbonImmutable::instance($birthDate)
                    ->setTimezone($this->clock->timezone())
                    ->startOfDay()
                    ->addYears($minimumAge);

                if ($this->clock->now()->startOfDay()->lessThan($eligibleFrom)) {
                    $violations[] = "Das Mindestalter von {$minimumAge} Jahren ist noch nicht erreicht.";
                }
            }
        }

        return $violations;
    }
}
