<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Services\AuditRecorder;
use App\Modules\Catalog\Models\CatalogTopic;
use App\Modules\Catalog\Models\Title;
use Illuminate\Support\Facades\Cache;

/** Markiert einen Titel oder ein Thema als „empfohlen“ (Startseite) oder nimmt die Markierung zurück. Neu Markierte kommen ans Ende. */
final readonly class ToggleFeaturedAction
{
    public function __construct(private AuditRecorder $audit) {}

    /** @return bool true, wenn danach empfohlen */
    public function execute(Title|CatalogTopic $item): bool
    {
        $next = $item->featured_position === null
            ? ((int) $item::query()->max('featured_position')) + 1
            : null;

        $item->forceFill(['featured_position' => $next])->save();

        // Die Startseite soll die Änderung sofort zeigen.
        Cache::forget('home-showcase.v1');

        $this->audit->record($next === null ? 'catalog.featured.removed' : 'catalog.featured.added', $next === null ? 'Empfehlung entfernt.' : 'Für die Startseite empfohlen.', $item);

        return $next !== null;
    }
}
