<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Exceptions\CatalogShelfStructureConflict;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Models\Copy;
use Illuminate\Support\Facades\DB;

final readonly class DeleteCatalogShelfSectionAction
{
    public function __construct(private AuditRecorder $audit) {}

    /**
     * Löscht eine Bereichsgruppe, einen Bereich oder ein Regal. Ist noch etwas darunter, geht es nur mit `$cascade`: Dann werden
     * untergeordnete Einträge und Regalbretter mitgelöscht. Exemplare auf diesen Regalbrettern verlieren dabei ihren Standort
     * (`$releaseCopies`); ohne diese Zustimmung bleibt es bei der Ablehnung.
     *
     * @return array{sections: int, shelves: int, copies: int}
     *
     * @throws CatalogShelfStructureConflict
     */
    public function execute(CatalogShelfSection $section, bool $cascade = false, bool $releaseCopies = false): array
    {
        return DB::transaction(function () use ($section, $cascade, $releaseCopies): array {
            $sectionIds = [(string) $section->getKey()];
            $level = $sectionIds;

            for ($depth = 0; $depth < 4 && $level !== []; $depth++) {
                $level = CatalogShelfSection::query()->whereIn('parent_id', $level)->pluck('id')->map(static fn ($id): string => (string) $id)->all();
                $sectionIds = array_merge($sectionIds, $level);
            }

            $children = count($sectionIds) - 1;
            $shelves = CatalogShelf::query()->whereIn('section_id', $sectionIds)->get();

            if (($children > 0 || $shelves->isNotEmpty()) && ! $cascade) {
                throw CatalogShelfStructureConflict::inUse($section->display(), $children, $shelves->count());
            }

            $codes = $shelves->pluck('code')->all();
            $copies = $codes === [] ? 0 : Copy::query()->whereIn('shelf_location', $codes)->count();

            if ($copies > 0 && ! $releaseCopies) {
                throw CatalogShelfStructureConflict::inUse($section->display(), $children, $shelves->count());
            }

            if ($copies > 0) {
                Copy::query()->whereIn('shelf_location', $codes)->update(['shelf_location' => null]);
            }

            $this->audit->record('catalog.shelf_section.deleted', $section->kind->label().' gelöscht.', $section, [
                'code' => $section->code,
                'sections' => count($sectionIds),
                'shelves' => $shelves->count(),
                'copies_released' => $copies,
            ]);

            foreach ($shelves as $shelf) {
                $shelf->delete();
            }

            // Tiefste Ebene zuerst (Regale, Bereiche, Gruppe), damit die Fremdschlüssel nicht greifen.
            foreach (array_reverse($sectionIds) as $id) {
                CatalogShelfSection::query()->whereKey($id)->delete();
            }

            return ['sections' => count($sectionIds), 'shelves' => $shelves->count(), 'copies' => $copies];
        });
    }
}
