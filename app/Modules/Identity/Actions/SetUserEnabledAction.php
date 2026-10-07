<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Identity\Services\UserAccountGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

/** Deaktiviert oder aktiviert ein Konto. Beim Deaktivieren werden laufende Anmeldungen sofort beendet. */
final readonly class SetUserEnabledAction
{
    public function __construct(private UserAccountGuard $guard, private BusinessClock $clock) {}

    public function disable(User $user, ?string $reason, User $actor): void
    {
        $this->guard->assertCanDisable($user, $actor);

        DB::transaction(function () use ($user, $reason): void {
            $user->forceFill([
                'disabled_at' => $this->clock->now(),
                'disabled_reason' => $reason !== null && trim($reason) !== '' ? mb_substr(trim($reason), 0, 200) : 'manual',
                'remember_token' => null,
            ])->save();

            DB::table('sessions')->where('user_id', $user->getKey())->delete();
        });
    }

    public function enable(User $user, User $actor): void
    {
        $user->forceFill(['disabled_at' => null, 'disabled_reason' => null])->save();
    }

    /** Schickt (erneut) den Link zum Setzen eines Passworts. */
    public function invite(User $user, User $actor): bool
    {
        $sent = Password::sendResetLink(['email' => $user->email, 'disabled_at' => null]) === Password::RESET_LINK_SENT;

        return $sent;
    }
}
