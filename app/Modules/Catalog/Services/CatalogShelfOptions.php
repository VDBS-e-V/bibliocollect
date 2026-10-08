<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\CatalogShelf;

/** Auswahlliste der Regalbretter für das Feld „Standort“. */
final class CatalogShelfOptions
{
    /**
     * Wert (Code des Regalbretts) zu Anzeigetext. Ein bisheriger Standort, der nicht mehr in der Liste steht
     * (ausgeschaltet oder gelöscht), bleibt beim Bearbeiten wählbar, damit er nicht ungewollt verloren geht.
     *
     * @return array<string, string>
     */
    public function forSelect(?string $current = null): array
    {
        $options = [];

        foreach (CatalogShelf::query()->where('is_active', true)->orderBy('sort_order')->orderBy('code')->get() as $shelf) {
            $options[$shelf->code] = $shelf->display();
        }

        if ($current !== null && $current !== '' && ! isset($options[$current])) {
            $options[$current] = $current.' (nicht mehr in der Liste)';
        }

        return $options;
    }

    /**
     * Die auswählbaren Regalbretter nach Regal gruppiert („I › A › 1 · Name“), danach die ohne Regal.
     *
     * @return list<array{label: string, options: array<string, string>}>
     */
    public function grouped(): array
    {
        $shelves = CatalogShelf::query()->where('is_active', true)->with('rack.parent.parent')->orderBy('sort_order')->orderBy('code')->get();
        $groups = [];
        $loose = [];

        foreach ($shelves as $shelf) {
            $rack = $shelf->rack;

            if ($rack === null) {
                $loose[$shelf->code] = $shelf->display();

                continue;
            }

            $key = (string) $rack->getKey();
            $area = $rack->parent;
            $group = $area?->parent;
            $path = implode(' › ', array_filter([$group?->code, $area?->code, $rack->code], static fn (?string $part): bool => $part !== null && $part !== ''));
            $groups[$key]['label'] ??= $path.($rack->name ? ' · '.$rack->name : '');
            $groups[$key]['options'][$shelf->code] = $shelf->display();
        }

        $result = array_values($groups);

        if ($loose !== []) {
            $result[] = ['label' => $result === [] ? 'Regalbretter' : 'Ohne Regal', 'options' => $loose];
        }

        return $result;
    }

    /** @return list<string> */
    public function activeCodes(): array
    {
        return array_map('strval', CatalogShelf::query()->where('is_active', true)->pluck('code')->all());
    }
}
