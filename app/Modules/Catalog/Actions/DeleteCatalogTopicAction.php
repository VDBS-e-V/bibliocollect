<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Exceptions\CatalogTaxonomyInUse;
use App\Modules\Catalog\Models\CatalogTopic;

final readonly class DeleteCatalogTopicAction
{
    public function __construct(private AuditRecorder $audit) {}

    /** @throws CatalogTaxonomyInUse */
    public function execute(CatalogTopic $topic): void
    {
        $children = $topic->children()->count();
        $shelves = $topic->shelves()->count();

        if ($children > 0 || $shelves > 0) {
            throw CatalogTaxonomyInUse::topic($topic->name, $children, $shelves);
        }

        $this->audit->record('catalog.topic.deleted', 'Themenbereich gelöscht.', $topic);
        $topic->delete();
    }
}
