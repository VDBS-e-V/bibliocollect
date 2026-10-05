<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Models\User;
use App\Modules\Catalog\Enums\CatalogImportStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property int|null $created_by_user_id
 * @property string $source_format
 * @property string|null $original_filename
 * @property CatalogImportStatus $status
 * @property array<int, string> $headers
 * @property array<string, string|null>|null $mapping
 * @property array<string, int>|null $summary
 * @property Carbon|null $committed_at
 */
final class CatalogImportBatch extends Model
{
    use HasUlids;

    protected $table = 'catalog_import_batches';

    /** @var list<string> */
    protected $fillable = [
        'created_by_user_id',
        'source_format',
        'original_filename',
        'status',
        'headers',
        'mapping',
        'summary',
        'committed_at',
    ];

    /** @return HasMany<CatalogImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(CatalogImportRow::class, 'batch_id')->orderBy('row_number');
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
            'status' => CatalogImportStatus::class,
            'headers' => 'array',
            'mapping' => 'array',
            'summary' => 'array',
            'committed_at' => 'datetime',
        ];
    }
}
