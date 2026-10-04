<?php

declare(strict_types=1);

namespace App\Models;

use App\Foundation\Auth\RoleRegistry;
use App\Foundation\Contracts\AuthorizesPermissions;
use App\Modules\Identity\Models\UserRoleAssignment;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $public_id
 * @property string|null $patron_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $disabled_at
 * @property string|null $disabled_reason
 */
#[Fillable(['name', 'email', 'password', 'patron_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements AuthorizesPermissions, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if ($user->getAttribute('public_id') === null) {
                $user->setAttribute('public_id', (string) Str::ulid());
            }
        });
    }

    /** @return HasMany<UserRoleAssignment, $this> */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRoleAssignment::class);
    }

    /** @return list<string> */
    public function roleKeys(): array
    {
        return $this->roleAssignments()
            ->pluck('role_key')
            ->map(static fn (mixed $role): string => (string) $role)
            ->filter(static fn (string $role): bool => $role !== '')
            ->values()
            ->all();
    }

    public function isEnabled(): bool
    {
        return $this->disabled_at === null;
    }

    public function allowsPermission(string $permission): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $permissions = app(RoleRegistry::class)->permissionsFor($this->roleKeys());

        return in_array($permission, $permissions, true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'disabled_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
