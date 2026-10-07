<?php

declare(strict_types=1);

namespace App\Foundation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $fingerprint
 * @property string $kind exception|job
 * @property string $class
 * @property string $message
 * @property string|null $file
 * @property int|null $line
 * @property string|null $method
 * @property string|null $path
 * @property int|null $user_id
 * @property int $occurrences
 * @property Carbon $first_seen_at
 * @property Carbon $last_seen_at
 */
final class SystemErrorEvent extends Model
{
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = ['fingerprint', 'kind', 'class', 'message', 'file', 'line', 'method', 'path', 'user_id', 'occurrences', 'first_seen_at', 'last_seen_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime', 'occurrences' => 'integer', 'line' => 'integer'];
    }
}
