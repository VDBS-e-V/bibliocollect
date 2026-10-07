<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Spielt eine Sicherung von `backup:database` zurück. Das ersetzt alle Daten der Datenbank. Ohne SSH geht dasselbe in
 * phpMyAdmin über „Importieren“ (die .sql.gz-Datei direkt auswählen).
 */
final class RestoreDatabaseCommand extends Command
{
    protected $signature = 'backup:restore
        {file : Sicherungsdatei (Pfad oder Name aus storage/app/backups)}
        {--database= : Datenbankverbindung (Standard: die aktuelle)}
        {--yes : Ohne Rückfrage ausführen (nötig ohne Terminal)}';

    protected $description = 'Spielt eine Datenbanksicherung zurück und ersetzt dabei alle vorhandenen Daten.';

    public function handle(): int
    {
        $path = $this->resolve((string) $this->argument('file'));

        if ($path === null) {
            $this->error('Die Sicherungsdatei gibt es nicht. Gib den Pfad oder den Namen aus storage/app/backups an.');

            return self::FAILURE;
        }

        $connectionName = $this->option('database') !== null ? (string) $this->option('database') : null;
        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();

        $this->warn('Alle Daten in der Datenbank „'.$connection->getDatabaseName().'“ werden durch die Sicherung ersetzt.');

        if (! $this->option('yes')) {
            if (! $this->input->isInteractive()) {
                $this->error('Ohne Rückfrage-Möglichkeit bitte --yes angeben.');

                return self::FAILURE;
            }

            if (! $this->confirm('Wirklich wiederherstellen?', false)) {
                $this->warn('Abgebrochen. Es wurde nichts geändert.');

                return self::FAILURE;
            }
        }

        try {
            $count = match ($driver) {
                'sqlite' => $this->restoreSqlite($path, $connection->getDatabaseName()),
                'mysql', 'mariadb' => $this->restoreMysql($path, $connection->getPdo()),
                default => throw new RuntimeException("Für den Datenbanktreiber „{$driver}“ gibt es noch keine Wiederherstellung."),
            };
        } catch (Throwable $exception) {
            $this->error('Die Wiederherstellung ist fehlgeschlagen: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info($driver === 'sqlite' ? 'Die Datenbankdatei wurde ersetzt.' : "Wiederhergestellt: {$count} Anweisungen ausgeführt.");

        return self::SUCCESS;
    }

    private function resolve(string $file): ?string
    {
        foreach ([$file, storage_path('app/backups/'.basename($file))] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function restoreSqlite(string $path, string $database): int
    {
        if ($database === ':memory:' || ! str_ends_with($path, '.sqlite')) {
            throw new RuntimeException('Für SQLite wird eine .sqlite-Sicherung und eine Datenbank-Datei benötigt.');
        }

        DB::disconnect();

        if (! copy($path, $database)) {
            throw new RuntimeException('Die Datenbankdatei konnte nicht ersetzt werden.');
        }

        return 1;
    }

    private function restoreMysql(string $path, \PDO $pdo): int
    {
        if (! str_ends_with($path, '.sql.gz') && ! str_ends_with($path, '.sql')) {
            throw new RuntimeException('Für MySQL wird eine .sql.gz- oder .sql-Sicherung benötigt.');
        }

        $handle = str_ends_with($path, '.gz') ? gzopen($path, 'rb') : fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Die Sicherungsdatei lässt sich nicht lesen.');
        }

        $executed = 0;

        try {
            // Jede Anweisung steht in einer Zeile (siehe backup:database).
            while (($line = str_ends_with($path, '.gz') ? gzgets($handle) : fgets($handle)) !== false) {
                $statement = trim($line);

                if ($statement === '' || str_starts_with($statement, '--')) {
                    continue;
                }

                $pdo->exec($statement);
                $executed++;
            }
        } finally {
            str_ends_with($path, '.gz') ? gzclose($handle) : fclose($handle);
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        }

        return $executed;
    }
}
