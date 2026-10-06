<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTOs;

final readonly class LegacyCatalogQualityReport
{
    /**
     * @param  array<string, int>  $summary
     * @param  list<array{
     *     media_id:?string,
     *     barcode:?string,
     *     dnb_record_id:?string,
     *     title:?string,
     *     responsibility_statement:?string,
     *     artifact_fields:list<string>,
     *     structured_contributor_count:int,
     *     additional_contributor_count:int,
     *     issues:list<string>
     * }>  $issues
     */
    public function __construct(
        public array $summary,
        public array $issues,
    ) {}
}
