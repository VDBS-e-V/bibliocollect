<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Services;

use App\Foundation\Support\BusinessClock;
use App\Modules\Identity\Contracts\PatronLinkGateway;
use App\Modules\Identity\DTOs\LinkablePatron;
use App\Modules\Identity\Exceptions\InvalidPatronLinkCode;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Models\PatronAccountLinkToken;
use App\Modules\Patrons\Support\PatronLinkCodeHasher;

final readonly class EloquentPatronLinkGateway implements PatronLinkGateway
{
    public function __construct(
        private PatronLinkCodeHasher $hasher,
        private BusinessClock $clock,
    ) {}

    public function consume(string $plainCode): LinkablePatron
    {
        $fingerprint = $this->hasher->fingerprint($plainCode);

        $token = PatronAccountLinkToken::query()
            ->with('patron')
            ->where('fingerprint', $fingerprint)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->lockForUpdate()
            ->first();

        if ($token === null || $token->expires_at === null || $token->expires_at->lessThanOrEqualTo($this->clock->now())) {
            throw InvalidPatronLinkCode::invalidOrExpired();
        }

        $patron = $token->patron;

        if ($patron === null || ! $patron->isActive()) {
            throw InvalidPatronLinkCode::patronUnavailable();
        }

        if (! in_array($patron->kind, [PatronKind::Student, PatronKind::Teacher], true)) {
            throw InvalidPatronLinkCode::patronUnavailable();
        }

        $token->forceFill(['used_at' => $this->clock->now()])->save();

        return new LinkablePatron(
            id: (string) $patron->getKey(),
            displayName: $patron->displayName(),
            kind: $patron->kind->value,
        );
    }
}
