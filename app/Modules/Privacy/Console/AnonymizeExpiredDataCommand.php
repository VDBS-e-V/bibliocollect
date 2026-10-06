<?php

declare(strict_types=1);

namespace App\Modules\Privacy\Console;

use App\Modules\Privacy\Services\AnonymizationService;
use Illuminate\Console\Command;

final class AnonymizeExpiredDataCommand extends Command
{
    protected $signature = 'privacy:anonymize {--dry-run : Nur zählen, nichts ändern}';

    protected $description = 'Anonymisiert abgelaufene Ausleihen, Vormerkungen, Ausleihkonten, Protokolleinträge und Erinnerungen nach der Aufbewahrungsfrist.';

    public function handle(AnonymizationService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = $service->run($dryRun);

        $this->info(($dryRun ? 'Würde anonymisieren' : 'Anonymisiert').' (Stichtag '.$service->cutoff()->format('d.m.Y').'):');
        $this->table(['Bereich', 'Anzahl'], [
            ['Ausleihen', $counts['loans']],
            ['Vormerkungen', $counts['reservations']],
            ['Belege', $counts['transactions']],
            ['Ausleihkonten', $counts['patrons']],
            ['Onlinekonten', $counts['accounts']],
            ['Protokolleinträge', $counts['audit_events']],
            ['Statusereignisse der Ausleihkonten', $counts['status_events']],
            ['Erinnerungen (gelöscht)', $counts['reminders']],
        ]);

        return self::SUCCESS;
    }
}
