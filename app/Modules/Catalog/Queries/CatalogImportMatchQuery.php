<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Contributor;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\Title;
use App\Modules\Catalog\Models\TitleContribution;
use App\Modules\Catalog\Services\CatalogIsbnNormalizer;
use Illuminate\Database\Eloquent\Collection;

final class CatalogImportMatchQuery
{
    public function __construct(private readonly CatalogIsbnNormalizer $isbnNormalizer) {}

    /**
     * @param  list<string>  $isbns
     * @return array<string, list<Edition>>
     */
    public function editionsByIsbns(array $isbns): array
    {
        $normalizedIsbns = array_values(array_unique($isbns));
        $matches = [];

        foreach ($normalizedIsbns as $isbn) {
            $matches[$isbn] = [];
        }

        if ($matches === []) {
            return $matches;
        }

        /** @var Collection<int, Edition> $editions */
        $editions = Edition::query()
            ->with('title')
            ->whereNotNull('isbn')
            ->get();

        foreach ($editions as $edition) {
            $rawIsbn = $edition->isbn;

            if (! is_string($rawIsbn) || $rawIsbn === '') {
                continue;
            }

            $normalized = $this->isbnNormalizer->normalize($rawIsbn);

            if (array_key_exists($normalized, $matches)) {
                $matches[$normalized][] = $edition;
            }
        }

        return $matches;
    }

    /** @return Collection<int, Title> */
    public function titlesByPreferredTitle(string $preferredTitle): Collection
    {
        return Title::query()
            ->where('preferred_title', $preferredTitle)
            ->get();
    }

    /** @return Collection<int, Contributor> */
    public function contributorsByExactName(string $displayName, ?string $sortName): Collection
    {
        $query = Contributor::query()->where('display_name', $displayName);

        if ($sortName !== null) {
            $query->where('sort_name', $sortName);
        }

        return $query->get();
    }

    public function contributionExists(string $titleId, string $contributorId, string $roleKey): bool
    {
        return TitleContribution::query()
            ->where('title_id', $titleId)
            ->where('contributor_id', $contributorId)
            ->where('role_key', $roleKey)
            ->exists();
    }

    /**
     * @param  list<string>  $barcodes
     * @return list<string>
     */
    public function existingBarcodes(array $barcodes): array
    {
        if ($barcodes === []) {
            return [];
        }

        return Copy::query()
            ->whereIn('barcode', array_values(array_unique($barcodes)))
            ->pluck('barcode')
            ->map(static fn (mixed $barcode): string => (string) $barcode)
            ->values()
            ->all();
    }
}
