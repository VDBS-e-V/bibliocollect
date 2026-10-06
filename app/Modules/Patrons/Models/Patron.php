<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Models;

use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Enums\PatronStatus;
use App\Modules\School\Models\SchoolClass;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $library_number
 * @property PatronKind $kind
 * @property PatronStatus $status
 * @property string $first_name
 * @property string $last_name
 * @property Carbon $birth_date
 * @property Carbon|null $blocked_at
 * @property string|null $blocked_reason
 */
final class Patron extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'library_number',
        'kind',
        'status',
        'first_name',
        'last_name',
        'birth_date',
        'email',
        'school_class_id',
        'leaving_on',
        'blocked_at',
        'blocked_reason',
    ];

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /** @return HasMany<PatronAccountLinkToken, $this> */
    public function accountLinkTokens(): HasMany
    {
        return $this->hasMany(PatronAccountLinkToken::class);
    }

    public function displayName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function isActive(): bool
    {
        return $this->status === PatronStatus::Active;
    }

    public function canLinkOnlineAccount(): bool
    {
        return $this->isActive()
            && in_array($this->kind, [PatronKind::Student, PatronKind::Teacher], true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => PatronKind::class,
            'status' => PatronStatus::class,
            'birth_date' => 'date',
            'leaving_on' => 'date',
            'blocked_at' => 'datetime',
        ];
    }
}
