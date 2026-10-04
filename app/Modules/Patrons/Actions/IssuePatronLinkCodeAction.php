<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Foundation\Support\BusinessClock;
use App\Models\User;
use App\Modules\Patrons\DTOs\IssuedPatronLinkCode;
use App\Modules\Patrons\Exceptions\PatronLinkCodeCannotBeIssued;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronAccountLinkToken;
use App\Modules\Patrons\Support\PatronLinkCodeGenerator;
use App\Modules\Patrons\Support\PatronLinkCodeHasher;
use Illuminate\Support\Facades\DB;

final readonly class IssuePatronLinkCodeAction
{
    public function __construct(
        private PatronLinkCodeGenerator $generator,
        private PatronLinkCodeHasher $hasher,
        private BusinessClock $clock,
    ) {}

    public function execute(Patron $patron, ?User $issuedBy = null, ?int $ttlMinutes = null): IssuedPatronLinkCode
    {
        if (! $patron->isActive()) {
            throw new PatronLinkCodeCannotBeIssued;
        }

        $ttl = $ttlMinutes ?? (int) config('identity.patron_link_code_ttl_minutes', 30);
        $expiresAt = $this->clock->now()->addMinutes(max(5, $ttl));

        return DB::transaction(function () use ($patron, $issuedBy, $expiresAt): IssuedPatronLinkCode {
            PatronAccountLinkToken::query()
                ->where('patron_id', $patron->getKey())
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => $this->clock->now()]);

            do {
                $code = $this->generator->generate();
                $fingerprint = $this->hasher->fingerprint($code);
            } while (PatronAccountLinkToken::query()->where('fingerprint', $fingerprint)->exists());

            PatronAccountLinkToken::query()->create([
                'patron_id' => $patron->getKey(),
                'fingerprint' => $fingerprint,
                'expires_at' => $expiresAt,
                'issued_by_user_id' => $issuedBy?->getKey(),
            ]);

            return new IssuedPatronLinkCode($code, $expiresAt);
        });
    }
}
