<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use App\Foundation\Environment\EnvSynchronizer;
use InvalidArgumentException;
use RuntimeException;

final class EnvSyncCommand extends EnvCommand
{
    use ConfirmsEnvChanges;

    protected $signature = 'env:sync
        {--target=* : Zieldatei (mehrfach möglich), sonst die Ziele aus config/foundation.php}
        {--force : Bestehende Werte durch die Werte aus der Vorlage ersetzen (destruktiv)}
        {--prune : Schlüssel entfernen, die nicht in der Vorlage stehen (destruktiv)}
        {--backup : Vor Änderungen eine Sicherung anlegen}
        {--yes : Bestätigung ohne Rückfrage (nötig bei --force/--prune ohne Terminal)}
        {--dry-run : Nur anzeigen, was sich ändern würde}';

    protected $description = 'Gleicht .env-Dateien mit .env.example ab: ergänzt neue Schlüssel, behält vorhandene Werte.';

    public function handle(EnvSynchronizer $synchronizer): int
    {
        $files = $this->files();
        $force = (bool) $this->option('force');
        $prune = (bool) $this->option('prune');
        $dryRun = (bool) $this->option('dry-run');

        try {
            $templatePath = $files->templatePath();
            $targets = $files->targets($this->targetOptions());
            $paths = [];

            foreach ($targets as $target) {
                $paths[$target] = $files->resolve($target);
            }
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! is_file($templatePath)) {
            $this->error('Die Vorlage '.config('foundation.environment.template').' gibt es nicht.');

            return self::FAILURE;
        }

        $template = $this->readFile($templatePath);
        $plans = [];

        foreach ($paths as $target => $path) {
            $plans[$target] = $synchronizer->synchronize($template, $this->readFile($path), $force, $prune) + ['exists' => is_file($path)];
        }

        foreach ($plans as $target => $plan) {
            $this->line(($dryRun ? '[Probelauf] ' : '').$target.($plan['exists'] ? '' : ' (wird neu angelegt)'));
            $this->listKeys('Ergänzt', $plan['missing']);
            $this->listKeys('Ersetzt', $plan['changed']);
            $this->listKeys($prune ? 'Entfernt' : 'Zusätzlich (bleibt erhalten)', $plan['extra']);

            if ($plan['missing'] === [] && $plan['changed'] === [] && ($plan['extra'] === [] || ! $prune)) {
                $this->line('  Keine Änderung nötig.');
            }
        }

        if ($dryRun) {
            $this->info('Probelauf: Es wurde nichts geschrieben.');

            return self::SUCCESS;
        }

        if (($force || $prune) && ! $this->confirmed('Das kann vorhandene Werte oder Schlüssel dauerhaft verändern. Fortfahren?')) {
            $this->warn('Abgebrochen. Es wurde nichts geändert.');

            return self::FAILURE;
        }

        // --force und --prune sichern immer, --backup auch bei einem normalen Abgleich.
        $backup = (bool) $this->option('backup') || $force || $prune;

        try {
            foreach ($plans as $target => $plan) {
                $path = $paths[$target];
                $current = $plan['exists'] ? $this->readFile($path) : null;

                if ($current !== null && $current === $plan['content']) {
                    continue;
                }

                if ($backup && $plan['exists']) {
                    $name = $files->backup($target);
                    $this->line("  Sicherung von {$target}: .foundation/env-backups/{$name}");
                }

                if (file_put_contents($path, $plan['content'], LOCK_EX) === false) {
                    throw new RuntimeException("{$target} konnte nicht geschrieben werden.");
                }

                $this->info("{$target} ist abgeglichen.");
            }
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function targetOptions(): array
    {
        /** @var list<string> $targets */
        $targets = array_values((array) $this->option('target'));

        return $targets;
    }
}
