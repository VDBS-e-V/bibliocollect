<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $patron_id
 * @property string $from_status
 * @property string $to_status
 * @property Carbon|null $effective_on
 * @property int|null $actor_user_id
 * @property Carbon|null $created_at
 */
final class PatronStatusEvent extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'patron_id',
        'from_status',
        'to_status',
        'effective_on',
        'actor_user_id',
        'created_at',
    ];

    /** @return BelongsTo<Patron, $this> */
    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'effective_on' => 'date',
            'created_at' => 'datetime',
        ];
    }
}
