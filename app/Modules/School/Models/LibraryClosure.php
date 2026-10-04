<?php

declare(strict_types=1);

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class LibraryClosure extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['date', 'reason'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
