<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\Models\Edition;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

final readonly class RefreshEditionCoverAction
{
    public function __construct(private CatalogCoverProvider $provider) {}

    public function execute(Edition $edition): bool
    {
        if (! $this->provider->configured()) {
            return false;
        }

        $checkedAt = now();
        $image = $this->provider->fetch($edition);

        if ($image === null) {
            $edition->forceFill([
                'cover_status' => 'missing',
                'cover_checked_at' => $checkedAt,
            ])->save();

            return false;
        }

        $maxBytes = max(1, (int) config('catalog.covers.max_bytes', 8 * 1024 * 1024));

        if ($image->contents === '' || strlen($image->contents) > $maxBytes) {
            throw new InvalidArgumentException('Cover-Datei ist leer oder überschreitet das Größenlimit.');
        }

        $imageInfo = @getimagesizefromstring($image->contents);
        $detectedMimeType = is_array($imageInfo) ? $imageInfo['mime'] : null;
        $declaredMimeType = mb_strtolower(trim($image->mimeType));

        if (! is_string($detectedMimeType) || $detectedMimeType !== $declaredMimeType) {
            throw new InvalidArgumentException('Cover-Datei und gemeldeter MIME-Typ passen nicht zusammen.');
        }

        $extension = match ($detectedMimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new InvalidArgumentException('Cover-Dateityp wird nicht unterstützt.'),
        };

        $directory = trim((string) config('catalog.covers.directory', 'catalog/covers'), '/');
        $path = $directory.'/'.(string) $edition->getKey().'.'.$extension;
        $disk = $this->disk();

        if (! $disk->put($path, $image->contents)) {
            throw new InvalidArgumentException('Cover-Datei konnte nicht lokal gespeichert werden.');
        }

        $oldPath = $edition->getAttribute('cover_path');

        $edition->forceFill([
            'cover_path' => $path,
            'cover_source' => trim($image->source) !== '' ? trim($image->source) : null,
            'cover_source_reference' => $image->sourceReference,
            'cover_status' => 'ready',
            'cover_checked_at' => $checkedAt,
            'cover_fetched_at' => $checkedAt,
        ])->save();

        if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $path && $disk->exists($oldPath)) {
            $disk->delete($oldPath);
        }

        return true;
    }

    private function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk((string) config('catalog.covers.disk', 'public'));

        return $disk;
    }
}
