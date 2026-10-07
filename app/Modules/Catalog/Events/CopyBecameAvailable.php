<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\Copy;

/** Ein Exemplar ist neu im Bestand oder wieder aktiv (zum Beispiel gefunden). Wartende Vormerkungen können nachrücken. */
final readonly class CopyBecameAvailable
{
    public function __construct(public Copy $copy) {}
}
