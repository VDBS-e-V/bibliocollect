<?php

declare(strict_types=1);

namespace App\Modules\Audit\DTOs;

use Carbon\CarbonImmutable;

/**
 * Filter für das Protokoll. Die betroffenen Personen kommen als Kennungen herein (die Seite löst Name oder Nummer auf), weil das
 * Protokoll selbst nur Kennungen kennt.
 */
final readonly class AuditFilter
{
    /**
     * @param  list<string>|null  $patronIds  null: kein Personenfilter; leere Liste: keine Person gefunden (also keine Treffer)
     */
    public function __construct(
        public ?string $area = null,
        public ?string $action = null,
        public ?string $term = null,
        public ?CarbonImmutable $from = null,
        public ?CarbonImmutable $to = null,
        public ?int $actorId = null,
        public ?array $patronIds = null,
    ) {}
}
