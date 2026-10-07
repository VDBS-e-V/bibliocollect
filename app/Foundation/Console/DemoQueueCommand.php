<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use App\Foundation\Jobs\DemoQueueJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/** Stellt Beispieljobs in die Warteschlange, damit man den Web-Cron (/_cron) sichtbar ausprobieren kann. */
final class DemoQueueCommand extends Command
{
    protected $signature = 'app:demo:queue {count=5 : Anzahl der Beispieljobs (1 bis 100)}';

    protected $description = 'Stellt harmlose Beispieljobs in die Warteschlange, um Cron und Queue zu erproben (nicht im Produktivbetrieb).';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Im Produktivbetrieb nicht verfügbar.');

            return self::FAILURE;
        }

        $count = max(1, min(100, (int) $this->argument('count')));

        for ($number = 1; $number <= $count; $number++) {
            DemoQueueJob::dispatch($number);
        }

        $this->info("{$count} Beispieljob(s) in die Warteschlange gestellt. Insgesamt bisher abgearbeitet: ".(int) Cache::get(DemoQueueJob::COUNTER_KEY, 0).'.');
        $this->line('Jetzt /_cron aufrufen (oder app:cron ausführen): Die Antwort nennt, wie viele Jobs abgearbeitet wurden.');

        return self::SUCCESS;
    }
}
