<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Lookup\Dnb;

use App\Modules\Catalog\DTOs\BibliographicRecord;
use App\Modules\Catalog\Exceptions\BibliographicLookupUnavailable;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Normalizer;

/**
 * Übersetzt MARC21-XML aus der DNB-SRU-Antwort in neutrale {@see BibliographicRecord}-Treffer.
 *
 * Die Zuordnung ist bewusst konservativ: Es werden nur Felder übernommen, die sich eindeutig
 * auf das offene Katalogmodell abbilden lassen. Nichts davon wird ohne Bestätigung gespeichert.
 */
final class DnbMarcMapper
{
    private const MAX_CONTRIBUTORS = 12;

    private const MAX_KEYWORDS = 15;

    /** @var array<string, string> MARC-Sprachcode (ISO 639-2/B) => ISO 639-1 */
    private const LANGUAGES = [
        'ger' => 'de', 'deu' => 'de', 'eng' => 'en', 'fre' => 'fr', 'fra' => 'fr',
        'spa' => 'es', 'ita' => 'it', 'tur' => 'tr', 'ara' => 'ar', 'rus' => 'ru',
        'pol' => 'pl', 'por' => 'pt', 'dut' => 'nl', 'nld' => 'nl', 'ukr' => 'uk',
        'lat' => 'la', 'gre' => 'el', 'chi' => 'zh', 'jpn' => 'ja', 'swe' => 'sv',
        'dan' => 'da', 'nor' => 'no', 'fin' => 'fi', 'cze' => 'cs', 'hun' => 'hu',
        'rum' => 'ro', 'bul' => 'bg', 'hrv' => 'hr', 'srp' => 'sr', 'per' => 'fa',
    ];

    /** @var array<string, string> MARC-Relatorcode => role_key des Katalogs */
    private const ROLES = [
        'aut' => 'author',
        'ill' => 'illustrator',
        'trl' => 'translator',
        'edt' => 'editor',
    ];

    /** @return list<BibliographicRecord> */
    public function map(string $xml): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadXML($xml, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($loaded === false) {
            throw BibliographicLookupUnavailable::because('Antwort der DNB war nicht lesbar');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('s', 'http://www.loc.gov/zing/srw/');
        $xpath->registerNamespace('m', 'http://www.loc.gov/MARC21/slim');

        $nodes = $xpath->query('//s:recordData/m:record');
        $records = [];

        if ($nodes !== false) {
            foreach ($nodes as $node) {
                $record = $this->mapRecord(new MarcRecordView($xpath, $node));

                if ($record !== null) {
                    $records[] = $record;
                }
            }
        }

        if ($records === [] && $this->hasDiagnostics($xpath)) {
            throw BibliographicLookupUnavailable::because('die DNB hat die Anfrage abgelehnt');
        }

        return $records;
    }

    private function hasDiagnostics(DOMXPath $xpath): bool
    {
        $nodes = $xpath->query("//*[local-name()='diagnostic']");

        return $nodes !== false && $nodes->length > 0;
    }

    private function mapRecord(MarcRecordView $marc): ?BibliographicRecord
    {
        $titleField = $marc->fields('245')[0] ?? null;

        if ($titleField === null) {
            return null;
        }

        $title = $this->title($marc, $titleField);

        if ($title === null) {
            return null;
        }

        $recordId = $marc->control('001');
        $publication = $this->publication($marc);

        return new BibliographicRecord(
            source: 'dnb',
            sourceRecordId: $recordId,
            sourcePermalink: $recordId !== null ? 'https://d-nb.info/'.rawurlencode($recordId) : null,
            title: $title,
            subtitle: $this->clean($marc->subfield($titleField, 'b')),
            responsibilityStatement: $this->clean($marc->subfield($titleField, 'c')),
            contributors: $this->contributors($marc),
            isbn: $this->isbn($marc),
            publisherName: $publication['publisher'],
            publicationPlace: $publication['place'],
            publicationYear: $publication['year'],
            editionStatement: $this->editionStatement($marc),
            physicalExtent: $this->firstSubfield($marc, '300', 'a'),
            languageCode: $this->language($marc, 'a') ?? $this->languageFromFixedField($marc),
            originalLanguageCode: $this->language($marc, 'h'),
            mediaType: $this->mediaType($marc),
            seriesStatement: $this->series($marc),
            summary: $this->firstSubfield($marc, '520', 'a'),
            subjectKeywords: $this->keywords($marc),
            targetAudience: $this->targetAudience($marc),
        );
    }

    private function title(MarcRecordView $marc, DOMElement $field): ?string
    {
        $parts = array_filter([
            $this->clean($marc->subfield($field, 'a')),
            $this->clean($marc->subfield($field, 'n')),
            $this->clean($marc->subfield($field, 'p')),
        ], static fn (?string $part): bool => $part !== null);

        return $parts === [] ? null : implode('. ', $parts);
    }

    private function isbn(MarcRecordView $marc): ?string
    {
        $isbn13 = null;
        $isbn10 = null;

        foreach ($marc->fields('020') as $field) {
            foreach ($marc->subfields($field, 'a') as $value) {
                $compact = strtoupper(preg_replace('/[\s\p{Pd}]+/u', '', $value) ?? $value);

                if ($isbn13 === null && preg_match('/^(\d{13})/', $compact, $match) === 1) {
                    $isbn13 = $match[1];
                } elseif ($isbn10 === null && preg_match('/^(\d{9}[\dX])/', $compact, $match) === 1) {
                    $isbn10 = $match[1];
                }
            }
        }

        return $isbn13 ?? $isbn10;
    }

    /** @return array{publisher: string|null, place: string|null, year: int|null} */
    private function publication(MarcRecordView $marc): array
    {
        $fields = $marc->fields('264');
        $field = null;

        foreach ($fields as $candidate) {
            if ($candidate->getAttribute('ind2') === '1') {
                $field = $candidate;
                break;
            }
        }

        $field ??= $marc->fields('260')[0] ?? $fields[0] ?? null;

        if ($field === null) {
            return ['publisher' => null, 'place' => null, 'year' => null];
        }

        $yearSource = $marc->subfield($field, 'c');
        $year = null;

        if ($yearSource !== null && preg_match('/\b(1\d{3}|20\d{2})\b/', $yearSource, $match) === 1) {
            $year = (int) $match[1];
        }

        return [
            'publisher' => $this->clean($marc->subfield($field, 'b')),
            'place' => $this->clean($marc->subfield($field, 'a')),
            'year' => $year,
        ];
    }

    private function editionStatement(MarcRecordView $marc): ?string
    {
        $field = $marc->fields('250')[0] ?? null;

        if ($field === null) {
            return null;
        }

        $parts = array_filter([
            $this->clean($marc->subfield($field, 'a')),
            $this->clean($marc->subfield($field, 'b')),
        ], static fn (?string $part): bool => $part !== null);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /** @return list<array{name: string, role: string, gnd_id: string|null}> */
    private function contributors(MarcRecordView $marc): array
    {
        $contributors = [];
        $seen = [];

        foreach (['100', '110', '700', '710'] as $tag) {
            foreach ($marc->fields($tag) as $field) {
                // 700/710 mit $t beschreiben ein verwandtes Werk, keine Person am Titel.
                if ($marc->subfield($field, 't') !== null) {
                    continue;
                }

                $name = $this->clean($marc->subfield($field, 'a'));

                if ($name === null) {
                    continue;
                }

                $role = $this->role($marc->subfield($field, '4'), $marc->subfield($field, 'e'), $tag === '100');
                $key = mb_strtolower($name.'|'.$role);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $contributors[] = [
                    'name' => $name,
                    'role' => $role,
                    'gnd_id' => $this->gndId($marc, $field),
                ];

                if (count($contributors) >= self::MAX_CONTRIBUTORS) {
                    return $contributors;
                }
            }
        }

        return $contributors;
    }

    private function role(?string $relatorCode, ?string $relatorText, bool $mainEntry): string
    {
        $code = $relatorCode !== null ? mb_strtolower(trim($relatorCode)) : null;

        if ($code !== null && isset(self::ROLES[$code])) {
            return self::ROLES[$code];
        }

        $text = $relatorText !== null ? mb_strtolower(trim($relatorText)) : '';
        $byText = match (true) {
            str_starts_with($text, 'verfasser'), str_starts_with($text, 'autor') => 'author',
            str_starts_with($text, 'illustrat') => 'illustrator',
            str_starts_with($text, 'übersetz') => 'translator',
            str_starts_with($text, 'herausgeb') => 'editor',
            default => null,
        };

        if ($byText !== null) {
            return $byText;
        }

        if ($code !== null && preg_match('/^[a-z][a-z0-9._-]{0,79}$/', $code) === 1) {
            return $code;
        }

        return $mainEntry ? 'author' : 'contributor';
    }

    private function gndId(MarcRecordView $marc, DOMElement $field): ?string
    {
        foreach ($marc->subfields($field, '0') as $value) {
            if (str_starts_with($value, '(DE-588)')) {
                $id = trim(substr($value, 8));

                return $id === '' ? null : $id;
            }
        }

        return null;
    }

    private function language(MarcRecordView $marc, string $code): ?string
    {
        $field = $marc->fields('041')[0] ?? null;
        $value = $field !== null ? $marc->subfield($field, $code) : null;

        return $value !== null ? $this->languageCode($value) : null;
    }

    private function languageFromFixedField(MarcRecordView $marc): ?string
    {
        $fixed = $marc->control('008');

        if ($fixed === null || strlen($fixed) < 38) {
            return null;
        }

        $code = trim(substr($fixed, 35, 3));

        return $code === '' || $code === 'zxx' || $code === 'mul' || $code === 'und'
            ? null
            : $this->languageCode($code);
    }

    private function languageCode(string $value): string
    {
        $code = mb_strtolower(trim($value));

        return self::LANGUAGES[$code] ?? $code;
    }

    private function mediaType(MarcRecordView $marc): ?string
    {
        $leader = $marc->leader();

        if (strlen($leader) > 7 && $leader[7] === 's') {
            return 'magazine';
        }

        $carrier = $this->firstSubfield($marc, '338', 'b');
        $content = $this->firstSubfield($marc, '336', 'b');

        return match (true) {
            $carrier === 'cr' => 'ebook',
            in_array($carrier, ['sd', 'ss', 'sz', 'se', 'sg'], true), $content === 'spw' => 'audiobook',
            in_array($carrier, ['vd', 'vf', 'vz'], true) => 'dvd',
            $content === 'txt' => 'book',
            default => null,
        };
    }

    private function series(MarcRecordView $marc): ?string
    {
        foreach (['490', '830'] as $tag) {
            $field = $marc->fields($tag)[0] ?? null;

            if ($field === null) {
                continue;
            }

            $name = $this->clean($marc->subfield($field, 'a'));

            if ($name === null) {
                continue;
            }

            $volume = $this->clean($marc->subfield($field, 'v'));

            return $volume !== null ? $name.' ; '.$volume : $name;
        }

        return null;
    }

    private function keywords(MarcRecordView $marc): ?string
    {
        $keywords = [];

        foreach (['650', '651'] as $tag) {
            foreach ($marc->fields($tag) as $field) {
                $keyword = $this->clean($marc->subfield($field, 'a'));

                if ($keyword !== null) {
                    $keywords[mb_strtolower($keyword)] ??= $keyword;
                }
            }
        }

        foreach ($marc->fields('653') as $field) {
            $keyword = $this->clean($marc->subfield($field, 'a'));

            // Klammerpräfixe wie (Zielgruppe), (Produktform) oder (BISAC …) sind keine Schlagwörter.
            if ($keyword !== null && ! str_starts_with($keyword, '(')) {
                $keywords[mb_strtolower($keyword)] ??= $keyword;
            }
        }

        if ($keywords === []) {
            return null;
        }

        return implode(', ', array_slice(array_values($keywords), 0, self::MAX_KEYWORDS));
    }

    private function targetAudience(MarcRecordView $marc): ?string
    {
        $audiences = [];

        foreach ($marc->fields('653') as $field) {
            $value = $this->clean($marc->subfield($field, 'a'));

            if ($value !== null && preg_match('/^\(Zielgruppe\)\s*(.+)$/u', $value, $match) === 1) {
                $audiences[] = trim($match[1]);
            }
        }

        foreach ($audiences as $audience) {
            if (str_starts_with(mb_strtolower($audience), 'ab ')) {
                return $audience;
            }
        }

        if ($audiences !== []) {
            return $audiences[0];
        }

        return $this->firstSubfield($marc, '385', 'a');
    }

    private function firstSubfield(MarcRecordView $marc, string $tag, string $code): ?string
    {
        $field = $marc->fields($tag)[0] ?? null;

        return $field !== null ? $this->clean($marc->subfield($field, $code)) : null;
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Die DNB liefert Umlaute zerlegt (NFD, "a" + Kombinationszeichen). Ohne Umwandlung in NFC
        // würde eine spätere Suche nach "Seeräuber" den gespeicherten Wert nicht finden.
        $composed = Normalizer::normalize($value, Normalizer::FORM_C);
        $value = is_string($composed) ? $composed : $value;

        // Nichtsortierzeichen (U+0098/U+009C) und andere C1-Steuerzeichen der DNB entfernen.
        $cleaned = preg_replace('/[\x{0080}-\x{009F}]/u', '', $value) ?? $value;
        $cleaned = preg_replace('/\s+/u', ' ', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s*[\/:;,=]\s*$/u', '', trim($cleaned)) ?? $cleaned;
        $cleaned = trim($cleaned);

        // Eckige Klammern der RDA-Erfassung ("[2022]", "[Stuttgart]") nur entfernen, wenn sie den ganzen Wert umschließen.
        if (preg_match('/^\[([^\[\]]*)\]$/u', $cleaned, $match) === 1) {
            $cleaned = trim($match[1]);
        }

        return $cleaned === '' ? null : $cleaned;
    }
}
