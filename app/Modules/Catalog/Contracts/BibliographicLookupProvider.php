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

    /**
     * Datensatz anhand der Kennung der Quelle (bei der DNB die IDN/RCN, z. B. "1244853364").
     *
     * @return list<BibliographicRecord>
     *
     * @throws BibliographicLookupUnavailable
     */
    public function findByRecordId(string $recordId): array;
}
