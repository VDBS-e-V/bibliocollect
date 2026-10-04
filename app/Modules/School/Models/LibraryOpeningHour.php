<?php

declare(strict_types=1);

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class LibraryOpeningHour extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['day_of_week', 'is_open', 'opens_at', 'closes_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_open' => 'boolean',
        ];
    }
}
