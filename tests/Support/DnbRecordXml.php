<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Baut eine SRU-Antwort der DNB mit MARC21-Datensätzen für Tests. So lassen sich einzelne Felder
 * gezielt steuern, ohne für jeden Fall einen echten Datensatz abzulegen.
 */
final class DnbRecordXml
{
    /**
     * @param  array{
     *     id?: string,
     *     isbns?: list<string>,
     *     title?: string,
     *     subtitle?: string|null,
     *     responsibility?: string|null,
     *     publisher?: string|null,
     *     place?: string|null,
     *     year?: string|null,
     *     language?: string|null,
     *     original_language?: string|null,
     *     contributors?: list<array{name: string, code?: string, gnd?: string|null}>,
     *     keywords?: list<string>,
     *     audience?: string|null,
     *     extent?: string|null,
     * }  $record
     */
    public static function record(array $record = []): string
    {
        return self::response([self::element($record)]);
    }

    /** @param array<string, mixed> $record gleiche Felder wie bei {@see self::record()} */
    public static function element(array $record = []): string
    {
        $record += [
            'id' => '1000000001',
            'isbns' => ['9783000000003'],
            'title' => 'Testtitel',
            'subtitle' => null,
            'responsibility' => null,
            'publisher' => 'Testverlag',
            'place' => 'Berlin',
            'year' => '2020',
            'language' => 'ger',
            'original_language' => null,
            'contributors' => [],
            'keywords' => [],
            'audience' => null,
            'extent' => null,
        ];

        $xml = '<record xmlns="http://www.loc.gov/MARC21/slim" type="Bibliographic">'
            .'<leader>00000nam a2200000 c 4500</leader>'
            .'<controlfield tag="001">'.self::e($record['id']).'</controlfield>';

        foreach ($record['isbns'] as $isbn) {
            $xml .= self::field('020', ['a' => $isbn]);
        }

        $languageSubfields = ['a' => $record['language']];

        if ($record['original_language'] !== null) {
            $languageSubfields['h'] = $record['original_language'];
        }

        if ($record['language'] !== null) {
            $xml .= self::field('041', $languageSubfields);
        }

        foreach ($record['contributors'] as $index => $contributor) {
            $subfields = ['a' => $contributor['name'], '4' => $contributor['code'] ?? 'aut'];

            if (($contributor['gnd'] ?? null) !== null) {
                $subfields = ['0' => '(DE-588)'.$contributor['gnd']] + $subfields;
            }

            $xml .= self::field($index === 0 ? '100' : '700', $subfields);
        }

        $titleSubfields = ['a' => $record['title']];

        if ($record['subtitle'] !== null) {
            $titleSubfields['b'] = $record['subtitle'];
        }

        if ($record['responsibility'] !== null) {
            $titleSubfields['c'] = $record['responsibility'];
        }

        $xml .= self::field('245', $titleSubfields, '1', '0');

        $publication = array_filter([
            'a' => $record['place'],
            'b' => $record['publisher'],
            'c' => $record['year'] !== null ? '['.$record['year'].']' : null,
        ], static fn (?string $value): bool => $value !== null);

        if ($publication !== []) {
            $xml .= self::field('264', $publication, ' ', '1');
        }

        if ($record['extent'] !== null) {
            $xml .= self::field('300', ['a' => $record['extent']]);
        }

        $xml .= self::field('336', ['a' => 'Text', 'b' => 'txt']).self::field('338', ['a' => 'Band', 'b' => 'nc']);

        foreach ($record['keywords'] as $keyword) {
            $xml .= self::field('650', ['a' => $keyword, '2' => 'gnd']);
        }

        if ($record['audience'] !== null) {
            $xml .= self::field('653', ['a' => '(Zielgruppe)'.$record['audience']]);
        }

        return $xml.'</record>';
    }

    /** @param list<string> $records bereits fertige <record>-Elemente */
    public static function response(array $records): string
    {
        $items = '';

        foreach ($records as $record) {
            $items .= '<record><recordSchema>MARC21-xml</recordSchema><recordPacking>xml</recordPacking><recordData>'.$record.'</recordData></record>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<searchRetrieveResponse xmlns="http://www.loc.gov/zing/srw/"><version>1.1</version>'
            .'<numberOfRecords>'.count($records).'</numberOfRecords><records>'.$items.'</records></searchRetrieveResponse>';
    }

    public static function empty(): string
    {
        return self::response([]);
    }

    /** @param array<string, string> $subfields */
    private static function field(string $tag, array $subfields, string $ind1 = ' ', string $ind2 = ' '): string
    {
        $xml = '<datafield tag="'.$tag.'" ind1="'.$ind1.'" ind2="'.$ind2.'">';

        foreach ($subfields as $code => $value) {
            $xml .= '<subfield code="'.$code.'">'.self::e($value).'</subfield>';
        }

        return $xml.'</datafield>';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
