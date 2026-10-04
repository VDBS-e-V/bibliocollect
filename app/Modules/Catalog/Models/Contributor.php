<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $display_name
 * @property string|null $sort_name
 */
final class Contributor extends Model
{
    use HasUlids;

    protected $table = 'catalog_contributors';

    /** @var list<string> */
    protected $fillable = [
        'display_name',
        'sort_name',
    ];

    /** @return HasMany<TitleContribution, $this> */
    public function contributions(): HasMany
    {
        return $this->hasMany(TitleContribution::class, 'contributor_id');
    }
}
