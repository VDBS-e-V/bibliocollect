<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Models;

use App\Modules\School\Models\SchoolClass;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Leseliste einer Lehrkraft für eine Klasse.
 *
 * @property string $id
 * @property int $user_id
 * @property string|null $school_class_id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $ends_on
 * @property bool $is_published
 * @property-read SchoolClass|null $schoolClass
 */
final class ReadingList extends Model
{
    use HasUlids;

    public const LIST_LIMIT = 30;

    public const ITEM_LIMIT = 100;

    protected $table = 'circulation_reading_lists';

    /** @var list<string> */
    protected $fillable = ['user_id', 'school_class_id', 'name', 'description', 'ends_on', 'is_published'];

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /** @return HasMany<ReadingListItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ReadingListItem::class, 'reading_list_id');
    }

    /** Ob die Liste noch läuft (ohne Ende oder Ende nicht vorbei). */
    public function isCurrent(): bool
    {
        return $this->ends_on === null || ! $this->ends_on->endOfDay()->isPast();
    }

    /** Ob die Schüler:innen der Klasse die Liste sehen. */
    public function isVisibleToClass(): bool
    {
        return $this->is_published && $this->school_class_id !== null && $this->isCurrent();
    }

    /** @return list<string> Titel in der Reihenfolge des Hinzufügens */
    public function titleIds(): array
    {
        return $this->items()->orderBy('id')->pluck('title_id')->map(static fn ($id): string => (string) $id)->all();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['ends_on' => 'date', 'is_published' => 'boolean'];
    }
}
