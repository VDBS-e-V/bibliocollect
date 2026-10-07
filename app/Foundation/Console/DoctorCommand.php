<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Prüft, ob die Installation für den Betrieb richtig eingerichtet ist. Gibt je Prüfung OK, Warnung oder Fehler aus.
 */
final class DoctorCommand extends Command
{
    protected $signature = 'app:doctor';

    protected $description = 'Prüft die Einrichtung: PHP, Erweiterungen, Schlüssel, Datenbank, Rechte, Warteschlange, Zeitplan, Mail und Cover.';

    public function handle(): int
    {
        $rows = [];
        $production = app()->environment('production');

        $rows[] = version_compare(PHP_VERSION, '8.4.0', '>=')
            ? ['OK', 'PHP', PHP_VERSION]
            : ['Fehler', 'PHP', PHP_VERSION.' (benötigt wird mindestens 8.4)'];

        $missing = array_values(array_filter(['mbstring', 'openssl', 'pdo', 'curl', 'fileinfo', 'ctype', 'json', 'tokenizer', 'xml'], static fn (string $extension): bool => ! extension_loaded($extension)));
        $rows[] = $missing === [] ? ['OK', 'PHP-Erweiterungen', 'alle benötigten vorhanden'] : ['Fehler', 'PHP-Erweiterungen', 'fehlen: '.implode(', ', $missing)];

        $rows[] = config('app.key') ? ['OK', 'APP_KEY', 'gesetzt'] : ['Fehler', 'APP_KEY', 'fehlt (php artisan key:generate)'];

        if ($production) {
            $rows[] = config('app.debug') ? ['Fehler', 'APP_DEBUG', 'ist im Produktivbetrieb an und zeigt Fehlerdetails'] : ['OK', 'APP_DEBUG', 'aus'];
            $rows[] = str_starts_with((string) config('app.url'), 'https://') ? ['OK', 'APP_URL', (string) config('app.url')] : ['Warnung', 'APP_URL', (string) config('app.url').' (im Betrieb sollte es https sein)'];
        } else {
            $rows[] = ['OK', 'Umgebung', (string) app()->environment().' (Prüfungen für den Produktivbetrieb entfallen)'];
        }

        $databaseOk = false;

        try {
            DB::connection()->getPdo();
            $databaseOk = true;
            $rows[] = ['OK', 'Datenbank', (string) config('database.default')];
        } catch (Throwable $exception) {
            $rows[] = ['Fehler', 'Datenbank', 'keine Verbindung: '.$exception->getMessage()];
        }

        if ($databaseOk) {
            $pending = $this->pendingMigrations();
            $rows[] = $pending === 0 ? ['OK', 'Migrationen', 'alle ausgeführt'] : ['Fehler', 'Migrationen', "{$pending} offen (php artisan migrate --force)"];

            if ($production && Schema::hasTable('users')) {
                $demo = DB::table('users')->where('email', 'like', '%@demo.bibliocollect.test')->count();
                $rows[] = $demo > 0
                    ? ['Fehler', 'Demo-Konten', "{$demo} Konten mit Demo-Adresse und bekanntem Passwort vorhanden. Bitte löschen"]
                    : ['OK', 'Demo-Konten', 'keine vorhanden'];
            }

            if (Schema::hasTable('jobs')) {
                $jobs = DB::table('jobs')->count();
                $failed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
                $rows[] = $failed > 0 ? ['Warnung', 'Warteschlange', "{$jobs} wartend, {$failed} fehlgeschlagen (php artisan queue:failed)"] : ['OK', 'Warteschlange', "{$jobs} wartend, keine fehlgeschlagen"];
            }
        }

        $heartbeat = Cache::get(CronCommand::HEARTBEAT_KEY);
        $lastRun = is_string($heartbeat) ? CarbonImmutable::parse($heartbeat) : null;
        $rows[] = match (true) {
            $lastRun === null => $production ? ['Fehler', 'Cronjob', 'noch nie gelaufen (php artisan app:cron muss regelmäßig laufen)'] : ['Warnung', 'Cronjob', 'noch nie gelaufen (php artisan app:cron oder schedule:work)'],
            $lastRun->lessThan(now()->subHours(2)) => ['Fehler', 'Cronjob', 'zuletzt '.$lastRun->diffForHumans().'; Zeitplan und Warteschlange stehen'],
            $lastRun->lessThan(now()->subMinutes(15)) => ['Warnung', 'Cronjob', 'zuletzt '.$lastRun->diffForHumans()],
            default => ['OK', 'Cronjob', 'zuletzt '.$lastRun->diffForHumans()],
        };

        foreach ([storage_path('app'), storage_path('logs'), storage_path('framework'), base_path('bootstrap/cache')] as $path) {
            $rows[] = is_writable($path) ? ['OK', 'Schreibrechte', str_replace(base_path().DIRECTORY_SEPARATOR, '', $path)] : ['Fehler', 'Schreibrechte', 'nicht beschreibbar: '.$path];
        }

        $coverDisk = (string) config('catalog.covers.disk', 'public');
        $coverRoot = (string) config('filesystems.disks.'.$coverDisk.'.root', '');
        $rows[] = $coverRoot !== '' && (is_dir($coverRoot) ? is_writable($coverRoot) : is_writable(dirname($coverRoot)))
            ? ['OK', 'Cover-Speicher', "Laufwerk „{$coverDisk}“"]
            : ['Fehler', 'Cover-Speicher', "Laufwerk „{$coverDisk}“ ist nicht beschreibbar"];

        if ($coverDisk === 'public' && ! file_exists(public_path('storage'))) {
            $rows[] = ['Warnung', 'storage:link', 'fehlt. Cover wären nicht sichtbar (php artisan storage:link oder CATALOG_COVER_DISK=covers)'];
        }

        $mailer = (string) config('mail.default');
        $rows[] = in_array($mailer, ['log', 'array'], true)
            ? [$production ? 'Warnung' : 'OK', 'Mail', "Versand über „{$mailer}“: Erinnerungen werden nur ins Log geschrieben"]
            : ['OK', 'Mail', $mailer.' (Test: php artisan mail:test adresse@example.org)'];

        $alert = config('hosting.alert_email');
        $rows[] = is_string($alert) && trim($alert) !== ''
            ? ['OK', 'Betriebsmeldungen', 'gehen an '.trim($alert)]
            : [$production ? 'Warnung' : 'OK', 'Betriebsmeldungen', 'keine ALERT_EMAIL gesetzt: Fehler und Cron-Ausfälle werden nicht per Mail gemeldet'];

        $rows[] = config('catalog.covers.google_books.key')
            ? ['OK', 'Google-Books-Key', 'gesetzt']
            : ['OK', 'Google-Books-Key', 'nicht gesetzt (Cover nur über Open Library)'];

        $this->table(['Ergebnis', 'Prüfung', 'Details'], $rows);

        $errors = count(array_filter($rows, static fn (array $row): bool => $row[0] === 'Fehler'));
        $warnings = count(array_filter($rows, static fn (array $row): bool => $row[0] === 'Warnung'));

        if ($errors > 0) {
            $this->error("{$errors} Fehler, {$warnings} Warnung(en).");

            return self::FAILURE;
        }

        $this->info($warnings > 0 ? "Keine Fehler, {$warnings} Warnung(en)." : 'Alles in Ordnung.');

        return self::SUCCESS;
    }

    private function pendingMigrations(): int
    {
        $migrator = app('migrator');
        $repository = $migrator->getRepository();

        if (! $repository->repositoryExists()) {
            return count($migrator->getMigrationFiles($migrator->paths())) ?: 1;
        }

        $files = $migrator->getMigrationFiles(array_merge($migrator->paths(), [database_path('migrations')]));

        return count(array_diff(array_keys($files), $repository->getRan()));
    }
}
