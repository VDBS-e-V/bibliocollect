<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use App\Foundation\Environment\EnvSynchronizer;
use InvalidArgumentException;

final class EnvCheckCommand extends EnvCommand
{
    protected $signature = 'env:check';

    protected $description = 'Prüft, ob in den .env-Dateien Schlüssel aus .env.example fehlen (zeigt nie Werte).';

    public function handle(EnvSynchronizer $synchronizer): int
    {
        $files = $this->files();

        try {
            $templatePath = $files->templatePath();
            $targets = $files->targets();
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! is_file($templatePath)) {
            $this->error('Die Vorlage '.config('foundation.environment.template').' gibt es nicht.');

            return self::FAILURE;
        }

        $template = $this->readFile($templatePath);
        $failed = false;

        foreach ($targets as $target) {
            try {
                $path = $files->resolve($target);
            } catch (InvalidArgumentException $exception) {
                $this->error($exception->getMessage());
                $failed = true;

                continue;
            }

            if (! is_file($path)) {
                $this->error("{$target}: Die Datei fehlt. Mit „php artisan env:sync” wird sie angelegt.");
                $failed = true;

                continue;
            }

            $diff = $synchronizer->diff($template, $this->readFile($path));

            if ($diff['missing'] === []) {
                $this->info("{$target}: vollständig".($diff['extra'] === [] ? '.' : ', mit '.count($diff['extra']).' zusätzlichen Schlüsseln.'));
            } else {
                $this->error("{$target}: ".count($diff['missing']).' Schlüssel fehlen.');
                $failed = true;
            }

            $this->listKeys('Fehlt', $diff['missing']);
            $this->listKeys('Zusätzlich (nur Hinweis)', $diff['extra']);
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
