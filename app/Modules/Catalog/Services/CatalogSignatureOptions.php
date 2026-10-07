<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\CatalogSignature;

/** Auswahlliste der Signaturen mit ihren Themenbereichen („I. A 1 d · Leicht zu lesen / Ich lese schon viel“). */
final class CatalogSignatureOptions
{
    /** @return array<string, string> Id der Signatur zu Anzeigetext, nach Signatur geordnet */
    public function forSelect(): array
    {
        $options = [];

        $signatures = CatalogSignature::query()->with('topics')->get()->sort(static fn (CatalogSignature $a, CatalogSignature $b): int => strnatcasecmp($a->signature, $b->signature));

        foreach ($signatures as $signature) {
            $topics = $signature->topics->pluck('name')->implode(' / ');
            $options[(string) $signature->getKey()] = $topics !== '' ? $signature->signature.' · '.$topics : $signature->signature;
        }

        return $options;
    }
}
