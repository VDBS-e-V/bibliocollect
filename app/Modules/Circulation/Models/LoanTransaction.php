<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Models;

use App\Modules\Patrons\Models\Patron;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Am Tresen bestätigter Vorgang mit Beleg. `items` enthält je Position Art (`checkout`, `renew`, `return`), Titel,
 * Barcode und das Ergebnis (neue Fälligkeit bzw. Rückgabe).
 *
 * @property string $id
 * @property string $number
 * @property string|null $patron_id
 * @property int|null $created_by_user_id
 * @property list<array<string, mixed>> $items
 * @property int $checked_out_count
 * @property int $renewed_count
 * @property int $returned_count
 * @property string|null $emailed_to
 * @property Carbon|null $emailed_at
 * @property Carbon|null $created_at
 */
final class LoanTransaction extends Model
{
    use HasUlids;

    protected $table = 'circulation_transactions';

    /** @var list<string> */
    protected $fillable = ['number', 'patron_id', 'created_by_user_id', 'items', 'checked_out_count', 'renewed_count', 'returned_count', 'emailed_to', 'emailed_at'];

    /** @return BelongsTo<Patron, $this> */
    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'emailed_at' => 'datetime',
        ];
    }
}
