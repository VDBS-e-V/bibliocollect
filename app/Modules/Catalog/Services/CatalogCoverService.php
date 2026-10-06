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

        return $disk->exists($path) ? $disk->url($path) : null;
    }

    private function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('catalog.covers.disk', 'public'));

        return $disk;
    }
}
