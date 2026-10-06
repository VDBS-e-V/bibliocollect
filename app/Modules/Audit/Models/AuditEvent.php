<?php

declare(strict_types=1);

namespace App\Modules\Audit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Unveränderliches Auditereignis. Der Kontext enthält nur Kennungen und Fachwerte, keine Klartextdaten von Personen.
 *
 * @property string $id
 * @property Carbon $occurred_at
 * @property int|null $actor_user_id
 * @property string $action
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property string $summary
 * @property array<string, mixed>|null $context
 */
final class AuditEvent extends Model
{
    use HasUlids;

    protected $table = 'audit_events';

    /** @var list<string> */
    protected $fillable = [
        'occurred_at',
        'actor_user_id',
        'action',
        'subject_type',
        'subject_id',
        'summary',
        'context',
    ];

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'context' => 'array',
        ];
    }
}
