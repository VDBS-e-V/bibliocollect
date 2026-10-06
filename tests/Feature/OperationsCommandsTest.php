<?php

declare(strict_types=1);

use App\Foundation\Console\CronCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

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
