<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $status open|closed
 * @property int|null $started_by_user_id
 * @property Carbon $started_at
 * @property Carbon|null $closed_at
 */
final class InventoryCount extends Model
{
    use HasUlids;

    public const OPEN = 'open';

    public const CLOSED = 'closed';

    protected $table = 'catalog_inventory_counts';

    /** @var list<string> */
    protected $fillable = ['name', 'status', 'started_by_user_id', 'started_at', 'closed_at'];

    public function isOpen(): bool
    {
        return $this->status === self::OPEN;
    }

    /** @return HasMany<InventoryCountItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryCountItem::class, 'inventory_count_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'closed_at' => 'datetime'];
    }
}
