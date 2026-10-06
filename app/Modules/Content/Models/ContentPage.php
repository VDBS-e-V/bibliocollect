<?php

declare(strict_types=1);

namespace App\Modules\Content\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $slug
 * @property string $title
 * @property string $body
 * @property bool $is_placeholder
 * @property int|null $updated_by_user_id
 */
final class ContentPage extends Model
{
    use HasUlids;

    protected $table = 'content_pages';

    /** @var list<string> */
    protected $fillable = ['slug', 'title', 'body', 'is_placeholder', 'updated_by_user_id'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_placeholder' => 'boolean'];
    }
}
