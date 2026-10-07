<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use InvalidArgumentException;
use RuntimeException;

final class EnvBackupCommand extends EnvCommand
{
    protected $signature = 'env:backup {--target=* : Zieldatei (mehrfach möglich), sonst die Ziele aus config/foundation.php}';

    protected $description = 'Sichert .env-Dateien mit Zeitstempel unter .foundation/env-backups/ (nie ins Repository).';

    public function handle(): int
    {
        $files = $this->files();

        try {
            /** @var list<string> $requested */
            $requested = array_values((array) $this->option('target'));
            $targets = $files->targets($requested);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $failed = false;
        $saved = 0;

        foreach ($targets as $target) {
            try {
                $name = $files->backup($target);
            } catch (InvalidArgumentException|RuntimeException $exception) {
                $this->error($exception->getMessage());
                $failed = true;

                continue;
            }

            if ($name === null) {
                $this->warn("{$target}: Die Datei gibt es nicht, übersprungen.");

                continue;
            }

            $saved++;
            $this->info("{$target} gesichert: .foundation/env-backups/{$name}");
        }

        if ($saved === 0 && ! $failed) {
            $this->warn('Es gab nichts zu sichern.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
