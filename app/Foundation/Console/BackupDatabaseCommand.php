<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;

/**
 * Sichert die Datenbank ohne externe Programme (läuft auch auf Hosting ohne `mysqldump`).
 *
 * SQLite: konsistente Kopie der Datei. MySQL/MariaDB: SQL-Datei mit den Daten aller Tabellen als INSERT-Anweisungen
 * (gzip). Die Struktur kommt beim Wiederherstellen aus den Migrationen (`php artisan migrate`), danach wird die
 * Datendatei in phpMyAdmin oder per `mysql` eingespielt.
 */
final class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--keep=14 : Wie viele Sicherungen aufbewahrt werden}';

    protected $description = 'Sichert die Datenbank nach storage/app/backups und löscht ältere Sicherungen.';

    public function handle(): int
    {
        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $stamp = now()->format('Y-m-d_His');
        $driver = DB::connection()->getDriverName();

        $file = match ($driver) {
            'sqlite' => $this->backupSqlite($directory, $stamp),
            'mysql', 'mariadb' => $this->backupMysql($directory, $stamp),
            default => throw new RuntimeException("Für den Datenbanktreiber „{$driver}“ gibt es noch keine Sicherung."),
        };

        $this->prune($directory, max(1, (int) $this->option('keep')));

        $this->info('Sicherung geschrieben: '.$file.' ('.number_format((int) filesize($file) / 1024, 0, ',', '.').' KB)');
        $this->line('Bitte regelmäßig auch außerhalb des Servers ablegen, z. B. herunterladen.');

        return self::SUCCESS;
    }

    private function backupSqlite(string $directory, string $stamp): string
    {
        $target = $directory.DIRECTORY_SEPARATOR."datenbank-{$stamp}.sqlite";

        // VACUUM INTO erzeugt eine konsistente Kopie, auch während die Datenbank benutzt wird.
        DB::connection()->statement('VACUUM INTO '.DB::connection()->getPdo()->quote($target));

        return $target;
    }

    private function backupMysql(string $directory, string $stamp): string
    {
        $pdo = DB::connection()->getPdo();
        $target = $directory.DIRECTORY_SEPARATOR."datenbank-{$stamp}.sql.gz";
        $handle = gzopen($target, 'wb9');

        if ($handle === false) {
            throw new RuntimeException('Die Sicherungsdatei konnte nicht angelegt werden.');
        }

        gzwrite($handle, "-- BiblioCollect Datensicherung {$stamp}\n-- Zuerst die Struktur mit `php artisan migrate --force` anlegen, dann diese Datei einspielen.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            if (in_array($table, ['cache', 'cache_locks', 'sessions'], true)) {
                continue;
            }

            $columns = array_map(static fn (array $column): string => (string) $column['Field'], $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC));
            $columnList = '`'.implode('`, `', $columns).'`';
            $statement = $pdo->query("SELECT * FROM `{$table}`");
            $batch = [];

            while (($row = $statement->fetch(PDO::FETCH_NUM)) !== false) {
                $batch[] = '('.implode(', ', array_map(static fn (mixed $value): string => $value === null ? 'NULL' : $pdo->quote((string) $value), $row)).')';

                if (count($batch) >= 200) {
                    gzwrite($handle, "INSERT INTO `{$table}` ({$columnList}) VALUES\n".implode(",\n", $batch).";\n");
                    $batch = [];
                }
            }

            if ($batch !== []) {
                gzwrite($handle, "INSERT INTO `{$table}` ({$columnList}) VALUES\n".implode(",\n", $batch).";\n");
            }
        }

        gzwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        gzclose($handle);

        return $target;
    }

    private function prune(string $directory, int $keep): void
    {
        $files = collect(File::files($directory))
            ->filter(static fn ($file): bool => str_starts_with($file->getFilename(), 'datenbank-'))
            ->sortByDesc(static fn ($file): string => $file->getFilename())
            ->values();

        foreach ($files->slice($keep) as $old) {
            File::delete($old->getPathname());
        }
    }
}
