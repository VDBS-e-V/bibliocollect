<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Support\Transliteration;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $preferred_title
 * @property string|null $subtitle
 * @property string|null $sort_title
 * @property int|null $featured_position
 * @property string|null $search_aliases
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

    protected static function booted(): void
    {
        // Lateinische Umschrift für die Suche (nur bei nicht lateinischer Schrift oder Sonderbuchstaben, sonst leer).
        self::saving(static function (self $title): void {
            $title->setAttribute('search_aliases', Transliteration::aliases($title->preferred_title, $title->subtitle));
        });
    }

    /** @return HasMany<Edition, $this> */
    public function editions(): HasMany
    {
        return $this->hasMany(Edition::class, 'title_id');
    }

    /** @return HasMany<TitleContribution, $this> */
    public function contributions(): HasMany
    {
        return $this->hasMany(TitleContribution::class, 'title_id')
            ->orderBy('position')
            ->orderBy('id');
    }
}
