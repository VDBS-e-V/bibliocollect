<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\Edition;

final class CatalogClassificationService
{
    /** @return list<string> */
    public function topicNamesForEdition(Edition $edition): array
    {
        $edition->loadMissing('copies.shelf.topics');
        $names = [(string) $edition->local_classification];

        foreach ($edition->copies as $copy) {
            $shelf = $copy->shelf;

            if (! $shelf instanceof CatalogShelf) {
                continue;
            }

            foreach ($shelf->topics as $topic) {
                $names[] = $topic->name;
            }
        }

        $names = array_values(array_unique(array_filter($names, static fn (string $name): bool => trim($name) !== '')));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }
}
