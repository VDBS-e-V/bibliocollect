<?php

declare(strict_types=1);

namespace App\Modules\Content\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Ein Textbaustein an einer festen Stelle (Schlüssel aus config/content.php).
 *
 * @property string $id
 * @property string $key
 * @property string $body
 * @property bool $is_active
 * @property Carbon|null $visible_from
 * @property Carbon|null $visible_until
 * @property int|null $updated_by_user_id
 */
final class ContentBlock extends Model
{
    use HasUlids;

    protected $table = 'content_blocks';

    /** @var list<string> */
    protected $fillable = ['key', 'body', 'is_active', 'visible_from', 'visible_until', 'updated_by_user_id'];

    /** Ob der Baustein heute zu sehen ist: eingeschaltet und im Zeitfenster. */
    public function isVisibleOn(Carbon $day): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->visible_from !== null && $day->toDateString() < $this->visible_from->toDateString()) {
            return false;
        }

        return $this->visible_until === null || $day->toDateString() <= $this->visible_until->toDateString();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'visible_from' => 'date', 'visible_until' => 'date'];
    }
}
