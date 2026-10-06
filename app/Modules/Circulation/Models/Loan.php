<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $patron_id
 * @property string $copy_id
 * @property Carbon $checked_out_at
 * @property Carbon $due_on
 * @property Carbon|null $returned_at
 * @property int|null $checked_out_by_user_id
 * @property int|null $returned_by_user_id
 * @property int $renewal_count
 * @property string|null $outcome
 * @property Carbon|null $last_renewed_at
 * @property int|null $last_renewed_by_user_id
 */
final class Loan extends Model
{
    use HasUlids;

    protected $table = 'circulation_loans';

    /** @var list<string> */
    protected $fillable = [
        'patron_id',
        'copy_id',
        'checked_out_at',
        'due_on',
        'returned_at',
        'checked_out_by_user_id',
        'returned_by_user_id',
        'renewal_count',
        'outcome',
        'last_renewed_at',
        'last_renewed_by_user_id',
    ];

    /** @return BelongsTo<Patron, $this> */
    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    /** @return BelongsTo<Copy, $this> */
    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class);
    }

    /** @return BelongsTo<User, $this> */
    public function checkedOutBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by_user_id');
    }

    public function isOpen(): bool
    {
        return $this->returned_at === null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'checked_out_at' => 'datetime',
            'due_on' => 'date',
            'returned_at' => 'datetime',
            'renewal_count' => 'integer',
            'last_renewed_at' => 'datetime',
        ];
    }
}
