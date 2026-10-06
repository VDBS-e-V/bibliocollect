<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

/** Der Vorschlag passt nicht mehr zum Stand der Ausgabe und darf nicht übernommen werden. */
final class MetadataProposalOutdated extends RuntimeException
{
    public static function missing(): self
    {
        return new self('Zu diesem Fall liegt kein Vorschlag vor.');
    }

    public static function changed(): self
    {
        return new self('Die Ausgabe wurde seit dem Vorschlag geändert. Bitte den Vorschlag neu abfragen.');
    }
}
