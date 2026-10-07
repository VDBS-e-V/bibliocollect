<?php

declare(strict_types=1);

namespace App\Foundation\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/** Harmloser Beispieljob, um die Warteschlange und den Web-Cron zu erproben. Er ändert keine Fachdaten. */
final class DemoQueueJob implements ShouldQueue
{
    use Queueable;

    public const COUNTER_KEY = 'app.demo.processed';

    public function __construct(public readonly int $number) {}

    public function handle(): void
    {
        Cache::increment(self::COUNTER_KEY);
        Log::info('Beispieljob der Warteschlange abgearbeitet.', ['number' => $this->number]);
    }
}
