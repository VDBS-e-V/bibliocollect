<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Enums\CardBlockReason;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Exceptions\PatronCardConflict;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Models\PatronCard;
use Illuminate\Support\Facades\DB;

/** Ordnet einen Ausweis einer Person zu. Alle früheren Ausweise der Person werden dabei gesperrt. */
final readonly class AssignPatronCardAction
{
    public function __construct(private AuditRecorder $audit, private BlockPatronCardAction $block) {}

    /** @return int Anzahl der dabei gesperrten früheren Ausweise */
    public function execute(string $number, Patron $patron, CardBlockReason $replaceReason = CardBlockReason::Replaced): int
    {
        return DB::transaction(function () use ($number, $patron, $replaceReason): int {
            $card = PatronCard::query()->where('number', trim($number))->lockForUpdate()->first();

            if (! $card instanceof PatronCard) {
                throw PatronCardConflict::unknown();
            }

            if ($card->status === CardStatus::Blocked) {
                throw PatronCardConflict::blocked();
            }

            if (! $patron->isActive()) {
                throw PatronCardConflict::patronNotActive();
            }

            if ($card->status === CardStatus::Assigned) {
                if ($card->patron_id === (string) $patron->getKey()) {
                    return 0;
                }

                throw PatronCardConflict::alreadyAssigned();
            }

            $replaced = 0;

            foreach (PatronCard::query()->where('patron_id', $patron->getKey())->where('status', CardStatus::Assigned->value)->get() as $old) {
                $this->block->execute($old, $replaceReason);
                $replaced++;
            }

            $card->forceFill(['status' => CardStatus::Assigned, 'patron_id' => $patron->getKey(), 'assigned_at' => now()])->save();

            $this->audit->record('patron_cards.assigned', 'Ausweis einer Person zugeordnet.', $card, ['patron_id' => (string) $patron->getKey(), 'replaced' => $replaced]);

            return $replaced;
        });
    }
}
