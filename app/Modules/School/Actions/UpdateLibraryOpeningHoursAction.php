<?php

declare(strict_types=1);

namespace App\Modules\School\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\School\Models\LibraryOpeningHour;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateLibraryOpeningHoursAction
{
    public function __construct(private AuditRecorder $audit) {}

    /**
     * Speichert die Wochenübersicht. Schlüssel 1 (Montag) bis 7 (Sonntag). Ein geöffneter Tag hat einen oder mehrere
     * Zeiträume (`H:i`), die sich nicht überschneiden; ein geschlossener Tag hat keine.
     *
     * @param  array<int, array{is_open: bool, ranges: list<array{from: string, to: string}>}>  $days
     */
    public function execute(array $days): void
    {
        $open = array_filter($days, static fn (array $day): bool => $day['is_open']);

        if ($open === []) {
            // Ohne Öffnungstag lässt sich keine Fälligkeit berechnen.
            throw new InvalidArgumentException('Mindestens ein Wochentag muss geöffnet sein.');
        }

        foreach ($open as $day) {
            if ($day['ranges'] === []) {
                throw new InvalidArgumentException('Für geöffnete Tage wird mindestens ein Zeitraum benötigt.');
            }
        }

        DB::transaction(function () use ($days, $open): void {
            foreach (range(1, 7) as $dayOfWeek) {
                $day = $days[$dayOfWeek] ?? ['is_open' => false, 'ranges' => []];

                LibraryOpeningHour::query()->where('day_of_week', $dayOfWeek)->delete();

                if (! $day['is_open']) {
                    LibraryOpeningHour::query()->create(['day_of_week' => $dayOfWeek, 'is_open' => false, 'opens_at' => null, 'closes_at' => null]);

                    continue;
                }

                foreach ($day['ranges'] as $range) {
                    LibraryOpeningHour::query()->create([
                        'day_of_week' => $dayOfWeek,
                        'is_open' => true,
                        'opens_at' => $range['from'],
                        'closes_at' => $range['to'],
                    ]);
                }
            }

            $this->audit->record(
                'school.opening_hours.updated',
                'Öffnungszeiten geändert.',
                null,
                ['open_days' => implode(',', array_keys($open))],
            );
        });
    }
}
