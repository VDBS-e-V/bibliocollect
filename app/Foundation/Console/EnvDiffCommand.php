<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use App\Foundation\Environment\EnvSynchronizer;
use InvalidArgumentException;

final class EnvDiffCommand extends EnvCommand
{
    protected $signature = 'env:diff {target=.env : Zieldatei}';

    protected $description = 'Vergleicht .env.example strukturell mit einer .env-Datei (nur Schlüsselnamen).';

    public function handle(EnvSynchronizer $synchronizer): int
    {
        $files = $this->files();
        $target = (string) $this->argument('target');

        try {
            $templatePath = $files->templatePath();
            $path = $files->resolve($target);
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! is_file($templatePath)) {
            $this->error('Die Vorlage '.config('foundation.environment.template').' gibt es nicht.');

            return self::FAILURE;
        }

        if (! is_file($path)) {
            $this->error("{$target}: Die Datei gibt es nicht.");

            return self::FAILURE;
        }

        $diff = $synchronizer->diff($this->readFile($templatePath), $this->readFile($path));

        if ($diff['missing'] === [] && $diff['extra'] === []) {
            $this->info("{$target} hat genau die Schlüssel der Vorlage.");

            return self::SUCCESS;
        }

        $this->line($target);
        $this->listKeys('Missing', $diff['missing']);
        $this->listKeys('Extra', $diff['extra']);

        return $diff['missing'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
