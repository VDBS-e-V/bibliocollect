<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Patrons\Exceptions\PatronBlockStateConflict;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronBlockEvent;
use Illuminate\Support\Facades\DB;

final readonly class BlockPatronAction
{
    public function __construct(private BusinessClock $clock) {}

    public function execute(Patron $patron, string $reason, User $actor): Patron
    {
        return DB::transaction(function () use ($patron, $reason, $actor): Patron {
            $lockedPatron = Patron::query()
                ->whereKey($patron->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedPatron->isActive()) {
                throw PatronBlockStateConflict::unavailableStatus();
            }

            if ($lockedPatron->blocked_at !== null) {
                throw PatronBlockStateConflict::alreadyBlocked();
            }

            $now = $this->clock->now();
            $normalizedReason = trim($reason);

            $lockedPatron->forceFill([
                'blocked_at' => $now,
                'blocked_reason' => $normalizedReason,
            ])->save();

            PatronBlockEvent::query()->create([
                'patron_id' => $lockedPatron->getKey(),
                'action' => 'blocked',
                'reason' => $normalizedReason,
                'actor_user_id' => $actor->getKey(),
                'created_at' => $now,
            ]);

            return $lockedPatron;
        });
    }
}
