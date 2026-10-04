<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\CopyStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $edition_id
 * @property string $barcode
 * @property CopyStatus $status
 * @property string|null $shelf_location
 */
final class Copy extends Model
{
    use HasUlids;

    protected $table = 'catalog_copies';

    /** @var list<string> */
    protected $fillable = [
        'edition_id',
        'barcode',
        'status',
        'shelf_location',
    ];

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CopyStatus::class,
        ];
    }
}
