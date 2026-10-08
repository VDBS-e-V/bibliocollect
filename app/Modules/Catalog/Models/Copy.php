<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\CopyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $edition_id
 * @property string $barcode
 * @property CopyStatus $status
 * @property string|null $shelf_location
 * @property Carbon|null $shelved_at
 * @property string|null $signature_id
 * @property string|null $legacy_source
 * @property string|null $legacy_media_id
 * @property bool|null $legacy_in_transition
 * @property string|null $legacy_school_id
 * @property string|null $access_status
 * @property Carbon|null $purchase_date
 * @property string|null $purchase_price
 * @property bool|null $legacy_is_available
 * @property Carbon|null $cataloged_on
 * @property string|null $legacy_cover_path
 * @property int|null $legacy_loan_count
 * @property Carbon|null $legacy_last_loan_date
 * @property string|null $internal_notes
 * @property string|null $condition_code
 * @property string|null $legacy_condition
 * @property string|null $depreciation_reason
 * @property Carbon|null $depreciated_at
 * @property string|null $further_use
 * @property array<string, mixed>|null $legacy_metadata
 * @property-read CatalogShelf|null $shelf
 * @property-read CatalogSignature|null $signature
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
        'shelved_at',
        'signature_id',
        'legacy_source',
        'legacy_media_id',
        'legacy_in_transition',
        'legacy_school_id',
        'access_status',
        'purchase_date',
        'purchase_price',
        'legacy_is_available',
        'cataloged_on',
        'legacy_cover_path',
        'legacy_loan_count',
        'legacy_last_loan_date',
        'internal_notes',
        'condition_code',
        'legacy_condition',
        'depreciation_reason',
        'depreciated_at',
        'further_use',
        'legacy_metadata',
    ];

    /**
     * Der Stapel „Einsortieren“: Exemplare im Bestand ohne Standort. Ausgesonderte und verlorene gehören nicht dazu.
     *
     * @param  Builder<Copy>  $query
     * @return Builder<Copy>
     */
    public function scopeAwaitingShelving(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [CopyStatus::Active->value, CopyStatus::Damaged->value])
            ->where(static function (Builder $inner): void {
                $inner->whereNull('shelf_location')->orWhere('shelf_location', '');
            });
    }

    /** @return BelongsTo<Edition, $this> */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class, 'edition_id');
    }

    /**
     * Das Regalbrett, auf dem das Exemplar steht (der Standort ist der Code des Regalbretts).
     *
     * @return BelongsTo<CatalogShelf, $this>
     */
    public function shelf(): BelongsTo
    {
        return $this->belongsTo(CatalogShelf::class, 'shelf_location', 'code');
    }

    /**
     * Nur noch aus dem Import des Altsystems.
     *
     * @return BelongsTo<CatalogSignature, $this>
     */
    public function signature(): BelongsTo
    {
        return $this->belongsTo(CatalogSignature::class, 'signature_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CopyStatus::class,
            'shelved_at' => 'datetime',
            'legacy_in_transition' => 'boolean',
            'legacy_is_available' => 'boolean',
            'purchase_date' => 'date',
            'purchase_price' => 'decimal:2',
            'cataloged_on' => 'date',
            'legacy_loan_count' => 'integer',
            'legacy_last_loan_date' => 'date',
            'depreciated_at' => 'date',
            'legacy_metadata' => 'array',
        ];
    }
}
