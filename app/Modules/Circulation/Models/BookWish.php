<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Models;

use App\Modules\Circulation\Enums\WishStatus;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Buchwunsch einer Person oder vom Tresen erfasst.
 *
 * @property string $id
 * @property string|null $patron_id
 * @property string $title
 * @property string|null $author
 * @property string|null $isbn
 * @property string|null $note
 * @property WishStatus $status
 * @property string|null $answer
 * @property int|null $decided_by_user_id
 * @property Carbon|null $decided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class BookWish extends Model
{
    use HasUlids;

    protected $table = 'circulation_book_wishes';

    /** @var list<string> */
    protected $fillable = ['patron_id', 'title', 'author', 'isbn', 'note', 'status', 'answer', 'decided_by_user_id', 'decided_at'];

    /** @return BelongsTo<Patron, $this> */
    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['status' => WishStatus::class, 'decided_at' => 'datetime'];
    }
}
