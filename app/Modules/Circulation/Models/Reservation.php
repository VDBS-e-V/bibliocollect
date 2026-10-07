<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Title;
use App\Modules\Circulation\Enums\ReservationStatus;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Titelbezogene Vormerkung. Die Reihenfolge der Warteschlange ergibt sich aus `requested_at`.
 *
 * @property string $id
 * @property string|null $patron_id
 * @property string $title_id
 * @property ReservationStatus $status
 * @property Carbon $requested_at
 * @property string|null $ready_copy_id
 * @property Carbon|null $ready_at
 * @property Carbon|null $pickup_until
 * @property Carbon|null $closed_at
 * @property string|null $loan_id
 * @property int|null $created_by_user_id
 * @property int|null $closed_by_user_id
 */
final class Reservation extends Model
{
    use HasUlids;

    protected $table = 'circulation_reservations';

    /** @var list<string> */
    protected $fillable = [
        'patron_id',
        'title_id',
        'status',
        'requested_at',
        'ready_copy_id',
        'ready_at',
        'pickup_until',
        'closed_at',
        'loan_id',
        'created_by_user_id',
        'closed_by_user_id',
    ];

    /** @return BelongsTo<Patron, $this> */
    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    protected static function booted(): void
    {
        // Der eindeutige Schlüssel gilt nur für offene Vormerkungen mit Ausleihkonto (siehe Migration open_key).
        self::saving(static function (self $reservation): void {
            $reservation->setAttribute(
                'open_key',
                $reservation->status->isOpen() && $reservation->patron_id !== null ? $reservation->patron_id.'|'.$reservation->title_id : null,
            );
        });
    }

    /** @return BelongsTo<Title, $this> */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    /** @return BelongsTo<Copy, $this> */
    public function readyCopy(): BelongsTo
    {
        return $this->belongsTo(Copy::class, 'ready_copy_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'requested_at' => 'datetime',
            'ready_at' => 'datetime',
            'pickup_until' => 'date',
            'closed_at' => 'datetime',
        ];
    }
}
