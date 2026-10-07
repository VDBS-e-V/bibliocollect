<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\DTOs\PatronCreateData;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Import\PatronImportPlanner;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Legt die neuen Ausleihkonten einer geprüften Importdatei an. Der Import läuft ganz oder gar nicht und nur,
 * wenn die Prüfung keine Fehler meldet. Vorhandene und doppelte Personen werden übersprungen, nie überschrieben.
 */
final readonly class ImportPatronsAction
{
    public function __construct(
        private PatronImportPlanner $planner,
        private CreatePatronAction $create,
        private AuditRecorder $audit,
    ) {}

    /**
     * @param  list<array{line: int, values: array<string, string>}>  $rows
     * @return array{created: int, skipped: int}
     *
     * @throws InvalidArgumentException wenn die Prüfung Fehler meldet
     */
    public function execute(array $rows, User $actor): array
    {
        return DB::transaction(function () use ($rows, $actor): array {
            $plan = $this->planner->plan($rows);

            if ($plan['counts']['error'] > 0) {
                throw new InvalidArgumentException('Die Datei enthält noch Fehler. Bitte korrigieren und erneut hochladen.');
            }

            $created = 0;

            foreach ($plan['rows'] as $row) {
                if ($row['status'] !== 'new') {
                    continue;
                }

                /** @var PatronKind $kind */
                $kind = $row['kind'];
                $number = $row['library_number'] ?? '';

                $this->create->execute(new PatronCreateData(
                    libraryNumber: $number,
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
                "Ausleihkonten importiert: {$created} angelegt, {$skipped} übersprungen.",
                null,
                ['created' => $created, 'skipped' => $skipped],
                (int) $actor->getKey(),
            );

            return ['created' => $created, 'skipped' => $skipped];
        });
    }
}
