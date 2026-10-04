<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Identity\Actions\DisablePatronOnlineAccountAction;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\Patrons\Events\PatronDeparted;
use App\Modules\Patrons\Exceptions\PatronStatusStateConflict;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronAccountLinkToken;
use App\Modules\Patrons\Models\PatronStatusEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class DepartPatronAction
{
    public function __construct(
        private BusinessClock $clock,
        private DisablePatronOnlineAccountAction $disableOnlineAccount,
    ) {}

    public function execute(Patron $patron, string $effectiveOn, User $actor): Patron
    {
        $effectiveDate = CarbonImmutable::parse($effectiveOn, $this->clock->timezone())->startOfDay();
        $now = $this->clock->now();

        if ($effectiveDate->greaterThan($now->startOfDay())) {
            throw PatronStatusStateConflict::futureEffectiveDate();
        }

        $departedPatron = DB::transaction(function () use ($patron, $effectiveDate, $now, $actor): Patron {
            $lockedPatron = Patron::query()
                ->whereKey($patron->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPatron->status !== PatronStatus::Active) {
                throw PatronStatusStateConflict::cannotDepart();
            }

            $fromStatus = $lockedPatron->status;

            $lockedPatron->forceFill([
                'status' => PatronStatus::Departed,
                'leaving_on' => $effectiveDate->toDateString(),
                'school_class_id' => null,
            ])->save();

            PatronAccountLinkToken::query()
                ->where('patron_id', $lockedPatron->getKey())
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => $now]);

            $this->disableOnlineAccount->execute((string) $lockedPatron->getKey());

            PatronStatusEvent::query()->create([
                'patron_id' => $lockedPatron->getKey(),
                'from_status' => $fromStatus->value,
                'to_status' => PatronStatus::Departed->value,
                'effective_on' => $effectiveDate->toDateString(),
                'actor_user_id' => $actor->getKey(),
                'created_at' => $now,
            ]);

            return $lockedPatron->load('schoolClass');
        });

        event(new PatronDeparted(
            patronId: (string) $departedPatron->getKey(),
            effectiveOn: $effectiveDate->toDateString(),
        ));

        return $departedPatron;
    }
}
