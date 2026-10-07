<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Foundation\Auth\RoleRegistry;
use App\Models\User;
use App\Modules\Identity\Exceptions\UserAccountStateConflict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Legt ein Konto für Mitarbeitende an und lädt per E-Mail ein: Die Person setzt ihr Passwort über den Link selbst.
 * Bis dahin hat das Konto ein zufälliges, unbekanntes Passwort.
 */
final readonly class CreateUserAccountAction
{
    public function __construct(private RoleRegistry $roles, private AssignRoleAction $assign) {}

    /** @param list<string> $roleKeys */
    public function execute(string $name, string $email, array $roleKeys, User $actor): User
    {
        $email = mb_strtolower(trim($email));

        foreach ($roleKeys as $key) {
            if (! array_key_exists($key, $this->roles->all())) {
                throw new InvalidArgumentException("Unbekannte Rolle [{$key}].");
            }
        }

        if (User::query()->whereRaw('lower(email) = ?', [$email])->exists()) {
            throw UserAccountStateConflict::emailTaken();
        }

        $user = DB::transaction(function () use ($name, $email, $roleKeys, $actor): User {
            $user = User::query()->create(['name' => trim($name), 'email' => $email, 'password' => Str::random(48)]);

            foreach (array_unique($roleKeys) as $key) {
                $this->assign->execute($user, $key, $actor);
            }

            return $user;
        });

        Password::sendResetLink(['email' => $user->email, 'disabled_at' => null]);

        return $user;
    }
}
