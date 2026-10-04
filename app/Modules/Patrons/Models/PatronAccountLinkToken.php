<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $patron_id
 * @property string $fingerprint
 * @property Carbon|null $expires_at
 * @property Carbon|null $used_at
 * @property Carbon|null $revoked_at
 * @property Patron|null $patron
 */
final class PatronAccountLinkToken extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'patron_id',
        'fingerprint',
        'expires_at',
        'used_at',
        'revoked_at',
        'issued_by_user_id',
    ];

    /** @return BelongsTo<Patron, $this> */
    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
