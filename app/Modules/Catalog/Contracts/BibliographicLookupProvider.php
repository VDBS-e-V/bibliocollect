<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\Exceptions\BibliographicLookupUnavailable;

interface BibliographicLookupProvider
{
    /**
     * @return list<BibliographicRecord>
     *
     * @throws BibliographicLookupUnavailable
     */
    public function findByIsbn(string $isbn): array;

    /**
     * @return list<BibliographicRecord>
     *
     * @throws BibliographicLookupUnavailable
     */
    public function search(?string $title, ?string $person): array;
}
