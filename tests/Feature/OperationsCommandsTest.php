<?php

declare(strict_types=1);

use App\Foundation\Console\CronCommand;
use App\Foundation\Jobs\DemoQueueJob;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;

uses(RefreshDatabase::class);

afterEach(function (): void {
    File::deleteDirectory(storage_path('app/backups'));
});

it('runs due schedule tasks and the queue in one cron call and leaves a heartbeat', function (): void {
    Cache::forget(CronCommand::HEARTBEAT_KEY);

    $this->artisan('app:cron', ['--queue-seconds' => 5])
        ->expectsOutputToContain('Warteschlange abgearbeitet')
        ->assertSuccessful();

    expect(Cache::get(CronCommand::HEARTBEAT_KEY))->not->toBeNull();
});

it('reports the state of the installation with the doctor command', function (): void {
    $this->artisan('app:doctor')
        ->expectsOutputToContain('PHP-Erweiterungen')
        ->expectsOutputToContain('Migrationen')
        ->assertSuccessful();
});

it('lets the doctor fail when the cron job stopped in production', function (): void {
    app()->detectEnvironment(static fn (): string => 'production');
    config(['app.debug' => true]);
    Cache::put(CronCommand::HEARTBEAT_KEY, now()->subDay()->toIso8601String());

    $this->artisan('app:doctor')
        ->expectsOutputToContain('APP_DEBUG')
        ->expectsOutputToContain('Zeitplan und Warteschlange stehen')
        ->assertFailed();
});

it('writes a database backup and keeps only the newest ones', function (): void {
    $directory = storage_path('app/backups');
    File::ensureDirectoryExists($directory);

    foreach (['2020-01-01_000001', '2020-01-02_000001', '2020-01-03_000001'] as $stamp) {
        file_put_contents($directory.'/datenbank-'.$stamp.'.sqlite', 'alt');
    }

    // VACUUM INTO geht nicht innerhalb einer Transaktion; RefreshDatabase hat eine geöffnet.
    while (DB::transactionLevel() > 0) {
        DB::rollBack();
    }

    $this->artisan('backup:database', ['--keep' => 2])->expectsOutputToContain('Sicherung geschrieben')->assertSuccessful();

    $files = collect(File::files($directory))->map(fn ($file) => $file->getFilename())->sort()->values();

    expect($files)->toHaveCount(2)
        ->and($files->last())->toStartWith('datenbank-'.now()->format('Y'));
});

it('sends a test mail and rejects invalid addresses', function (): void {
    Mail::fake();

    $this->artisan('mail:test', ['address' => 'kein-mail'])->assertFailed();

    $this->artisan('mail:test', ['address' => 'jemand@example.org'])->assertSuccessful();
});

it('knows the cron and operations commands outside the console, as the web cron needs them', function (): void {
    $code = "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); "
        ."echo (\$app->runningInConsole() ? 'konsole' : 'web').'|'.implode(',', array_filter(['app:cron', 'backup:database', 'reminders:send', 'circulation:reservations:expire', 'privacy:anonymize', 'catalog:covers:queue'], static fn (string \$name): bool => array_key_exists(\$name, Illuminate\Support\Facades\Artisan::all())));";

    $process = new Process([PHP_BINARY, '-r', $code], base_path(), ['APP_RUNNING_IN_CONSOLE' => 'false', 'APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:']);
    $process->run();

    expect(trim($process->getOutput()))->toBe('web|app:cron,backup:database,reminders:send,circulation:reservations:expire,privacy:anonymize,catalog:covers:queue');
});

it('runs the schedule tasks inside the same process so that the web cron can start them', function (): void {
    $events = collect(app(Schedule::class)->events());

    expect($events->map(static fn (Event $event): string => $event->description ?? '')->sort()->values()->all())
        ->toBe(['backup:database', 'catalog:covers:queue', 'catalog:quality:propose', 'circulation:reservations:expire', 'privacy:anonymize', 'reminders:send', 'system:prune-errors'])
        ->and($events->every(static fn (Event $event): bool => $event instanceof CallbackEvent))->toBeTrue();

    // Um 04:00 läuft das Ablaufen der Abholfristen über schedule:run, ohne zweiten PHP-Prozess.
    Carbon::setTestNow(Carbon::parse('2026-10-08 04:00:30', config('app.timezone')));

    $this->artisan('schedule:run')->expectsOutputToContain('circulation:reservations:expire')->assertSuccessful();

    Carbon::setTestNow();
});

it('queues demo jobs and reports how many the cron call worked off', function (): void {
    config(['queue.default' => 'database']);
    Cache::forget(DemoQueueJob::COUNTER_KEY);

    $this->artisan('app:demo:queue', ['count' => 3])->expectsOutputToContain('3 Beispieljob(s)')->assertSuccessful();
    expect(DB::table('jobs')->count())->toBe(3);

    $this->artisan('app:cron', ['--queue-seconds' => 5])
        ->expectsOutputToContain('Jobs: 3 abgearbeitet, 0 wartend, 0 fehlgeschlagen')
        ->assertSuccessful();

    expect(DB::table('jobs')->count())->toBe(0)->and((int) Cache::get(DemoQueueJob::COUNTER_KEY))->toBe(3);

    // Ein zweiter Lauf hat nichts mehr zu tun.
    $this->artisan('app:cron', ['--queue-seconds' => 5])->expectsOutputToContain('Jobs: 0 abgearbeitet')->assertSuccessful();
});

it('refuses to queue demo jobs in production', function (): void {
    app()->detectEnvironment(static fn (): string => 'production');

    $this->artisan('app:demo:queue')->expectsOutputToContain('Im Produktivbetrieb nicht verfügbar')->assertFailed();
});
