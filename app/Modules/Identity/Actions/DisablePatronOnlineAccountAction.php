<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;

final readonly class DisablePatronOnlineAccountAction
{
    public function __construct(private BusinessClock $clock) {}

    public function execute(string $patronId, string $reason = 'patron_departed'): ?User
    {
        $user = User::query()
            ->where('patron_id', $patronId)
            ->lockForUpdate()
            ->first();

        if ($user === null) {
            return null;
        }

        if ($user->disabled_at === null) {
            $user->forceFill([
                'disabled_at' => $this->clock->now(),
                'disabled_reason' => $reason,
            ])->save();
        }

        return $user;
    }
}
