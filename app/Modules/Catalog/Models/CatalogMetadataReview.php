<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\MetadataReviewStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $edition_id
 * @property MetadataReviewStatus $status
 * @property list<string> $issues
 * @property int $severity
 * @property string $fingerprint
 * @property array<string, mixed>|null $proposal
 * @property string|null $proposal_source
 * @property string|null $proposal_state
 * @property Carbon|null $proposal_fetched_at
 * @property int|null $decided_by_user_id
 * @property Carbon|null $decided_at
 * @property list<array<string, mixed>>|null $history
 */
final class CatalogMetadataReview extends Model
{
    use HasUlids;

    protected $table = 'catalog_metadata_reviews';

    /** @var list<string> */
    protected $fillable = [
        'edition_id',
        'status',
        'issues',
        'severity',
        'fingerprint',
        'proposal',
        'proposal_source',
        'proposal_state',
        'proposal_fetched_at',
        'decided_by_user_id',
        'decided_at',
        'history',
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
            'status' => MetadataReviewStatus::class,
            'issues' => 'array',
            'severity' => 'integer',
            'proposal' => 'array',
            'proposal_fetched_at' => 'datetime',
            'decided_at' => 'datetime',
            'history' => 'array',
        ];
    }
}
