<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $title_id
 * @property string $contributor_id
 * @property string $role_key
 * @property int $position
 */
final class TitleContribution extends Model
{
    use HasUlids;

    protected $table = 'catalog_title_contributions';

    /** @var list<string> */
    protected $fillable = [
        'title_id',
        'contributor_id',
        'role_key',
        'position',
    ];

    /** @return BelongsTo<Title, $this> */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id');
    }

    /** @return BelongsTo<Contributor, $this> */
    public function contributor(): BelongsTo
    {
        return $this->belongsTo(Contributor::class, 'contributor_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }
}
