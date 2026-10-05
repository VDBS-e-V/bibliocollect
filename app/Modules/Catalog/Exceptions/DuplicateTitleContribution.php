<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class DuplicateTitleContribution extends RuntimeException
{
    public static function for(string $displayName, string $roleKey): self
    {
        return new self("{$displayName} ist mit der Rolle {$roleKey} bereits an diesem Titel hinterlegt.");
    }
}
