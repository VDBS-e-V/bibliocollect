<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Exceptions\CatalogShelfStructureConflict;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;

final readonly class DeleteCatalogShelfSectionAction
{
    public function __construct(private AuditRecorder $audit) {}

    /** @throws CatalogShelfStructureConflict */
    public function execute(CatalogShelfSection $section): void
    {
        $children = $section->children()->count();
        $shelves = CatalogShelf::query()->where('section_id', $section->getKey())->count();

        if ($children > 0 || $shelves > 0) {
            throw CatalogShelfStructureConflict::inUse($section->display(), $children, $shelves);
        }

        $this->audit->record('catalog.shelf_section.deleted', $section->kind->label().' gelöscht.', $section);
        $section->delete();
    }
}
