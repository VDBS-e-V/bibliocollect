<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $title_id
 * @property string|null $edition_statement
 * @property string|null $isbn
 * @property string|null $publisher_name
 * @property int|null $publication_year
 * @property int|null $minimum_age
 * @property string|null $age_rating_label
 */
final class Edition extends Model
{
    use HasUlids;

    protected $table = 'catalog_editions';

    /** @var list<string> */
    protected $fillable = [
        'title_id',
        'edition_statement',
        'isbn',
        'publisher_name',
        'publication_year',
        'minimum_age',
        'age_rating_label',
    ];

    /** @return BelongsTo<Title, $this> */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id');
    }

    /** @return HasMany<Copy, $this> */
    public function copies(): HasMany
    {
        return $this->hasMany(Copy::class, 'edition_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'minimum_age' => 'integer',
        ];
    }
}
