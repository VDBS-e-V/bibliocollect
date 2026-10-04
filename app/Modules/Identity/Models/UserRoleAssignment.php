<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property string $role_key
 * @property int|null $assigned_by_user_id
 */
final class UserRoleAssignment extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'role_key',
        'assigned_by_user_id',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
