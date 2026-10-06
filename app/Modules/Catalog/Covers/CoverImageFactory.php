<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Covers;

use App\Modules\Catalog\DTOs\CatalogCoverImage;

/**
 * Baut aus einem heruntergeladenen Bild einen {@see CatalogCoverImage}. Der Typ wird am Inhalt
 * erkannt, nicht am Header der Gegenseite, und nur JPEG/PNG/WebP werden akzeptiert.
 */
final class CoverImageFactory
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function fromBinary(string $contents, string $source, ?string $sourceReference): ?CatalogCoverImage
    {
        if ($contents === '') {
            return null;
        }

        $info = @getimagesizefromstring($contents);

        if (! is_array($info) || ! in_array($info['mime'], self::ALLOWED_MIME_TYPES, true)) {
            return null;
        }

        return new CatalogCoverImage(
            contents: $contents,
            mimeType: $info['mime'],
            source: $source,
            sourceReference: $sourceReference,
        );
    }
}
