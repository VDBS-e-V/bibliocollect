<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Models\User;
use App\Modules\Identity\Contracts\PatronLinkGateway;
use App\Modules\Identity\Exceptions\PatronAlreadyLinked;
use Illuminate\Support\Facades\DB;

final readonly class ClaimOnlineAccountAction
{
    public function __construct(
        private PatronLinkGateway $patrons,
        private AssignRoleAction $assignRole,
    ) {}

    public function execute(string $code, string $email, string $password): User
    {
        return DB::transaction(function () use ($code, $email, $password): User {
            $patron = $this->patrons->consume($code);

            if (User::query()->where('patron_id', $patron->id)->exists()) {
                throw new PatronAlreadyLinked;
            }

            $user = User::query()->create([
                'name' => $patron->displayName,
                'email' => mb_strtolower(trim($email)),
                'password' => $password,
                'patron_id' => $patron->id,
            ]);

            $defaultRole = match ($patron->kind) {
                'student' => 'student',
                'teacher' => 'teacher',
                default => null,
            };

            if ($defaultRole !== null) {
                $this->assignRole->execute($user, $defaultRole);
            }

            return $user;
        });
    }
}
