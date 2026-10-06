<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Contributor;

/**
 * Findet oder legt Verantwortliche an, ohne Dubletten zu erzeugen: zuerst über die GND-ID (eindeutig),
 * sonst nur bei genau einem exakten Namenstreffer ohne widersprüchliche GND-ID.
 */
final class ContributorResolver
{
    public function resolve(string $name, ?string $gndId): Contributor
    {
        if ($gndId !== null) {
            $byGnd = Contributor::query()->where('gnd_id', $gndId)->first();

            if ($byGnd !== null) {
                return $byGnd;
            }
        }

        $sortName = $this->sortName($name);
        $displayName = $this->displayName($name);

        $candidates = Contributor::query()
            ->where('display_name', $displayName)
            ->where('sort_name', $sortName)
            ->get()
            ->filter(static fn (Contributor $candidate): bool => $candidate->gnd_id === null || $candidate->gnd_id === $gndId);

        if ($candidates->count() === 1) {
            /** @var Contributor $existing */
            $existing = $candidates->first();

            if ($existing->gnd_id === null && $gndId !== null) {
                $existing->forceFill(['gnd_id' => $gndId])->save();
            }

            return $existing;
        }

        /** @var Contributor $contributor */
        $contributor = Contributor::query()->create([
            'display_name' => $displayName,
            'sort_name' => $sortName,
            'gnd_id' => $gndId,
        ]);

        return $contributor;
    }

    /** "Nachname, Vorname" wird zur Anzeigeform "Vorname Nachname"; alles andere bleibt unverändert. */
    public function displayName(string $name): string
    {
        $parts = array_map('trim', explode(',', $name));

        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            return $parts[1].' '.$parts[0];
        }

        return trim($name);
    }

    /** Nur Personennamen in der Form "Nachname, Vorname" haben eine eigene Sortierform. */
    public function sortName(string $name): ?string
    {
        return str_contains($name, ',') ? trim($name) : null;
    }
}
