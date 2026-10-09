<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\DTOs\PatronCreateData;
use App\Modules\Patrons\DTOs\PatronUpdateData;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Import\PatronImportPlanner;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Legt die neuen Ausleihkonten einer geprüften Klassenliste an (alle in der gewählten Klasse, ohne Ausweis). Der Import läuft ganz oder gar nicht und nur,
 * wenn die Prüfung keine Fehler meldet. Vorhandene und doppelte Personen werden übersprungen. Nur auf Wunsch (Aktualisieren) ändert der
 * Import bei vorhandenen Personen die E-Mail-Adresse (nie auf leer) und die Klasse; Name, Geburtsdatum und Nummer bleiben.
 */
final readonly class ImportPatronsAction
{
    public function __construct(
        private PatronImportPlanner $planner,
        private CreatePatronAction $create,
        private UpdatePatronAction $updatePatron,
        private AuditRecorder $audit,
    ) {}

    /**
     * @param  list<array{line: int, values: array<string, string>}>  $rows
     * @return array{created: int, updated: int, skipped: int}
     *
     * @throws InvalidArgumentException wenn die Prüfung Fehler meldet
     */
    public function execute(array $rows, ?string $classId, User $actor, bool $update = false): array
    {
        return DB::transaction(function () use ($rows, $classId, $actor, $update): array {
            $plan = $this->planner->plan($rows, $classId, $update);

            if ($plan['counts']['error'] > 0) {
                throw new InvalidArgumentException('Die Datei enthält noch Fehler. Bitte korrigieren und erneut hochladen.');
            }

            $created = 0;
            $updated = 0;

            foreach ($plan['rows'] as $row) {
                if ($row['status'] === 'update' && is_string($row['patron_id'])) {
                    $patron = Patron::query()->findOrFail($row['patron_id']);
                    $this->updatePatron->execute($patron, new PatronUpdateData(
                        libraryNumber: $patron->library_number,
                        firstName: $patron->first_name,
                        lastName: $patron->last_name,
                        birthDate: $patron->birth_date->toDateString(),
                        email: $row['email'] ?? $patron->email,
                        schoolClassId: $row['school_class_id'] ?? $patron->school_class_id,
                        leavingOn: $patron->leaving_on?->toDateString(),
                    ));
                    $updated++;

                    continue;
                }

                if ($row['status'] !== 'new') {
                    continue;
                }

                /** @var PatronKind $kind */
                $kind = $row['kind'];
                $this->create->execute(new PatronCreateData(
                    libraryNumber: '', // leer: wird zufällig vergeben
                    kind: $kind,
                    firstName: $row['first_name'],
                    lastName: $row['last_name'],
                    birthDate: $row['birth_date'],
                    email: $row['email'],
                    schoolClassId: $kind === PatronKind::Student ? $row['school_class_id'] : null,
                    leavingOn: null,
                ));

                $created++;
            }

            $skipped = $plan['counts']['existing'] + $plan['counts']['duplicate'];

            $this->audit->record(
                'patrons.import.committed',
                "Ausleihkonten importiert: {$created} angelegt, {$updated} aktualisiert, {$skipped} übersprungen.",
                null,
                ['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'class_id' => $classId],
                (int) $actor->getKey(),
            );

            return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped];
        });
    }
}
