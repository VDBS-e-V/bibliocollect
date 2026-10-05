<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\CatalogImportRowStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $batch_id
 * @property int $row_number
 * @property CatalogImportRowStatus $status
 * @property array<string, string|null> $raw_data
 * @property list<string> $source_errors
 * @property array<string, string|int|null>|null $normalized_data
 * @property array<string, mixed>|null $plan
 * @property list<string> $warnings
 * @property list<string> $conflicts
 */
final class CatalogImportRow extends Model
{
    use HasUlids;

    protected $table = 'catalog_import_rows';

    /** @var list<string> */
    protected $fillable = [
        'batch_id',
        'row_number',
        'status',
        'raw_data',
        'source_errors',
        'normalized_data',
        'plan',
        'warnings',
        'conflicts',
    ];

    /** @return BelongsTo<CatalogImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(CatalogImportBatch::class, 'batch_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'status' => CatalogImportRowStatus::class,
            'raw_data' => 'array',
            'source_errors' => 'array',
            'normalized_data' => 'array',
            'plan' => 'array',
            'warnings' => 'array',
            'conflicts' => 'array',
        ];
    }
}
