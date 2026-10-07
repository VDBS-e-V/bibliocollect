<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Enums\CardBlockReason;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Models\PatronCard;

final readonly class BlockPatronCardAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(PatronCard $card, CardBlockReason $reason): PatronCard
    {
        if ($card->status === CardStatus::Blocked) {
            return $card;
        }

        $card->forceFill(['status' => CardStatus::Blocked, 'block_reason' => $reason, 'blocked_at' => now()])->save();

        $this->audit->record('patron_cards.blocked', 'Ausweis gesperrt ('.$reason->label().').', $card, ['patron_id' => $card->patron_id, 'reason' => $reason->value]);

        return $card;
    }
}
