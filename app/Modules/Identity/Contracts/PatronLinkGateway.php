<?php

declare(strict_types=1);

namespace App\Modules\Identity\Contracts;

use App\Modules\Identity\DTOs\LinkablePatron;

interface PatronLinkGateway
{
    /**
     * Consume a one-time in-person link code.
     *
     * This method is called inside the Identity transaction and must lock the
     * underlying token row before marking it as used.
     */
    public function consume(string $plainCode): LinkablePatron;
}
