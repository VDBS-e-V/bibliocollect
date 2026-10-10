<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Models;

use App\Modules\Catalog\Models\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reading_list_id
 * @property string $title_id
 * @property Carbon|null $created_at
 */
final class ReadingListItem extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'circulation_reading_list_items';

    /** @var list<string> */
    protected $fillable = ['reading_list_id', 'title_id'];

    /** @return BelongsTo<Title, $this> */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id');
    }
}
