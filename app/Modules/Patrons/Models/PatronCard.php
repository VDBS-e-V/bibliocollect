<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Models;

use App\Modules\Patrons\Enums\CardBlockReason;
use App\Modules\Patrons\Enums\CardStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Nicht personalisierter Bibliotheksausweis. Die Nummer ist zufällig, nie fortlaufend und für immer einmalig.
 *
 * @property string $id
 * @property string $number
 * @property int|null $batch
 * @property CardStatus $status
 * @property string|null $patron_id
 * @property Carbon|null $printed_at
 * @property Carbon|null $assigned_at
 * @property Carbon|null $blocked_at
 * @property CardBlockReason|null $block_reason
 * @property Carbon|null $created_at
 */
final class PatronCard extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['number', 'batch', 'status', 'patron_id', 'printed_at', 'assigned_at', 'blocked_at', 'block_reason'];

    /** @return BelongsTo<Patron, $this> */
    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CardStatus::class,
            'block_reason' => CardBlockReason::class,
            'batch' => 'integer',
            'printed_at' => 'datetime',
            'assigned_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }
}
