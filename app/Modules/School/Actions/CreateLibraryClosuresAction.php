<?php

declare(strict_types=1);

namespace App\Modules\School\Actions;

use App\Modules\School\Models\LibraryClosure;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CreateLibraryClosuresAction
{
    public const MAX_DAYS = 400;

    /**
     * Legt für jeden Tag des Zeitraums einen Schließtag an. Bereits eingetragene Tage bleiben unverändert.
     *
     * @return int Anzahl neu angelegter Schließtage
     */
    public function execute(string $from, string $to, ?string $reason): int
    {
        $start = CarbonImmutable::parse($from)->startOfDay();
        $end = CarbonImmutable::parse($to)->startOfDay();

        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('Das Ende darf nicht vor dem Beginn liegen.');
        }

        if ($start->diffInDays($end) >= self::MAX_DAYS) {
            throw new InvalidArgumentException('Der Zeitraum ist zu lang (höchstens '.self::MAX_DAYS.' Tage).');
        }

        $reason = $reason !== null && trim($reason) !== '' ? trim($reason) : null;

        return DB::transaction(static function () use ($start, $end, $reason): int {
            $created = 0;

            for ($day = $start; $day->lessThanOrEqualTo($end); $day = $day->addDay()) {
                // whereDate, weil das Datum je nach Datenbank mit Uhrzeit gespeichert wird.
                if (LibraryClosure::query()->whereDate('date', $day->toDateString())->exists()) {
                    continue;
                }

                LibraryClosure::query()->create(['date' => $day->toDateString(), 'reason' => $reason]);
                $created++;
            }

            return $created;
        });
    }
}
