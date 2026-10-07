<?php

declare(strict_types=1);

namespace App\Foundation\Environment;

use InvalidArgumentException;
use RuntimeException;

/**
 * Dateizugriff für die env:*-Befehle: sichere Pfade innerhalb des Projekts und zeitgestempelte Sicherungen.
 * Die Sicherungen liegen bewusst nicht in einem allgemeinen Verlauf, weil sie Geheimnisse enthalten.
 */
final class EnvFiles
{
    public function root(): string
    {
        return rtrim((string) config('foundation.environment.root', base_path()), '\\/');
    }

    public function templatePath(): string
    {
        return $this->resolve((string) config('foundation.environment.template', '.env.example'));
    }

    /**
     * @param  list<string>  $requested
     * @return list<string> Relative Namen, wie angegeben
     */
    public function targets(array $requested = []): array
    {
        $requested = array_values(array_filter(array_map(static fn (mixed $target): string => trim((string) $target), $requested), static fn (string $target): bool => $target !== ''));

        if ($requested !== []) {
            return array_values(array_unique($requested));
        }

        /** @var list<string> $configured */
        $configured = array_values((array) config('foundation.environment.targets', ['.env']));

        return $configured;
    }

    /** Absoluter Pfad einer Datei im Projekt. Wirft bei absoluten Pfaden, Laufwerken und „..“. */
    public function resolve(string $relative): string
    {
        $relative = trim($relative);
        $normalized = str_replace('\\', '/', $relative);

        if (
            $normalized === ''
            || str_contains($normalized, "\0")
            || str_starts_with($normalized, '/')
            || preg_match('/^[A-Za-z]:/', $normalized) === 1
            || str_starts_with($normalized, '//')
            || in_array('..', explode('/', $normalized), true)
        ) {
            throw new InvalidArgumentException("Unsicherer Pfad „{$relative}“: erlaubt sind nur Pfade innerhalb des Projekts, ohne „..“.");
        }

        return $this->root().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    }

    public function backupDirectory(): string
    {
        return $this->resolve((string) config('foundation.environment.backup_path', '.foundation/env-backups'));
    }

    /** Legt eine Sicherung an. Gibt den Dateinamen zurück oder null, wenn das Ziel nicht existiert. */
    public function backup(string $target): ?string
    {
        $source = $this->resolve($target);

        if (! is_file($source)) {
            return null;
        }

        $directory = $this->backupDirectory();

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Das Sicherungsverzeichnis konnte nicht angelegt werden.');
        }

        $prefix = $this->prefixFor($target);
        $stamp = date('Ymd-His');
        $name = "{$prefix}.{$stamp}.bak";

        for ($counter = 2; is_file($directory.DIRECTORY_SEPARATOR.$name); $counter++) {
            $name = "{$prefix}.{$stamp}-{$counter}.bak";
        }

        if (! copy($source, $directory.DIRECTORY_SEPARATOR.$name)) {
            throw new RuntimeException('Die Sicherung konnte nicht geschrieben werden.');
        }

        @chmod($directory.DIRECTORY_SEPARATOR.$name, 0600);

        return $name;
    }

    /**
     * Vorhandene Sicherungen, neueste zuerst. Mit $target nur die zu dieser Datei.
     *
     * @return list<string>
     */
    public function backups(?string $target = null): array
    {
        $directory = $this->backupDirectory();

        if (! is_dir($directory)) {
            return [];
        }

        $names = [];

        foreach (scandir($directory) ?: [] as $name) {
            if (preg_match('/^.+\.\d{8}-\d{6}(?:-\d+)?\.bak$/', $name) === 1 && is_file($directory.DIRECTORY_SEPARATOR.$name)) {
                $names[] = $name;
            }
        }

        if ($target !== null) {
            $prefix = $this->prefixFor($target).'.';
            $names = array_values(array_filter($names, static fn (string $name): bool => str_starts_with($name, $prefix) && preg_match('/^\d{8}-\d{6}(?:-\d+)?\.bak$/', substr($name, strlen($prefix))) === 1));
        }

        // Die Zeit steht im Namen; bei gleicher Sekunde entscheidet der Zähler.
        usort($names, static fn (string $a, string $b): int => strcmp(self::sortKey($b), self::sortKey($a)));

        return $names;
    }

    /** Pfad einer benannten Sicherung. Erlaubt sind nur Namen aus dem Sicherungsverzeichnis, keine Pfade. */
    public function backupPath(string $name): string
    {
        $name = trim($name);

        if ($name === '' || $name !== basename(str_replace('\\', '/', $name)) || str_contains($name, '..') || str_contains($name, "\0")) {
            throw new InvalidArgumentException('Gib nur den Dateinamen der Sicherung an, keinen Pfad.');
        }

        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$name;

        if (! is_file($path)) {
            throw new InvalidArgumentException("Die Sicherung „{$name}“ gibt es nicht.");
        }

        return $path;
    }

    private function prefixFor(string $target): string
    {
        $base = basename(str_replace('\\', '/', $target));

        return ltrim($base, '.') !== '' ? ltrim($base, '.') : 'env';
    }

    private static function sortKey(string $name): string
    {
        preg_match('/(\d{8}-\d{6})(?:-(\d+))?\.bak$/', $name, $matches);

        return ($matches[1] ?? '').'-'.str_pad($matches[2] ?? '1', 6, '0', STR_PAD_LEFT);
    }
}
