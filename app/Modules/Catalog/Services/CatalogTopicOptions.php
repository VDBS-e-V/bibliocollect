<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\CatalogTopic;

/** Auswahlliste der Themenbereiche als Baum (Unterbereiche eingerückt), ohne Regalbretter oder Signaturen. */
final class CatalogTopicOptions
{
    /** @return array<string, string> Name des Themenbereichs zu Anzeigetext */
    public function forSelect(): array
    {
        $topics = CatalogTopic::query()->orderBy('public_key')->orderBy('name')->get();
        $byParent = $topics->groupBy(static fn (CatalogTopic $topic): string => (string) ($topic->parent_id ?? ''));
        $options = [];

        $walk = function (string $parentId, int $depth) use (&$walk, $byParent, &$options): void {
            foreach ($byParent->get($parentId, collect()) as $topic) {
                $options[$topic->name] = str_repeat('– ', $depth).$topic->name;
                $walk((string) $topic->getKey(), $depth + 1);
            }
        };

        $walk('', 0);

        return $options;
    }
}
