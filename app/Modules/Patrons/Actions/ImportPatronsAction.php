<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Actions;

use App\Models\User;
use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Patrons\DTOs\PatronCreateData;
use App\Modules\Patrons\Enums\PatronKind;
use App\Modules\Patrons\Import\PatronImportPlanner;
use App\Modules\Patrons\Models\Patron;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Legt die neuen Ausleihkonten einer geprüften Importdatei an. Der Import läuft ganz oder gar nicht und nur,
 * wenn die Prüfung keine Fehler meldet. Vorhandene und doppelte Personen werden übersprungen, nie überschrieben.
 */
final readonly class ImportPatronsAction
{
    private const PREFIXES = [
        'student' => ['S', 10001],
        'teacher' => ['L', 20001],
        'employee' => ['M', 30001],
    ];

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

            $next = $this->nextNumbers();
            $created = 0;

            foreach ($plan['rows'] as $row) {
                if ($row['status'] !== 'new') {
                    continue;
                }

                /** @var PatronKind $kind */
                $kind = $row['kind'];
                $number = $row['library_number'] ?? $this->generate($kind, $next);

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

    /**
     * Nächste freie laufende Nummer je Art, ausgehend von den vorhandenen Nummern im Schema „S-10001“.
     *
     * @return array<string, int>
     */
    private function nextNumbers(): array
    {
        $existing = Patron::query()->pluck('library_number')->all();
        $next = [];

        foreach (self::PREFIXES as $kind => [$prefix, $start]) {
            $highest = $start - 1;

            foreach ($existing as $number) {
                if (preg_match('/^'.$prefix.'-(\d+)$/i', $number, $match) === 1) {
                    $highest = max($highest, (int) $match[1]);
                }
            }

            $next[$kind] = $highest + 1;
        }

        return $next;
    }

    /** @param  array<string, int>  $next */
    private function generate(PatronKind $kind, array &$next): string
    {
        [$prefix] = self::PREFIXES[$kind->value];
        $number = $prefix.'-'.$next[$kind->value];
        $next[$kind->value]++;

        return $number;
    }
}
