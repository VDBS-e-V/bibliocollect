<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Quality;

use App\Modules\Catalog\Models\Edition;
use App\Modules\Catalog\Models\TitleContribution;

/**
 * Prüfsumme aller Daten, die Prüfung und Vorschläge betreffen. Ein Entscheid ("kein Handlungsbedarf",
 * eine übernommene Änderung) gilt nur für genau diesen Stand.
 */
final class MetadataFingerprint
{
    /** Ausgabe mit `title.contributions.contributor` muss geladen sein. */
    public function for(Edition $edition): string
    {
        $fields = [];

        foreach (array_keys(MetadataFields::all()) as $key) {
            $fields[$key] = MetadataFields::value($edition, $key);
        }

        $contributors = $edition->title->contributions
            ->map(static fn (TitleContribution $link): array => [
                (string) $link->contributor_id,
                $link->contributor->display_name,
                $link->contributor->sort_name,
                $link->contributor->gnd_id,
                $link->role_key,
            ])
            ->all();

        return hash('sha256', (string) json_encode([$fields, $contributors], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
