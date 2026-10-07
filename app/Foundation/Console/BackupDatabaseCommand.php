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
 * SQLite: konsistente Kopie der Datei. MySQL/MariaDB: eigenständige SQL-Datei (gzip) mit `DROP TABLE`, `CREATE TABLE` und den
 * Daten aller Tabellen, jede Anweisung in einer Zeile. Sie lässt sich ohne vorherige Migration wiederherstellen, mit
 * `php artisan backup:restore <Datei>` oder in phpMyAdmin über „Importieren“.
 */
final class BackupDatabaseCommand extends Command
{
    /** Tabellen, deren Inhalt nicht gesichert wird (flüchtig); die Struktur kommt trotzdem mit. */
    private const WITHOUT_DATA = ['cache', 'cache_locks', 'sessions'];

    private const ROWS_PER_READ = 500;

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
        $this->line('Bitte regelmäßig auch außerhalb des Servers ablegen, zum Beispiel über „Systemzustand“ herunterladen.');

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
        $partial = $target.'.part';
        $handle = gzopen($partial, 'wb9');

        if ($handle === false) {
            throw new RuntimeException('Die Sicherungsdatei konnte nicht angelegt werden.');
        }

        try {
            gzwrite($handle, "-- BiblioCollect Datensicherung {$stamp}\n-- Eine Anweisung je Zeile. Einspielen: php artisan backup:restore <Datei> oder phpMyAdmin > Importieren.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");

            foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_COLUMN) as $table) {
                $table = (string) $table;
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);

                gzwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                gzwrite($handle, preg_replace('/\s*\R\s*/', ' ', (string) $create[1]).";\n");

                if (! in_array($table, self::WITHOUT_DATA, true)) {
                    $this->dumpRows($pdo, $handle, $table);
                }
            }

            gzwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        } catch (\Throwable $exception) {
            gzclose($handle);
            File::delete($partial);

            throw $exception;
        }

        gzclose($handle);
        File::move($partial, $target);

        return $target;
    }

    /** @param  resource  $handle */
    private function dumpRows(PDO $pdo, $handle, string $table): void
    {
        $columns = array_map(static fn (array $column): string => (string) $column['Field'], $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC));
        $columnList = '`'.implode('`, `', $columns).'`';

        $keys = $pdo->query("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'")->fetchAll(PDO::FETCH_ASSOC);
        usort($keys, static fn (array $a, array $b): int => (int) $a['Seq_in_index'] <=> (int) $b['Seq_in_index']);
        $order = $keys === [] ? '' : ' ORDER BY '.implode(', ', array_map(static fn (array $key): string => '`'.$key['Column_name'].'`', $keys));

        // In Blöcken lesen, damit auch große Tabellen nicht ganz im Arbeitsspeicher liegen.
        for ($offset = 0; ; $offset += self::ROWS_PER_READ) {
            $rows = $pdo->query("SELECT * FROM `{$table}`{$order} LIMIT ".self::ROWS_PER_READ." OFFSET {$offset}")->fetchAll(PDO::FETCH_NUM);

            if ($rows === []) {
                return;
            }

            $values = array_map(
                static fn (array $row): string => '('.implode(',', array_map(static fn (mixed $value): string => $value === null ? 'NULL' : $pdo->quote((string) $value), $row)).')',
                $rows,
            );

            gzwrite($handle, "INSERT INTO `{$table}` ({$columnList}) VALUES ".implode(',', $values).";\n");

            if (count($rows) < self::ROWS_PER_READ) {
                return;
            }
        }
    }

    private function prune(string $directory, int $keep): void
    {
        $files = collect(File::files($directory))
            ->filter(static fn ($file): bool => str_starts_with($file->getFilename(), 'datenbank-') && ! str_ends_with($file->getFilename(), '.part'))
            ->sortByDesc(static fn ($file): string => $file->getFilename())
            ->values();

        foreach ($files->slice($keep) as $old) {
            File::delete($old->getPathname());
        }
    }
}
