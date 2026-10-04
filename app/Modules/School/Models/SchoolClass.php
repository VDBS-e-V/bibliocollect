<?php

declare(strict_types=1);

namespace App\Modules\School\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SchoolClass extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = ['school_year_id', 'name', 'grade_level', 'is_active'];

    /** @return BelongsTo<SchoolYear, $this> */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
