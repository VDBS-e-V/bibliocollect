<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Patrons\Exceptions\PatronBlockStateConflict;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronBlockEvent;
use Illuminate\Support\Facades\DB;

final readonly class UnblockPatronAction
{
    public function __construct(private BusinessClock $clock) {}

    public function execute(Patron $patron, User $actor): Patron
    {
        return DB::transaction(function () use ($patron, $actor): Patron {
            $lockedPatron = Patron::query()
                ->whereKey($patron->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPatron->blocked_at === null) {
                throw PatronBlockStateConflict::notBlocked();
            }

            $now = $this->clock->now();
            $previousReason = $lockedPatron->blocked_reason;

            $lockedPatron->forceFill([
                'blocked_at' => null,
                'blocked_reason' => null,
            ])->save();

            PatronBlockEvent::query()->create([
                'patron_id' => $lockedPatron->getKey(),
                'action' => 'unblocked',
                'reason' => $previousReason,
                'actor_user_id' => $actor->getKey(),
                'created_at' => $now,
            ]);

            return $lockedPatron;
        });
    }
}
