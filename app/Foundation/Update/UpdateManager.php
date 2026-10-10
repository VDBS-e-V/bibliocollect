<?php

declare(strict_types=1);

namespace App\Foundation\Update;

use FilesystemIterator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use ZipArchive;

/**
 * Updates ohne Konsole: Ein Paket (ZIP des Release-Pakets) liegt im Ordner storage/app/updates (hochgeladen oder per FTP abgelegt).
 * Beim Einspielen wird die Datenbank gesichert, die Seite in den Wartungsmodus gesetzt, das Paket entpackt und über die Anwendung
 * kopiert. Danach läuft der Abschluss in einer **neuen Anfrage** mit dem neuen Code: Migrationen, Zwischenspeicher leeren, Wartungsmodus
 * beenden. Nie angefasst werden .env, storage, Cover und Ausweis-Motive.
 */
class UpdateManager
{
    /** Gesetzt, sobald in diesem Prozess Dateien ausgetauscht wurden: Der Prozess darf danach keinen weiteren Code mehr laden. */
    public static bool $applied = false;

    private const REQUIRED = ['artisan', 'composer.json', 'bootstrap/app.php', 'vendor/autoload.php', 'public/index.php'];

    public function directory(): string
    {
        $directory = (string) (config('foundation.update.directory') ?: storage_path('app/updates'));
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    public function target(): string
    {
        return rtrim((string) (config('foundation.update.target') ?: base_path()), '/\\');
    }

    public function currentVersion(): string
    {
        $file = $this->target().'/VERSION';
        $version = is_file($file) ? trim((string) file_get_contents($file)) : '';

        return $version !== '' ? $version : 'unbekannt';
    }

    /**
     * Die bereitliegenden Pakete, das neueste zuerst.
     *
     * @return list<array{name: string, size: int, modified: int, version: ?string, newer: ?bool, error: ?string}>
     */
    public function packages(): array
    {
        $current = $this->currentVersion();
        $list = [];

        foreach (File::glob($this->directory().'/*.zip') ?: [] as $path) {
            $entry = ['name' => basename($path), 'size' => (int) filesize($path), 'modified' => (int) filemtime($path), 'version' => null, 'newer' => null, 'error' => null];

            try {
                $entry['version'] = $this->inspect($path)['version'];
                $entry['newer'] = $this->compare($entry['version'], $current);
            } catch (UpdateException $exception) {
                $entry['error'] = $exception->getMessage();
            }

            $list[] = $entry;
        }

        usort($list, fn (array $a, array $b): int => $b['modified'] <=> $a['modified']);

        return $list;
    }

    /**
     * Prüft ein Paket, ohne etwas zu entpacken.
     *
     * @return array{version: ?string, files: int, bytes: int}
     *
     * @throws UpdateException
     */
    public function inspect(string $path): array
    {
        $zip = new ZipArchive;

        if (! is_file($path) || $zip->open($path) !== true) {
            throw new UpdateException('Die Datei ist kein lesbares ZIP-Paket.');
        }

        $names = [];
        $bytes = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            // Das Windows-PowerShell legt Pfade mit Rückwärtsstrichen an („app\Http\“): Das ist gültig und wird wie „app/Http/“ behandelt.
            $name = str_replace('\\', '/', (string) ($stat['name'] ?? ''));

            if ($name === '' || str_contains($name, '..') || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:/', $name) === 1) {
                $zip->close();

                throw new UpdateException('Das Paket enthält einen unzulässigen Dateinamen („'.$name.'“) und wird nicht eingespielt.');
            }

            $names[$name] = true;
            $bytes += (int) ($stat['size'] ?? 0);
        }

        foreach (self::REQUIRED as $required) {
            if (! isset($names[$required])) {
                $zip->close();

                throw new UpdateException('Das ist kein BiblioCollect-Paket: „'.$required.'“ fehlt. Bitte das Release-Paket (build-release.ps1) hochladen.');
            }
        }

        $version = $zip->getFromName('VERSION');
        $zip->close();

        return ['version' => $version !== false && trim($version) !== '' ? trim($version) : null, 'files' => count($names), 'bytes' => $bytes];
    }

    /** Legt eine hochgeladene Datei im Update-Ordner ab, wenn sie ein gültiges Paket ist. */
    public function store(string $temporaryPath, string $originalName): string
    {
        $this->inspect($temporaryPath);

        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME)) ?: 'update';
        $name = Str::limit($name, 60, '').'.zip';
        File::move($temporaryPath, $this->directory().'/'.$name);

        return $name;
    }

    public function delete(string $name): void
    {
        File::delete($this->path($name));
    }

    public function autoEnabled(): bool
    {
        return is_file($this->directory().'/auto-update');
    }

    public function setAuto(bool $on): void
    {
        $marker = $this->directory().'/auto-update';

        if ($on) {
            File::put($marker, now()->toIso8601String());
        } else {
            File::delete($marker);
        }
    }

    /** Ob nachts nach einem neuen Release auf GitHub gesucht und es selbst geholt wird (nur zusammen mit dem automatischen Einspielen). */
    public function autoDownloadEnabled(): bool
    {
        return is_file($this->directory().'/auto-download');
    }

    public function setAutoDownload(bool $on): void
    {
        $marker = $this->directory().'/auto-download';

        if ($on) {
            File::put($marker, now()->toIso8601String());
        } else {
            File::delete($marker);
        }
    }

    /** Ob schon ein Paket dieser Version bereitliegt. */
    public function hasPackageVersion(string $version): bool
    {
        foreach ($this->packages() as $package) {
            if ($package['error'] === null && $package['version'] !== null && $this->normalize($package['version']) === $this->normalize($version)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, mixed>|null */
    public function pending(): ?array
    {
        return $this->readJson('pending.json');
    }

    /** @return array<string, mixed>|null */
    public function lastResult(): ?array
    {
        return $this->readJson('last.json');
    }

    /**
     * Ob $version neuer als $current ist; null, wenn sich das nicht beurteilen lässt.
     */
    public function compare(?string $version, string $current): ?bool
    {
        $a = $this->normalize($version);
        $b = $this->normalize($current);

        return $a === null || $b === null ? null : version_compare($a, $b, '>');
    }

    /** Das neueste Paket, das neuer als die laufende Version ist (für das nächtliche Einspielen). */
    public function newestUsable(): ?string
    {
        foreach ($this->packages() as $package) {
            if ($package['error'] === null && $package['newer'] === true) {
                return $package['name'];
            }
        }

        return null;
    }

    /**
     * Spielt ein Paket ein und lässt die Seite im Wartungsmodus zurück. Den Abschluss (Migrationen, Zwischenspeicher, Wartungsmodus
     * beenden) übernimmt eine neue Anfrage mit dem neuen Code: {@see finish()}.
     *
     * @return string Schlüssel für den Abschluss
     *
     * @throws UpdateException
     */
    public function apply(string $name, bool $allowOlder = false): string
    {
        if ($this->pending() !== null) {
            throw new UpdateException('Ein Update wartet noch auf seinen Abschluss. Bitte zuerst den Abschluss abwarten.');
        }

        $path = $this->path($name);
        $info = $this->inspect($path);
        $current = $this->currentVersion();

        if ($this->compare($info['version'], $current) === false && ! $allowOlder) {
            throw new UpdateException('Das Paket ist nicht neuer als die laufende Version ('.$current.'). Zum Einspielen einer älteren oder gleichen Version die Bestätigung setzen.');
        }

        $free = @disk_free_space($this->target());

        if ($free !== false && $free < max(200 * 1024 * 1024, $info['bytes'] * 2)) {
            throw new UpdateException('Auf dem Server ist zu wenig Speicherplatz frei für das Update.');
        }

        @set_time_limit(900);

        // 1. Sicherung der Datenbank. Gelingt sie nicht, wird nichts verändert.
        if (config('foundation.update.backup', true)) {
            try {
                Artisan::call('backup:database');
            } catch (Throwable $exception) {
                throw new UpdateException('Die Sicherung vor dem Update ist fehlgeschlagen, es wurde nichts verändert: '.$exception->getMessage());
            }
        }

        // 2. Wartungsmodus.
        Artisan::call('down', ['--retry' => 120]);

        $staging = $this->directory().'/staging-'.now()->format('YmdHis');

        try {
            // 3. Entpacken und über die Anwendung kopieren.
            $this->extract($path, $staging);

            File::deleteDirectory($this->target().'/public/build');
            self::$applied = true;
            $count = $this->copyTree($staging, $this->target());
        } catch (Throwable $exception) {
            File::deleteDirectory($staging);
            Artisan::call('up');
            $this->writeJson('last.json', ['ok' => false, 'at' => now()->toIso8601String(), 'package' => $name, 'message' => 'Das Einspielen ist abgebrochen worden: '.$exception->getMessage().' Ein Teil der Dateien kann schon ausgetauscht sein; bitte das Update erneut einspielen.']);

            throw new UpdateException('Das Einspielen ist abgebrochen worden: '.$exception->getMessage());
        }

        File::deleteDirectory($staging);

        $token = Str::lower(Str::random(40));
        $this->writeJson('pending.json', ['token' => $token, 'package' => $name, 'from' => $current, 'to' => $info['version'] ?? 'unbekannt', 'files' => $count, 'at' => now()->toIso8601String()]);

        return $token;
    }

    /**
     * Abschluss nach dem Einspielen, in einer neuen Anfrage mit dem neuen Code: Migrationen, Zwischenspeicher leeren, Wartungsmodus beenden.
     *
     * @param  ?string  $token  Schlüssel aus {@see apply()}; null beim Cron (der darf jedes wartende Update abschließen)
     * @return array<string, mixed>
     *
     * @throws UpdateException bei falschem Schlüssel oder wenn nichts wartet
     */
    public function finish(?string $token): array
    {
        $pending = $this->pending();

        if ($pending === null) {
            throw new UpdateException('Es wartet kein Update auf seinen Abschluss.');
        }

        if ($token !== null && ! hash_equals((string) ($pending['token'] ?? ''), $token)) {
            throw new UpdateException('Der Schlüssel passt nicht zum wartenden Update.');
        }

        @set_time_limit(900);

        $steps = [];

        try {
            Artisan::call('migrate', ['--force' => true]);
            $steps[] = 'Datenbank aktualisiert (Migrationen).';

            foreach (['config:clear', 'route:clear', 'view:clear', 'event:clear'] as $command) {
                Artisan::call($command);
            }

            $steps[] = 'Zwischenspeicher geleert.';

            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }

            Artisan::call('up');
            $steps[] = 'Wartungsmodus beendet.';
        } catch (Throwable $exception) {
            $result = ['ok' => false, 'at' => now()->toIso8601String(), 'package' => $pending['package'] ?? null, 'from' => $pending['from'] ?? null, 'to' => $pending['to'] ?? null, 'steps' => $steps, 'message' => 'Der Abschluss ist fehlgeschlagen: '.$exception->getMessage().' Die Seite bleibt im Wartungsmodus. Die Datenbank-Sicherung von vor dem Update liegt unter Verwaltung → Systemzustand.'];
            $this->writeJson('last.json', $result);

            throw new UpdateException($result['message']);
        }

        File::ensureDirectoryExists($this->directory().'/done');
        $package = $this->directory().'/'.($pending['package'] ?? '');

        if (is_file($package)) {
            File::move($package, $this->directory().'/done/'.basename($package));
        }

        File::delete($this->directory().'/pending.json');

        $result = ['ok' => true, 'at' => now()->toIso8601String(), 'package' => $pending['package'] ?? null, 'from' => $pending['from'] ?? null, 'to' => $pending['to'] ?? null, 'files' => $pending['files'] ?? null, 'steps' => $steps, 'message' => 'Das Update auf '.($pending['to'] ?? 'die neue Version').' ist eingespielt.'];
        $this->writeJson('last.json', $result);

        return $result;
    }

    /** Entpackt das Paket Datei für Datei; Rückwärtsstriche in Pfaden werden zu Schrägstrichen (extractTo würde sie als Teil des Dateinamens anlegen). */
    private function extract(string $path, string $to): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new UpdateException('Das Paket ist nicht lesbar.');
        }

        File::ensureDirectoryExists($to);

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            $destination = $to.'/'.$name;

            if (str_ends_with($name, '/')) {
                File::ensureDirectoryExists($destination);

                continue;
            }

            File::ensureDirectoryExists(dirname($destination));
            $stream = $zip->getStream((string) $zip->getNameIndex($i));
            $target = $stream === false ? false : fopen($destination, 'wb');

            if ($stream === false || $target === false) {
                $zip->close();

                throw new UpdateException('Die Datei „'.$name.'“ aus dem Paket konnte nicht entpackt werden.');
            }

            stream_copy_to_stream($stream, $target);
            fclose($stream);
            fclose($target);
        }

        $zip->close();
    }

    private function path(string $name): string
    {
        if (preg_match('/^[A-Za-z0-9._-]+\.zip$/', $name) !== 1 || ! is_file($this->directory().'/'.$name)) {
            throw new UpdateException('Dieses Paket gibt es nicht.');
        }

        return $this->directory().'/'.$name;
    }

    private function skipped(string $relative): bool
    {
        $relative = trim(str_replace('\\', '/', $relative), '/');

        foreach ((array) config('foundation.update.protected', ['.env', 'storage', 'public/covers', 'public/card-designs', 'public/storage', 'bootstrap/cache']) as $protected) {
            $protected = trim((string) $protected, '/');

            if ($relative === $protected || str_starts_with($relative, $protected.'/')) {
                return true;
            }
        }

        return false;
    }

    private function copyTree(string $from, string $to): int
    {
        $count = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($from) + 1));

            if ($this->skipped($relative)) {
                continue;
            }

            $destination = $to.'/'.$relative;

            if ($item->isDir()) {
                File::ensureDirectoryExists($destination);

                continue;
            }

            File::ensureDirectoryExists(dirname($destination));

            if (! @copy($item->getPathname(), $destination)) {
                throw new UpdateException('Die Datei „'.$relative.'“ konnte nicht geschrieben werden (Rechte prüfen).');
            }

            $count++;
        }

        return $count;
    }

    /** „v0.49.0“ und „v0.49.0-3-gabc123“ werden zu „0.49.0“ und „0.49.0.3“. */
    private function normalize(?string $version): ?string
    {
        if ($version === null || preg_match('/^v?(\d+)\.(\d+)\.(\d+)(?:-(\d+)-g[0-9a-f]+)?/i', trim($version), $m) !== 1) {
            return null;
        }

        return $m[1].'.'.$m[2].'.'.$m[3].'.'.($m[4] ?? '0');
    }

    /** @return array<string, mixed>|null */
    private function readJson(string $file): ?array
    {
        $path = $this->directory().'/'.$file;
        $data = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        return is_array($data) ? $data : null;
    }

    /** @param  array<string, mixed>  $data */
    private function writeJson(string $file, array $data): void
    {
        File::put($this->directory().'/'.$file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
}
