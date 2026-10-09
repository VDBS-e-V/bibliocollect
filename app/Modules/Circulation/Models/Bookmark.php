<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Models;

use App\Modules\Catalog\Models\Title;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ein Titel auf der Merkliste eines Onlinekontos.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title_id
 * @property Carbon|null $created_at
 */
final class Bookmark extends Model
{
    public const UPDATED_AT = null;

    public const LIMIT = 100;

    protected $table = 'circulation_bookmarks';

    /** @var list<string> */
    protected $fillable = ['user_id', 'title_id'];

    /** @return BelongsTo<Title, $this> */
    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'title_id');
    }

    /**
     * Welche der Titel hat sich dieses Konto gemerkt?
     *
     * @param  list<string>  $titleIds
     * @return list<string>
     */
    public static function markedBy(int $userId, array $titleIds): array
    {
        if ($titleIds === []) {
            return [];
        }

        return self::query()->where('user_id', $userId)->whereIn('title_id', $titleIds)->pluck('title_id')->map(static fn ($id): string => (string) $id)->all();
    }
}
