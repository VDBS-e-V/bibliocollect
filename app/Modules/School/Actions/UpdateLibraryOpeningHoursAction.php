<?php

declare(strict_types=1);

namespace App\Modules\School\Actions;

use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class UpdateLibraryOpeningHoursAction
{
    /**
     * Speichert die Wochenübersicht. Schlüssel 1 (Montag) bis 7 (Sonntag); geschlossene Tage verlieren ihre Zeiten.
     *
     * @param  array<int, array{is_open: bool, opens_at: ?string, closes_at: ?string}>  $days
     */
    public function execute(array $days): void
    {
        $open = array_filter($days, static fn (array $day): bool => $day['is_open']);

        if ($open === []) {
            // Ohne Öffnungstag lässt sich keine Fälligkeit berechnen.
            throw new InvalidArgumentException('Mindestens ein Wochentag muss geöffnet sein.');
        }

        DB::transaction(static function () use ($days): void {
            foreach (range(1, 7) as $dayOfWeek) {
                $day = $days[$dayOfWeek] ?? ['is_open' => false, 'opens_at' => null, 'closes_at' => null];

                LibraryOpeningHour::query()->updateOrCreate(
                    ['day_of_week' => $dayOfWeek],
                    [
                        'is_open' => $day['is_open'],
                        'opens_at' => $day['is_open'] ? $day['opens_at'] : null,
                        'closes_at' => $day['is_open'] ? $day['closes_at'] : null,
                    ],
                );
            }
        });
    }
}
