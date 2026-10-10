<?php

declare(strict_types=1);

namespace App\Foundation\Update;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Holt das neueste veröffentlichte Update-Paket von GitHub (Server zu Server, ohne Upload im Browser).
 *
 * Nur das feste Repository aus `foundation.update.release.repository`, nur HTTPS, nur stabile Releases. Das Paket muss `bibliocollect-<tag>.zip`
 * heißen und eine Prüfsumme `bibliocollect-<tag>.zip.sha256` neben sich haben; ohne passende Prüfsumme wird nichts übernommen.
 */
class ReleaseSource
{
    /**
     * Das neueste stabile Release.
     *
     * @return array{tag: string, version: string, name: string, notes: string, published: string|null, zip_url: string, zip_size: int, sha_url: string}
     *
     * @throws UpdateException
     */
    public function latest(): array
    {
        $repository = $this->repository();

        try {
            $response = Http::timeout(10)->acceptJson()
                ->withHeaders(['User-Agent' => 'BiblioCollect-Update', 'X-GitHub-Api-Version' => '2022-11-28'])
                ->get('https://api.github.com/repos/'.$repository.'/releases/latest');
        } catch (ConnectionException) {
            throw new UpdateException('GitHub ist von diesem Server aus nicht erreichbar. Bitte später erneut versuchen oder das Paket hochladen.');
        }

        if ($response->status() === 404) {
            throw new UpdateException('Es gibt noch kein veröffentlichtes Release.');
        }

        if (in_array($response->status(), [403, 429], true)) {
            $remaining = $response->header('X-RateLimit-Remaining');
            $retry = $response->header('Retry-After');
            $reset = $response->header('X-RateLimit-Reset');
            $message = strtolower((string) $response->json('message', ''));
            $limited = $response->status() === 429
                || $remaining === '0'
                || str_contains($message, 'rate limit')
                || str_contains($message, 'abuse detection');

            if (! $limited) {
                throw new UpdateException('GitHub verweigert die Release-Abfrage (HTTP 403). Bitte Repository-Zugriff und Serververbindung prüfen. Alternativ das Release-Paket manuell hochladen.');
            }

            $waitUntil = null;

            if (is_string($retry) && ctype_digit($retry)) {
                $waitUntil = now()->addSeconds((int) $retry);
            } elseif (is_string($reset) && ctype_digit($reset)) {
                $waitUntil = \Carbon\CarbonImmutable::createFromTimestamp((int) $reset, 'UTC');
            }

            $when = $waitUntil !== null && $waitUntil->isFuture()
                ? ' Nächster Versuch frühestens am '.$waitUntil->timezone('Europe/Berlin')->format('d.m.Y H:i').' Uhr.'
                : ' Bitte später erneut versuchen.';

            throw new UpdateException('GitHub begrenzt gerade die Abfragen von diesem Server.'.$when.' Alternativ das Release-Paket manuell hochladen.');
        }

        if (! $response->successful()) {
            throw new UpdateException('GitHub hat die Abfrage mit dem Status '.$response->status().' beantwortet. Bitte später erneut versuchen.');
        }

        $data = (array) $response->json();
        $tag = (string) ($data['tag_name'] ?? '');

        if (preg_match('/^v\d+\.\d+\.\d+$/', $tag) !== 1 || ($data['draft'] ?? false) || ($data['prerelease'] ?? false)) {
            throw new UpdateException('Das neueste Release ist kein stabiles Release mit einer Versionsnummer wie v0.70.0 und wird nicht angeboten.');
        }

        $zipName = 'bibliocollect-'.$tag.'.zip';
        $prefix = 'https://github.com/'.$repository.'/releases/download/'.$tag.'/';
        $zip = null;
        $sha = null;

        foreach ((array) ($data['assets'] ?? []) as $asset) {
            $name = (string) ($asset['name'] ?? '');
            $url = (string) ($asset['browser_download_url'] ?? '');

            if (! str_starts_with($url, $prefix)) {
                continue;
            }

            if ($name === $zipName) {
                $zip = ['url' => $url, 'size' => (int) ($asset['size'] ?? 0)];
            } elseif ($name === $zipName.'.sha256') {
                $sha = $url;
            }
        }

        if ($zip === null) {
            throw new UpdateException('Zum Release '.$tag.' gehört kein Paket „'.$zipName.'“.');
        }

        if ($sha === null) {
            throw new UpdateException('Zum Release '.$tag.' fehlt die Prüfsumme „'.$zipName.'.sha256“. Ohne sie wird das Paket nicht geholt.');
        }

        if ($zip['size'] <= 0 || $zip['size'] > $this->maxBytes()) {
            throw new UpdateException('Das Paket hat eine unglaubwürdige Größe ('.$zip['size'].' Bytes) und wird nicht geholt.');
        }

        return [
            'tag' => $tag,
            'version' => $tag,
            'name' => (string) ($data['name'] ?? $tag) !== '' ? (string) ($data['name'] ?? $tag) : $tag,
            'notes' => mb_substr(trim((string) ($data['body'] ?? '')), 0, 3000),
            'published' => is_string($data['published_at'] ?? null) ? $data['published_at'] : null,
            'zip_url' => $zip['url'],
            'zip_size' => $zip['size'],
            'sha_url' => $sha,
        ];
    }

    /**
     * Lädt das Paket in eine temporäre Datei und prüft Größe und SHA-256. Gibt den Pfad zurück; die Datei räumt der Aufrufer auf.
     *
     * @param  array{tag: string, version: string, name: string, notes: string, published: string|null, zip_url: string, zip_size: int, sha_url: string}  $release
     *
     * @throws UpdateException
     */
    public function download(array $release): string
    {
        $expected = $this->expectedChecksum($release);
        $path = (string) tempnam(sys_get_temp_dir(), 'bcrelease');

        try {
            $response = Http::timeout(240)
                ->withHeaders(['User-Agent' => 'BiblioCollect-Update'])
                ->withOptions(['sink' => $path, 'allow_redirects' => ['max' => 5, 'protocols' => ['https']]])
                ->get($release['zip_url']);

            if (! $response->successful()) {
                throw new UpdateException('Das Paket konnte nicht geladen werden (Status '.$response->status().').');
            }

            $size = (int) filesize($path);

            if ($size !== $release['zip_size'] || $size > $this->maxBytes()) {
                throw new UpdateException('Das geladene Paket hat nicht die angekündigte Größe (erwartet '.$release['zip_size'].', erhalten '.$size.' Bytes). Es wird verworfen.');
            }

            if (! hash_equals($expected, strtolower((string) hash_file('sha256', $path)))) {
                throw new UpdateException('Die Prüfsumme des geladenen Pakets stimmt nicht. Es wird verworfen und nicht eingespielt.');
            }
        } catch (ConnectionException) {
            @unlink($path);

            throw new UpdateException('Das Paket konnte nicht geladen werden: Die Verbindung zu GitHub ist abgebrochen.');
        } catch (Throwable $exception) {
            @unlink($path);

            throw $exception instanceof UpdateException ? $exception : new UpdateException('Das Paket konnte nicht geladen werden: '.$exception->getMessage());
        }

        return $path;
    }

    public function repository(): string
    {
        return (string) config('foundation.update.release.repository', 'VDBS-e-V/bibliocollect');
    }

    private function maxBytes(): int
    {
        return max(1_000_000, (int) config('foundation.update.release.max_bytes', 100 * 1024 * 1024));
    }

    /**
     * @param  array{sha_url: string, tag: string}  $release
     *
     * @throws UpdateException
     */
    private function expectedChecksum(array $release): string
    {
        try {
            $response = Http::timeout(15)->withHeaders(['User-Agent' => 'BiblioCollect-Update'])
                ->withOptions(['allow_redirects' => ['max' => 5, 'protocols' => ['https']]])
                ->get($release['sha_url']);
        } catch (ConnectionException) {
            throw new UpdateException('Die Prüfsumme konnte nicht geladen werden: GitHub ist nicht erreichbar.');
        }

        if (! $response->successful() || preg_match('/^\s*([a-f0-9]{64})\b/i', substr($response->body(), 0, 400), $match) !== 1) {
            throw new UpdateException('Die Prüfsumme des Releases '.$release['tag'].' ist nicht lesbar. Das Paket wird nicht geholt.');
        }

        return strtolower($match[1]);
    }
}
