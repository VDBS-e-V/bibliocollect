<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Enums\CopyStatus;
use App\Modules\Catalog\Enums\WithdrawalFate;
use App\Modules\Catalog\Enums\WithdrawalReason;
use App\Modules\Catalog\Models\Copy;
use Illuminate\Support\Facades\DB;

/** Sondert Exemplare aus: Status „ausgesondert“, Grund, Datum und Verbleib. Das Exemplar bleibt im System und lässt sich zurückholen. */
final readonly class WithdrawCopiesAction
{
    public function __construct(private AuditRecorder $audit) {}

    /**
     * @param  list<string>  $copyIds
     * @return int Anzahl der ausgesonderten Exemplare
     */
    public function execute(array $copyIds, WithdrawalReason $reason, WithdrawalFate $fate, string $date): int
    {
        return DB::transaction(function () use ($copyIds, $reason, $fate, $date): int {
            $count = 0;

            foreach (Copy::query()->whereIn('id', $copyIds)->where('status', '!=', CopyStatus::Withdrawn->value)->lockForUpdate()->get() as $copy) {
                $copy->forceFill([
                    'status' => CopyStatus::Withdrawn,
                    'depreciation_reason' => $reason->value,
                    'further_use' => $fate->value,
                    'depreciated_at' => $date,
                ])->save();

                $this->audit->record('catalog.copy.withdrawn', 'Exemplar ausgesondert.', $copy, ['reason' => $reason->value, 'fate' => $fate->value]);
                $count++;
            }

            return $count;
        });
    }
}
