<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Collection;
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

    /** Lesbarer Pfadteil für Links auf die Themenseite: „Rätsel & Knobeln“ wird zu „Rätsel-Knobeln“. */
    public function publicSlug(): string
    {
        return rawurlencode(trim((string) preg_replace('/[^\p{L}\p{N}]+/u', '-', $this->name), '-'));
    }

    /** Vergleichsform eines Namens oder Pfadteils: nur Buchstaben und Ziffern, klein. */
    public static function normalizeKey(string $value): string
    {
        return mb_strtolower((string) preg_replace('/[^\p{L}\p{N}]/u', '', rawurldecode($value)));
    }

    /**
     * Dieses Thema und alle Unterthemen.
     *
     * @return Collection<int, CatalogTopic>
     */
    public function family(): Collection
    {
        $all = new Collection([$this]);
        $level = [$this->getKey()];

        for ($depth = 0; $depth < 6 && $level !== []; $depth++) {
            $children = self::query()->whereIn('parent_id', $level)->get();
            $all = $all->concat($children);
            $level = $children->pluck('id')->all();
        }

        return $all->unique('id')->values();
    }

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

    /** @return BelongsToMany<CatalogShelf, $this> */
    public function shelves(): BelongsToMany
    {
        return $this->belongsToMany(CatalogShelf::class, 'catalog_shelf_topics', 'topic_id', 'shelf_id')->withPivot('position');
    }

    /**
     * Nur noch für den Import aus dem Altsystem.
     *
     * @return BelongsToMany<CatalogSignature, $this>
     */
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
