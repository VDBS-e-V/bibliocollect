<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\DTOs\BibliographicLookupResult;
use App\Modules\Catalog\Exceptions\BibliographicLookupUnavailable;
use Illuminate\Support\Facades\Log;

/**
 * Fachlicher Einstieg für externe Metadatenabfragen. Eine ausgefallene Quelle darf die
 * Erfassung niemals blockieren: Der Fehler wird als "nicht verfügbar" gemeldet, nicht geworfen.
 */
final readonly class BibliographicLookupService
{
    public function __construct(private BibliographicLookupProvider $provider) {}

    public function byIsbn(string $isbn): BibliographicLookupResult
    {
        try {
            return new BibliographicLookupResult($this->provider->findByIsbn($isbn));
        } catch (BibliographicLookupUnavailable $exception) {
            Log::warning('Bibliografische ISBN-Abfrage fehlgeschlagen.', ['reason' => $exception->getMessage()]);

            return BibliographicLookupResult::unavailable();
        }
    }

    public function byRecordId(string $recordId): BibliographicLookupResult
    {
        try {
            return new BibliographicLookupResult($this->provider->findByRecordId($recordId));
        } catch (BibliographicLookupUnavailable $exception) {
            Log::warning('Bibliografische Abfrage per Datensatz-ID fehlgeschlagen.', ['reason' => $exception->getMessage()]);

            return BibliographicLookupResult::unavailable();
        }
    }

    public function search(?string $title, ?string $person): BibliographicLookupResult
    {
        try {
            return new BibliographicLookupResult($this->provider->search($title, $person));
        } catch (BibliographicLookupUnavailable $exception) {
            Log::warning('Bibliografische Titelsuche fehlgeschlagen.', ['reason' => $exception->getMessage()]);

            return BibliographicLookupResult::unavailable();
        }
    }
}
