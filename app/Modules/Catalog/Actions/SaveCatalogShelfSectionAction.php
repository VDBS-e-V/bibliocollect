<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Enums\ShelfSectionKind;
use App\Modules\Catalog\Exceptions\CatalogShelfStructureConflict;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;
use App\Modules\Catalog\Models\Copy;
use App\Modules\Catalog\Services\CatalogShelfStructure;
use Illuminate\Support\Facades\DB;

/**
 * Legt eine Bereichsgruppe, einen Bereich oder ein Regal an oder ändert es. Ändert sich der Code, ziehen die Standorte der darunter
 * liegenden Regalbretter und ihrer Exemplare mit.
 */
final readonly class SaveCatalogShelfSectionAction
{
    public function __construct(private AuditRecorder $audit, private CatalogShelfStructure $structure) {}

    /** @throws CatalogShelfStructureConflict */
    public function execute(?CatalogShelfSection $section, ShelfSectionKind $kind, ?string $parentId, string $code, ?string $name, ?string $description, int $sortOrder): CatalogShelfSection
    {
        return DB::transaction(function () use ($section, $kind, $parentId, $code, $name, $description, $sortOrder): CatalogShelfSection {
            $code = trim($code);
            $parentKind = $kind->parentKind();
            $parent = $parentKind !== null && $parentId !== null ? CatalogShelfSection::query()->find($parentId) : null;

            if ($parentKind !== null && ($parent === null || $parent->kind !== $parentKind)) {
                throw CatalogShelfStructureConflict::notInLevel($parentKind->label());
            }

            $duplicate = CatalogShelfSection::query()
                ->where('kind', $kind->value)
                ->where('parent_id', $parent?->getKey())
                ->whereRaw('lower(code) = ?', [mb_strtolower($code)])
                ->when($section !== null, static fn ($query) => $query->whereKeyNot($section?->getKey()))
                ->exists();

            if ($duplicate) {
                throw CatalogShelfStructureConflict::duplicate($kind->label().' '.$code);
            }

            $values = [
                'parent_id' => $parent?->getKey(),
                'kind' => $kind,
                'code' => $code,
                'name' => $name !== null && trim($name) !== '' ? trim($name) : null,
                'description' => $description !== null && trim($description) !== '' ? trim($description) : null,
                'sort_order' => $sortOrder,
            ];

            if ($section === null) {
                $section = CatalogShelfSection::query()->create($values);
                $this->audit->record('catalog.shelf_section.created', $kind->label().' angelegt.', $section);

                return $section;
            }

            $codeChanged = $section->code !== $code;
            $section->forceFill($values)->save();

            if ($codeChanged) {
                $this->renumber($section);
            }

            $this->audit->record('catalog.shelf_section.updated', $kind->label().' geändert.', $section, ['renamed' => $codeChanged]);

            return $section;
        });
    }

    /** Standort-Codes aller Regalbretter unterhalb von $section neu zusammensetzen. */
    private function renumber(CatalogShelfSection $section): void
    {
        $rackIds = $this->rackIds($section);

        foreach (CatalogShelf::query()->whereIn('section_id', $rackIds)->whereNotNull('board')->get() as $shelf) {
            $rack = CatalogShelfSection::query()->with('parent.parent')->find($shelf->section_id);

            if ($rack === null) {
                continue;
            }

            $new = $this->structure->codeFor($rack, (string) $shelf->board);

            if ($new === $shelf->code) {
                continue;
            }

            if (CatalogShelf::query()->where('code', $new)->whereKeyNot($shelf->getKey())->exists()) {
                throw CatalogShelfStructureConflict::codeTaken($new);
            }

            Copy::query()->where('shelf_location', $shelf->code)->update(['shelf_location' => $new]);
            $shelf->forceFill(['code' => $new])->save();
        }
    }

    /** @return list<string> */
    private function rackIds(CatalogShelfSection $section): array
    {
        if ($section->kind === ShelfSectionKind::Rack) {
            return [(string) $section->getKey()];
        }

        $areaIds = $section->kind === ShelfSectionKind::Area
            ? [(string) $section->getKey()]
            : array_map('strval', CatalogShelfSection::query()->where('parent_id', $section->getKey())->pluck('id')->all());

        return array_map('strval', CatalogShelfSection::query()->whereIn('parent_id', $areaIds)->where('kind', ShelfSectionKind::Rack->value)->pluck('id')->all());
    }
}
