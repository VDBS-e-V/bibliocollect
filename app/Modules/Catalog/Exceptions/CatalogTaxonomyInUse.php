<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class CatalogTaxonomyInUse extends RuntimeException
{
    public static function signature(string $code, int $copies): self
    {
        return new self("Die Signatur „{$code}“ ist noch bei {$copies} Exemplaren eingetragen und kann nicht gelöscht werden.");
    }

    public static function topic(string $name, int $children, int $signatures): self
    {
        $parts = array_filter([$children > 0 ? "{$children} Unterbereich(e)" : null, $signatures > 0 ? "{$signatures} Signatur(en)" : null]);

        return new self("Der Themenbereich „{$name}“ hat noch ".implode(' und ', $parts).' und kann nicht gelöscht werden.');
    }

    public static function cycle(): self
    {
        return new self('Ein Themenbereich kann nicht unter sich selbst oder einem eigenen Unterbereich stehen.');
    }
}
