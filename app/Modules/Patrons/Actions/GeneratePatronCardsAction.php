<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Enums\CardStatus;
use App\Modules\Patrons\Models\PatronCard;
use App\Modules\Patrons\Support\PatronCardNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Erzeugt eine Charge Ausweise mit zufälligen, nicht aufeinanderfolgenden Nummern. */
final readonly class GeneratePatronCardsAction
{
    public function __construct(private AuditRecorder $audit) {}

    /** @return int Nummer der neuen Charge */
    public function execute(int $count): int
    {
        return DB::transaction(function () use ($count): int {
            $batch = ((int) PatronCard::query()->max('batch')) + 1;
            $created = 0;
            $now = now();

            while ($created < $count) {
                $candidates = [];

                while (count($candidates) < ($count - $created)) {
                    $base = PatronCardNumber::random();

                    // Weder doppelt noch direkt neben einer anderen Nummer der Charge.
                    if (isset($candidates[$base]) || isset($candidates[$base - 1]) || isset($candidates[$base + 1])) {
                        continue;
                    }

                    $candidates[$base] = true;
                }

                // Gegen vorhandene Nummern prüfen, auch gegen ihre Nachbarn.
                $probe = [];

                foreach (array_keys($candidates) as $base) {
                    foreach ([$base - 1, $base, $base + 1] as $near) {
                        $probe[] = PatronCardNumber::fromBase($near);
                    }
                }

                $taken = [];

                foreach (array_chunk($probe, 500) as $chunk) {
                    foreach (PatronCard::query()->whereIn('number', $chunk)->pluck('number') as $number) {
                        $taken[PatronCardNumber::base((string) $number)] = true;
                    }
                }

                $rows = [];

                foreach (array_keys($candidates) as $base) {
                    if (isset($taken[$base]) || isset($taken[$base - 1]) || isset($taken[$base + 1])) {
                        continue;
                    }

                    $rows[] = [
                        'id' => (string) Str::ulid(),
                        'number' => PatronCardNumber::fromBase($base),
                        'batch' => $batch,
                        'status' => CardStatus::Generated->value,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($rows, 200) as $chunk) {
                    PatronCard::query()->insert($chunk);
                }

                $created += count($rows);
            }

            $this->audit->record('patron_cards.generated', "Charge {$batch} mit {$count} Ausweisen erzeugt.", null, ['batch' => $batch, 'count' => $count]);

            return $batch;
        });
    }
}
