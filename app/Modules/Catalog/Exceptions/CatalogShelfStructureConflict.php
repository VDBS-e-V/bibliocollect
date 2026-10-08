<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class CatalogShelfStructureConflict extends RuntimeException
{
    public static function duplicate(string $label): self
    {
        return new self("„{$label}“ gibt es an dieser Stelle schon.");
    }

    public static function notInLevel(string $level): self
    {
        return new self("Der übergeordnete Eintrag muss ein(e) {$level} sein.");
    }

    public static function inUse(string $label, int $children, int $shelves): self
    {
        $parts = array_filter([$children > 0 ? "{$children} untergeordnete(n) Eintrag/Einträgen" : null, $shelves > 0 ? "{$shelves} Regalbrett(ern)" : null]);

        return new self("„{$label}“ enthält noch ".implode(' und ', $parts).' und kann nicht gelöscht werden.');
    }

    public static function codeTaken(string $code): self
    {
        return new self("Der Standort „{$code}“ gibt es schon bei einem anderen Regalbrett. Bitte zuerst dort ändern.");
    }
}
