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

    /** @return list<string> */
    public function activeCodes(): array
    {
        return array_map('strval', CatalogShelf::query()->where('is_active', true)->pluck('code')->all());
    }
}
