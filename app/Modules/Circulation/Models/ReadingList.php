<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Models;

use App\Modules\School\Models\SchoolClass;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Leseliste einer Lehrkraft für eine Klasse.
 *
 * @property string $id
 * @property int $user_id
 * @property string $public_token
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $ends_on
 * @property bool $is_published
 * @property-read Collection<int, SchoolClass> $classes
 */
final class ReadingList extends Model
{
    use HasUlids;

    public const LIST_LIMIT = 30;

    public const ITEM_LIMIT = 100;

    protected $table = 'circulation_reading_lists';

    /** @var list<string> */
    protected $fillable = ['user_id', 'name', 'description', 'ends_on', 'is_published'];

    protected static function booted(): void
    {
        // Öffentlicher Link: nicht erratbares Kennzeichen, gesetzt beim Anlegen.
        self::creating(static function (self $list): void {
            if (blank($list->getAttribute('public_token'))) {
                $list->public_token = Str::random(32);
            }
        });
    }

    /** @return BelongsToMany<SchoolClass, $this> */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'circulation_reading_list_classes', 'reading_list_id', 'school_class_id');
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

    /** Ob die Liste über den öffentlichen Link und im Konto der Klassen erreichbar ist. */
    public function isActive(): bool
    {
        return $this->is_published && $this->isCurrent();
    }

    /** Neuen Link erzeugen; der alte funktioniert dann nicht mehr. */
    public function regenerateToken(): void
    {
        $this->forceFill(['public_token' => Str::random(32)])->save();
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
