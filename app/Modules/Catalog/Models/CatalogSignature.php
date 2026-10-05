<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $legacy_source
 * @property string|null $legacy_id
 * @property string $signature
 */
final class CatalogSignature extends Model
{
    use HasUlids;

    protected $table = 'catalog_signatures';

    /** @var list<string> */
    protected $fillable = [
        'legacy_source',
        'legacy_id',
        'signature',
    ];

    /** @return BelongsToMany<CatalogTopic, $this> */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogTopic::class,
            'catalog_signature_topics',
            'signature_id',
            'topic_id',
        )->withPivot('position')->orderByPivot('position');
    }

    /** @return HasMany<Copy, $this> */
    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class, 'signature_id');
    }
}
