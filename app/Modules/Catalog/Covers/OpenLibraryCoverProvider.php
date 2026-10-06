<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Covers;

use App\Modules\Catalog\Contracts\CatalogCoverProvider;
use App\Modules\Catalog\DTOs\CatalogCoverImage;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Kostenlose Cover-Quelle ohne Zugangsschlüssel (https://openlibrary.org/dev/docs/api/covers).
 * Die Abdeckung bei deutschsprachigen Titeln ist lückenhaft; "nicht gefunden" ist der Normalfall.
 */
final readonly class OpenLibraryCoverProvider implements CatalogCoverProvider
{
    public function __construct(
        private CatalogIsbnNormalizer $isbns,
        private CoverImageFactory $images,
    ) {}

    public function configured(): bool
    {
        return (bool) config('catalog.covers.open_library.enabled', true);
    }

    public function fetch(Edition $edition): ?CatalogCoverImage
    {
        $isbn = is_string($edition->isbn) ? $this->isbns->normalize($edition->isbn) : null;

        if ($isbn === null || ! $this->isbns->isStandardFormat($isbn)) {
            return null;
        }

        $url = 'https://covers.openlibrary.org/b/isbn/'.$isbn.'-L.jpg';

        try {
            // default=false liefert bei fehlendem Cover 404 statt eines Platzhalterbildes.
            $response = Http::timeout(max(1, (int) config('catalog.covers.open_library.timeout', 10)))
                ->withHeaders(['User-Agent' => 'BiblioCollect/1.0 (VDBS e.V. Schulbibliothek)'])
                ->get($url, ['default' => 'false']);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $this->images->fromBinary($response->body(), 'open-library', $url);
    }
}
