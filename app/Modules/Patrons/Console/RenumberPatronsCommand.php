<?php

declare(strict_types=1);

namespace App\Modules\Patrons\Console;

use App\Modules\Patrons\Actions\RenumberPatronsAction;
use Illuminate\Console\Command;

final class RenumberPatronsCommand extends Command
{
    protected $signature = 'patrons:renumber
        {--dry-run : Nur zählen, nichts ändern}
        {--yes : Ohne Rückfrage ausführen}';

    protected $description = 'Ersetzt alte Bibliotheksnummern (S-10001 usw.) durch zufällige sechsstellige Nummern.';

    public function handle(RenumberPatronsAction $renumber): int
    {
        $pending = $renumber->execute(dryRun: true);

        if ($pending === []) {
            $this->info('Alle Bibliotheksnummern folgen schon dem Schema (sechs Ziffern).');

            return self::SUCCESS;
        }

        $this->line(count($pending).' Bibliotheksnummern würden ersetzt (z. B. '.$pending[0]['old'].').');

        if ($this->option('dry-run')) {
            $this->info('Probelauf: Es wurde nichts geändert.');

            return self::SUCCESS;
        }

        if (! $this->option('yes')) {
            if (! $this->input->isInteractive()) {
                $this->error('Das ändert alle alten Nummern. Ohne Rückfrage-Möglichkeit bitte --yes angeben.');

                return self::FAILURE;
            }

            $this->warn('Bereits gedruckte Zettel oder Listen mit den alten Nummern passen danach nicht mehr. Sichere vorher die Datenbank (php artisan backup:database).');

            if (! $this->confirm('Alle alten Nummern jetzt ersetzen?', false)) {
                $this->warn('Abgebrochen. Es wurde nichts geändert.');

                return self::FAILURE;
            }
        }

        $changes = $renumber->execute();

        $directory = storage_path('app/private');

        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }

        $file = $directory.DIRECTORY_SEPARATOR.'bibliotheksnummern-alt-neu-'.date('Ymd-His').'.csv';
        $handle = fopen($file, 'w');

        if ($handle !== false) {
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Alte Nummer', 'Neue Nummer', 'Name'], ';');

            foreach ($changes as $change) {
                fputcsv($handle, [$change['old'], $change['new'], $change['name']], ';');
            }

            fclose($handle);
        }

        $this->info(count($changes).' Bibliotheksnummern ersetzt.');
        $this->line('Zuordnung alt → neu (enthält Namen, vertraulich): '.$file);

        return self::SUCCESS;
    }
}
