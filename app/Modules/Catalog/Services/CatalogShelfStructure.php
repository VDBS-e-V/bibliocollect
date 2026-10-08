<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Enums\ShelfSectionKind;
use App\Modules\Catalog\Models\CatalogShelf;
use App\Modules\Catalog\Models\CatalogShelfSection;

/**
 * Standortstruktur: Bereichsgruppe › Bereich › Regal › Regalbrett. Setzt Standort-Codes zusammen („I. A 1 a“), zerlegt alte Codes
 * und ordnet Regalbretter ihrem Regal zu.
 */
final class CatalogShelfStructure
{
    private const PATTERN = '/^\s*([IVXLCDM]+|\d+)\.?\s+([A-Za-z]+)\s+(\d+)\s*([A-Za-z])\s*$/i';

    /** Der Standort-Code eines Regalbretts im Regal. Beispiel: Gruppe „I“, Bereich „A“, Regal „1“, Brett „a“ ergibt „I. A 1 a“. */
    public function codeFor(CatalogShelfSection $rack, string $board): string
    {
        $area = $rack->parent;
        $group = $area?->parent;

        $groupCode = $group !== null ? rtrim(trim($group->code), '.').'.' : '';
        $parts = array_filter([$groupCode, $area?->code, $rack->code, trim($board)], static fn (?string $part): bool => $part !== null && $part !== '');

        return mb_substr(implode(' ', $parts), 0, 40);
    }

    /**
     * Ordnet ein Regalbrett mit altem Code („I. A 1 a“) seinem Regal zu und legt fehlende Gruppen, Bereiche und Regale an.
     *
     * @return bool ob der Code zerlegt werden konnte
     */
    public function assign(CatalogShelf $shelf): bool
    {
        if (preg_match(self::PATTERN, $shelf->code, $m) !== 1) {
            return false;
        }

        $group = $this->node(ShelfSectionKind::Group, null, $m[1]);
        $area = $this->node(ShelfSectionKind::Area, $group, $m[2]);
        $rack = $this->node(ShelfSectionKind::Rack, $area, $m[3]);

        $shelf->forceFill(['section_id' => $rack->getKey(), 'board' => $m[4]])->save();

        return true;
    }

    /** Ordnet alle Regalbretter ohne Regal zu, soweit der Code zur Form passt. @return int Anzahl der zugeordneten Regalbretter */
    public function assignAll(): int
    {
        $assigned = 0;

        foreach (CatalogShelf::query()->whereNull('section_id')->orderBy('sort_order')->orderBy('code')->get() as $shelf) {
            if ($this->assign($shelf)) {
                $assigned++;
            }
        }

        return $assigned;
    }

    private function node(ShelfSectionKind $kind, ?CatalogShelfSection $parent, string $code): CatalogShelfSection
    {
        $existing = CatalogShelfSection::query()
            ->where('kind', $kind->value)
            ->where('parent_id', $parent?->getKey())
            ->whereRaw('lower(code) = ?', [mb_strtolower($code)])
            ->first();

        return $existing ?? CatalogShelfSection::query()->create([
            'kind' => $kind,
            'parent_id' => $parent?->getKey(),
            'code' => $code,
            'sort_order' => (int) CatalogShelfSection::query()->where('kind', $kind->value)->where('parent_id', $parent?->getKey())->max('sort_order') + 1,
        ]);
    }
}
