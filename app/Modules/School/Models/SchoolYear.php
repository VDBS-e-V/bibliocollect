<?php

declare(strict_types=1);

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SchoolYear extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_active'];

    /** @return HasMany<SchoolClass, $this> */
    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
