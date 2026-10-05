<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string|null $legacy_source
 * @property string|null $legacy_id
 * @property string|null $public_key
 * @property string|null $parent_id
 * @property string $name
 * @property string|null $description
 */
final class CatalogTopic extends Model
{
    use HasUlids;

    protected $table = 'catalog_topics';

    /** @var list<string> */
    protected $fillable = [
        'legacy_source',
        'legacy_id',
        'public_key',
        'parent_id',
        'name',
        'description',
    ];

    /** @return BelongsTo<CatalogTopic, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<CatalogTopic, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    /** @return BelongsToMany<CatalogSignature, $this> */
    public function signatures(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogSignature::class,
            'catalog_signature_topics',
            'topic_id',
            'signature_id',
        )->withPivot('position')->orderByPivot('position');
    }
}
