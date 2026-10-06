<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Lookup\Dnb;

use App\Modules\Catalog\Exceptions\BibliographicLookupUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Dünner Client für die frei zugängliche SRU-Schnittstelle der Deutschen Nationalbibliothek.
 * Die Schnittstelle benötigt keinen Zugangsschlüssel und liefert MARC21-XML.
 */
final class DnbSruClient
{
    public function search(string $query): string
    {
        $endpoint = (string) config('catalog.lookup.dnb.endpoint', 'https://services.dnb.de/sru/dnb');
        $timeout = max(1, (int) config('catalog.lookup.dnb.timeout', 10));
        $maxRecords = max(1, min((int) config('catalog.lookup.dnb.max_records', 10), 50));

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Accept' => 'application/xml',
                    'User-Agent' => 'BiblioCollect/1.0 (VDBS e.V. Schulbibliothek)',
                ])
                ->get($endpoint, [
                    'version' => '1.1',
                    'operation' => 'searchRetrieve',
                    'query' => $query,
                    'recordSchema' => 'MARC21-xml',
                    'maximumRecords' => $maxRecords,
                ]);
        } catch (ConnectionException $exception) {
            throw BibliographicLookupUnavailable::because('keine Verbindung zur DNB', $exception);
        }

        if (! $response->successful()) {
            throw BibliographicLookupUnavailable::because('DNB antwortete mit HTTP '.$response->status());
        }

        return $response->body();
    }
}
