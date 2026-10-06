<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

final class CatalogCoverService
{
    public function localUrlForTitle(Title $title): ?string
    {
        $title->loadMissing('editions');

        foreach ($title->editions->sortByDesc(static fn (Edition $edition): int => $edition->publication_year ?? 0) as $edition) {
            $url = $this->localUrlForEdition($edition);

            if ($url !== null) {
                return $url;
            }
        }

        return null;
    }

    public function localUrlForEdition(Edition $edition): ?string
    {
        $path = $edition->getAttribute('cover_path');

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $disk = $this->disk();

        return $disk->exists($path) ? $this->urlFor($path) : null;
    }

    /**
     * Lokale Disks liefern ihre URL sonst fest aus APP_URL. Weicht die Adresse im Browser davon ab
     * (anderer Port, `artisan serve`, 127.0.0.1 statt localhost, Unterverzeichnis), zeigt das Bild ins Leere,
     * während Platzhalter und Assets über die aktuelle Adresse noch funktionieren. Deshalb wird nur der
     * Pfadteil der Disk-URL verwendet und gegen die aktuelle Anfrage aufgelöst.
     */
    private function urlFor(string $path): string
    {
        $config = config('filesystems.disks.'.(string) config('catalog.covers.disk', 'public'));
        $base = is_array($config) && ($config['driver'] ?? null) === 'local' && is_string($config['url'] ?? null)
            ? parse_url($config['url'], PHP_URL_PATH)
            : null;

        if (is_string($base) && $base !== '') {
            return url(rtrim($base, '/').'/'.ltrim($path, '/'));
        }

        return $this->disk()->url($path);
    }

    private function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('catalog.covers.disk', 'public'));

        return $disk;
    }
}
