<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use InvalidArgumentException;
use RuntimeException;

final class EnvRestoreCommand extends EnvCommand
{
    use ConfirmsEnvChanges;

    protected $signature = 'env:restore
        {backup? : Dateiname der Sicherung, sonst die neueste zum Ziel}
        {--target=.env : Datei, die wiederhergestellt wird}
        {--yes : Bestätigung ohne Rückfrage (nötig ohne Terminal)}';

    protected $description = 'Stellt eine .env-Datei aus einer Sicherung unter .foundation/env-backups/ wieder her.';

    public function handle(): int
    {
        $files = $this->files();
        $target = (string) $this->option('target');

        try {
            $path = $files->resolve($target);

            if ($this->argument('backup') === null) {
                $latest = $files->backups($target)[0] ?? null;

                if ($latest === null) {
                    $this->error("Es gibt keine Sicherung für {$target}.");

                    return self::FAILURE;
                }

                $name = $latest;
            } else {
                $name = (string) $this->argument('backup');
            }

            $source = $files->backupPath($name);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line("Sicherung: {$name}");
        $this->line("Ziel: {$target}".(is_file($path) ? ' (wird überschrieben)' : ' (wird neu angelegt)'));

        if (! $this->confirmed("{$target} aus dieser Sicherung wiederherstellen?")) {
            $this->warn('Abgebrochen. Es wurde nichts geändert.');

            return self::FAILURE;
        }

        try {
            // Den bisherigen Stand sichern, damit auch ein Restore rückgängig zu machen ist.
            $before = $files->backup($target);

            if ($before !== null) {
                $this->line("  Bisheriger Stand gesichert: .foundation/env-backups/{$before}");
            }

            if (! copy($source, $path)) {
                throw new RuntimeException("{$target} konnte nicht geschrieben werden.");
            }
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("{$target} ist wiederhergestellt.");

        return self::SUCCESS;
    }
}
