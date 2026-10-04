<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $preferred_title
 * @property string|null $subtitle
 * @property string|null $sort_title
 */
final class Title extends Model
{
    use HasUlids;

    protected $table = 'catalog_titles';

    /** @var list<string> */
    protected $fillable = [
        'preferred_title',
        'subtitle',
        'sort_title',
    ];

    /** @return HasMany<Edition, $this> */
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class, 'title_id');
    }
}
