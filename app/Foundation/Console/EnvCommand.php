<?php

declare(strict_types=1);

namespace App\Foundation\Console;

use App\Foundation\Environment\EnvFiles;
use Illuminate\Console\Command;

/** Gemeinsame Hilfen der env:*-Befehle. Ausgegeben werden nur Schlüsselnamen und Dateinamen, nie Werte. */
abstract class EnvCommand extends Command
{
    protected function readFile(string $path): string
    {
        $content = is_file($path) ? file_get_contents($path) : '';

        return $content === false ? '' : $content;
    }

    /** @param list<string> $keys */
    protected function listKeys(string $heading, array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $this->line("  {$heading} (".count($keys).'):');

        foreach ($keys as $key) {
            $this->line("    - {$key}");
        }
    }

    protected function files(): EnvFiles
    {
        return app(EnvFiles::class);
    }
}
