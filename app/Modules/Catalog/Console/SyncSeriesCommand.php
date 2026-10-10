<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Console;

use App\Modules\Catalog\Services\SeriesService;
use Illuminate\Console\Command;

final class SyncSeriesCommand extends Command
{
    protected $signature = 'catalog:series:sync';

    protected $description = 'Ordnet alle Ausgaben mit Reihenangabe ihrer Reihe und Bandnummer zu (nach Import oder Massenänderungen).';

    public function handle(SeriesService $series): int
    {
        $this->info($series->syncAll().' Ausgaben aktualisiert.');

        return self::SUCCESS;
    }
}
