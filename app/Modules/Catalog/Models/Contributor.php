<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Support\Transliteration;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $display_name
 * @property string|null $sort_name
 * @property string|null $gnd_id
 * @property string|null $search_aliases
 */
final class Contributor extends Model
{
    use HasUlids;

    protected $table = 'catalog_contributors';

    /** @var list<string> */
    protected $fillable = [
        'display_name',
        'sort_name',
        'gnd_id',
    ];

    protected static function booted(): void
    {
        self::saving(static function (self $contributor): void {
            $contributor->setAttribute('search_aliases', Transliteration::aliases($contributor->display_name, $contributor->sort_name));
        });
    }

    /** @return HasMany<TitleContribution, $this> */
    public function contributions(): HasMany
    {
        return $this->hasMany(TitleContribution::class, 'contributor_id');
    }
}
