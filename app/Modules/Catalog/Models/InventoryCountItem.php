<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $inventory_count_id
 * @property string|null $copy_id
 * @property string $barcode
 * @property string $shelf_code
 * @property Carbon $scanned_at
 */
final class InventoryCountItem extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $table = 'catalog_inventory_items';

    /** @var list<string> */
    protected $fillable = ['inventory_count_id', 'copy_id', 'barcode', 'shelf_code', 'scanned_at'];

    /** @return BelongsTo<Copy, $this> */
    public function copy(): BelongsTo
    {
        return $this->belongsTo(Copy::class, 'copy_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['scanned_at' => 'datetime'];
    }
}
