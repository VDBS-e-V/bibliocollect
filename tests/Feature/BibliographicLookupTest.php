<?php

declare(strict_types=1);

use App\Modules\Catalog\Contracts\BibliographicLookupProvider;
use App\Modules\Catalog\Exceptions\BibliographicLookupUnavailable;
use App\Modules\Catalog\Lookup\Dnb\DnbLookupProvider;
use App\Modules\Catalog\Lookup\Dnb\DnbMarcMapper;
use App\Modules\Catalog\Services\BibliographicLookupService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function dnbFixture(string $name): string
{
    return (string) file_get_contents(__DIR__.'/../Fixtures/dnb/'.$name);
}

it('maps a real DNB MARC21 record to neutral catalog fields', function (): void {
    $records = app(DnbMarcMapper::class)->map(dnbFixture('isbn-9783522202800.xml'));

    expect($records)->toHaveCount(1);

    $record = $records[0];

    expect($record->source)->toBe('dnb')
        ->and($record->sourceRecordId)->toBe('1244853364')
        ->and($record->sourcePermalink)->toBe('https://d-nb.info/1244853364')
        ->and($record->title)->toBe('Shi Yu')
        ->and($record->subtitle)->toBe('die Unbezwingbare')
        ->and($record->responsibilityStatement)->toBe('Davide Morosinotto ; aus dem Italienischen von Cornelia Panzacchi')
        ->and($record->isbn)->toBe('9783522202800')
        ->and($record->publisherName)->toBe('Thienemann')
        ->and($record->publicationPlace)->toBe('Stuttgart')
        ->and($record->publicationYear)->toBe(2022)
        ->and($record->physicalExtent)->toBe('506 Seiten')
        ->and($record->languageCode)->toBe('de')
        ->and($record->originalLanguageCode)->toBe('it')
        ->and($record->mediaType)->toBe('book')
        ->and($record->targetAudience)->toBe('ab 13 Jahre');
});

it('keeps roles, GND ids and ordering of contributors', function (): void {
    $record = app(DnbMarcMapper::class)->map(dnbFixture('isbn-9783522202800.xml'))[0];

    expect($record->contributors)->toBe([
        ['name' => 'Morosinotto, Davide', 'role' => 'author', 'gnd_id' => '1015211690'],
        ['name' => 'Panzacchi, Cornelia', 'role' => 'translator', 'gnd_id' => '112058426'],
        ['name' => 'Dautremer, Rébecca', 'role' => 'illustrator', 'gnd_id' => '1011472945'],
        ['name' => 'Kümmel, Timo', 'role' => 'illustrator', 'gnd_id' => '1209068389'],
    ]);
});

it('derives keywords from subject headings and drops bracket-prefixed classification noise', function (): void {
    $record = app(DnbMarcMapper::class)->map(dnbFixture('isbn-9783522202800.xml'))[0];

    expect($record->subjectKeywords)->toContain('Seeräuber')
        ->and($record->subjectKeywords)->toContain('Karate')
        ->and($record->subjectKeywords)->toContain('China')
        ->and($record->subjectKeywords)->not->toContain('BISAC')
        ->and($record->subjectKeywords)->not->toContain('Zielgruppe')
        ->and($record->subjectKeywords)->not->toContain('Produktform');
});

it('strips non sorting control characters from DNB titles', function (): void {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<searchRetrieveResponse xmlns="http://www.loc.gov/zing/srw/"><records><record><recordData>
<record xmlns="http://www.loc.gov/MARC21/slim">
<controlfield tag="001">1</controlfield>
<datafield tag="245" ind1="1" ind2="0"><subfield code="a">&#152;Der&#156; Titel</subfield></datafield>
</record></recordData></record></records></searchRetrieveResponse>
XML;

    $record = app(DnbMarcMapper::class)->map($xml)[0];

    expect($record->title)->toBe('Der Titel');
});

it('looks up a DNB record by ISBN through the SRU interface', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(dnbFixture('isbn-9783522202800.xml'), 200)]);

    $result = app(BibliographicLookupService::class)->byIsbn('978-3-522-20280-0');

    expect($result->available)->toBeTrue()
        ->and($result->records)->toHaveCount(1)
        ->and($result->records[0]->title)->toBe('Shi Yu');

    Http::assertSent(static fn (Request $request): bool => str_contains($request->url(), 'services.dnb.de/sru/dnb')
        && $request['query'] === 'num=9783522202800'
        && $request['recordSchema'] === 'MARC21-xml');
});

it('builds title and person searches from plain words only', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response(dnbFixture('isbn-9783522202800.xml'), 200)]);

    app(BibliographicLookupService::class)->search('Momo" or tit=x', 'Ende, Michael');

    Http::assertSent(static fn (Request $request): bool => $request['query'] === 'tit=Momo and tit=or and tit=tit and tit=x and per=Ende and per=Michael');
});

it('does not query the DNB for an invalid ISBN or an empty search', function (): void {
    Http::fake();

    $service = app(BibliographicLookupService::class);

    expect($service->byIsbn('abc')->records)->toBe([])
        ->and($service->search(null, '  ')->records)->toBe([]);

    Http::assertNothingSent();
});

it('reports the source as unavailable instead of failing the workflow', function (): void {
    Http::fake(['services.dnb.de/*' => Http::response('', 503)]);

    expect(app(BibliographicLookupService::class)->byIsbn('9783522202800')->available)->toBeFalse();

    Http::fake(static fn (): never => throw new ConnectionException('timeout'));

    $result = app(BibliographicLookupService::class)->search('Momo', null);

    expect($result->available)->toBeFalse()
        ->and($result->records)->toBe([]);
});

it('returns no records for an empty SRU result and rejects SRU diagnostics', function (): void {
    $empty = '<?xml version="1.0"?><searchRetrieveResponse xmlns="http://www.loc.gov/zing/srw/"><numberOfRecords>0</numberOfRecords></searchRetrieveResponse>';

    expect(app(DnbMarcMapper::class)->map($empty))->toBe([]);

    $diagnostic = '<?xml version="1.0"?><searchRetrieveResponse xmlns="http://www.loc.gov/zing/srw/"><numberOfRecords>0</numberOfRecords><diagnostics><diagnostic><message>Unsupported index</message></diagnostic></diagnostics></searchRetrieveResponse>';

    expect(static fn (): array => app(DnbMarcMapper::class)->map($diagnostic))
        ->toThrow(BibliographicLookupUnavailable::class);

    expect(static fn (): array => app(DnbMarcMapper::class)->map('<not-xml'))
        ->toThrow(BibliographicLookupUnavailable::class);
});

it('exposes the DNB provider behind the lookup contract', function (): void {
    expect(app(BibliographicLookupProvider::class))
        ->toBeInstanceOf(DnbLookupProvider::class);
});
