<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\Models\Patron;
use App\Modules\Patrons\Support\PatronLibraryNumber;
use Illuminate\Support\Facades\DB;

/**
 * Ersetzt alle Bibliotheksnummern, die noch nicht dem Schema „sechs Zufallsziffern“ folgen, durch neue Zufallsnummern.
 * Anonymisierte Konten bleiben unberührt. Alles oder nichts. Die Zuordnung alt → neu geht zurück, damit sich bereits
 * ausgegebene Zettel oder Listen zuordnen lassen.
 */
final readonly class RenumberPatronsAction
{
    /** Vorsilbe anonymisierter Konten (siehe Datenschutz); sie behalten ihre Kennung. */
    private const ANONYMIZED_PREFIX = 'ANON-';

    public function __construct(private AuditRecorder $audit) {}

    /**
     * @return list<array{id: string, old: string, new: string, name: string}>
     */
    public function execute(bool $dryRun = false): array
    {
        $patrons = Patron::query()
            ->where('library_number', 'not like', self::ANONYMIZED_PREFIX.'%')
            ->orderBy('library_number')
            ->get()
            ->reject(static fn (Patron $patron): bool => preg_match('/^[1-9]\d{5}$/', $patron->library_number) === 1)
            ->values();

        if ($dryRun || $patrons->isEmpty()) {
            return $patrons->map(static fn (Patron $patron): array => [
                'id' => (string) $patron->getKey(),
                'old' => $patron->library_number,
                'new' => '',
                'name' => $patron->last_name.', '.$patron->first_name,
            ])->all();
        }

        return DB::transaction(function () use ($patrons): array {
            $changes = [];

            foreach ($patrons as $patron) {
                $old = $patron->library_number;
                $new = PatronLibraryNumber::generate();

                $patron->forceFill(['library_number' => $new])->save();

                $changes[] = ['id' => (string) $patron->getKey(), 'old' => $old, 'new' => $new, 'name' => $patron->last_name.', '.$patron->first_name];
            }

            $this->audit->record(
                'patrons.library_numbers.renumbered',
                count($changes).' Bibliotheksnummern durch Zufallsnummern ersetzt.',
                null,
                ['count' => count($changes)],
            );

            return $changes;
        });
    }
}
