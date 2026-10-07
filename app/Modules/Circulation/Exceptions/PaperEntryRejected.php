<?php

declare(strict_types=1);

namespace App\Modules\Circulation\Exceptions;

use DomainException;

/** Nachgetragene Papiereinträge wurden abgelehnt; nichts wurde gebucht. */
final class PaperEntryRejected extends DomainException
{
    /** @param list<string> $problems */
    public function __construct(public readonly array $problems)
    {
        parent::__construct(implode(' ', $problems));
    }
}
