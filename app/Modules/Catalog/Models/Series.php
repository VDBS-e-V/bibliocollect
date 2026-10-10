<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eine Buchreihe („Die Schule der magischen Tiere“). Verlags- und Taschenbuchreihen („dtv“) lassen sich ausblenden.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $key
 * @property bool $is_hidden
 */
final class Series extends Model
{
    use HasUlids;

    protected $table = 'catalog_series';

    /** @var list<string> */
    protected $fillable = ['name', 'slug', 'key', 'is_hidden'];

    /** @return HasMany<Edition, $this> */
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class, 'series_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_hidden' => 'boolean'];
    }
}
