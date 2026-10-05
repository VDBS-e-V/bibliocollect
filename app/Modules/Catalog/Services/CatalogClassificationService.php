<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Edition;

final class CatalogClassificationService
{
    /** @return list<string> */
    public function topicNamesForEdition(Edition $edition): array
    {
        $edition->loadMissing('copies.signature.topics');
        $names = [];

        foreach ($edition->copies as $copy) {
            if ($copy->signature === null) {
                continue;
            }

            foreach ($copy->signature->topics as $topic) {
                $names[] = $topic->name;
            }
        }

        $names = array_values(array_unique(array_filter($names, static fn (string $name): bool => trim($name) !== '')));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }
}
