<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Catalog\Models\Title;
use App\Modules\Patrons\Models\Patron;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SampleOperationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Prüft Seiten auf häufige Barrierefreiheitsfehler, die sich automatisch finden lassen. Das ersetzt keine Prüfung mit
 * Screenreader und Tastatur, fängt aber Rückschritte ab: Seitensprache, genau eine Hauptüberschrift, Beschriftung aller
 * Formularfelder, Tabellenköpfe mit Bezug, Bilder mit alt, benannte Schaltflächen und Links, eindeutige IDs.
 *
 * @return list<string> Befunde
 */
function accessibilityFindings(string $html): array
{
    $findings = [];
    $dom = new DOMDocument;
    @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);

    $root = $xpath->query('//html')->item(0);

    if (! $root instanceof DOMElement || trim($root->getAttribute('lang')) === '') {
        $findings[] = 'html ohne lang-Attribut';
    }

    if ($xpath->query('//title')->item(0) === null || trim((string) $xpath->query('//title')->item(0)->textContent) === '') {
        $findings[] = 'Seite ohne Titel';
    }

    $h1 = $xpath->query('//h1')->length;

    if ($h1 !== 1) {
        $findings[] = "{$h1} Hauptüberschriften (erwartet: genau eine)";
    }

    $ids = [];

    foreach ($xpath->query('//*[@id]') as $node) {
        $ids[$node->getAttribute('id')][] = $node->nodeName;
    }

    foreach ($ids as $id => $nodes) {
        if (count($nodes) > 1) {
            $findings[] = "ID „{$id}“ kommt ".count($nodes).'-mal vor';
        }
    }

    $labelled = [];

    foreach ($xpath->query('//label[@for]') as $label) {
        $labelled[$label->getAttribute('for')] = true;
    }

    foreach ($xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="image")] | //select | //textarea') as $field) {
        /** @var DOMElement $field */
        $id = $field->getAttribute('id');
        $hasName = isset($labelled[$id]) && $id !== ''
            || $field->hasAttribute('aria-label')
            || $field->hasAttribute('aria-labelledby')
            || $field->hasAttribute('title')
            || $xpath->query('ancestor::label', $field)->length > 0;

        if (! $hasName) {
            $findings[] = 'Formularfeld ohne Beschriftung: '.$field->nodeName.'[name='.$field->getAttribute('name').']';
        }
    }

    foreach ($xpath->query('//th[not(@scope)]') as $th) {
        // Zellen im Kopf einer Tabelle (thead) gelten als Spaltenköpfe; in Zeilen ohne scope fehlt der Bezug.
        if ($xpath->query('ancestor::thead', $th)->length === 0) {
            $findings[] = 'Tabellenzelle th ohne scope: '.trim(preg_replace('/\s+/', ' ', $th->textContent) ?? '');
        }
    }

    foreach ($xpath->query('//img') as $img) {
        /** @var DOMElement $img */
        if (! $img->hasAttribute('alt')) {
            $findings[] = 'Bild ohne alt: '.$img->getAttribute('src');
        }
    }

    foreach ($xpath->query('//button') as $button) {
        /** @var DOMElement $button */
        $name = trim($button->textContent) !== '' || $button->hasAttribute('aria-label') || $button->hasAttribute('title');

        if (! $name) {
            $findings[] = 'Schaltfläche ohne Namen';
        }
    }

    foreach ($xpath->query('//a[@href]') as $link) {
        /** @var DOMElement $link */
        $name = trim($link->textContent) !== '' || $link->hasAttribute('aria-label') || $xpath->query('.//img[@alt!=""]', $link)->length > 0;

        if (! $name) {
            $findings[] = 'Link ohne Namen: '.$link->getAttribute('href');
        }
    }

    if ($xpath->query('//main')->length !== 1 && $xpath->query('//*[@id="main-content"]')->length !== 1) {
        $findings[] = 'kein eindeutiger Hauptbereich (main)';
    }

    foreach ($xpath->query('//a[starts-with(@href, "#") and string-length(@href) > 1]') as $anchor) {
        /** @var DOMElement $anchor */
        $target = substr($anchor->getAttribute('href'), 1);

        if ($xpath->query('//*[@id="'.$target.'"]')->length === 0) {
            $findings[] = 'Sprungmarke ohne Ziel: #'.$target;
        }
    }

    return $findings;
}

it('finds no automatically detectable accessibility problems on the main pages', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(SampleOperationsSeeder::class);

    $manager = User::query()->where('email', 'management@demo.bibliocollect.test')->firstOrFail();
    $student = User::query()->where('email', 'student@demo.bibliocollect.test')->firstOrFail();
    $patron = Patron::query()->where('library_number', 'S-10105')->firstOrFail();
    $title = Title::query()->firstOrFail();

    $guest = [
        'Startseite' => route('public.home'),
        'Katalog' => route('public.catalog.index'),
        'Katalog mit Suche' => route('public.catalog.index', ['q' => 'momo']),
        'Erweiterte Suche' => route('public.catalog.advanced'),
        'Titelseite' => route('public.catalog.show', $title->getKey()),
        'Anmelden' => route('login'),
        'Passwort vergessen' => route('password.request'),
        'Konto aktivieren' => route('identity.claim.create'),
        'Impressum' => route('public.page', ['slug' => 'impressum']),
        'Buchwunsch erfassen' => route('public.wishes.create'),
    ];

    $staff = [
        'Portal' => [$student, route('portal.home')],
        'Arbeitsplatz' => [$manager, route('pos.home')],
        'Ausleihe Start' => [$manager, route('pos.terminal')],
        'Ausleihkonten' => [$manager, route('pos.patrons.index')],
        'Ausleihkonto' => [$manager, route('pos.patrons.show', ['patronId' => $patron->getKey()])],
        'Ausleihkonten importieren' => [$manager, route('pos.patrons.import.create')],
        'Katalogpflege' => [$manager, route('pos.catalog.index')],
        'Katalogqualität' => [$manager, route('pos.catalog.quality.index')],
        'Eindeutige Vorschläge' => [$manager, route('pos.catalog.quality.safe')],
        'Medium erfassen' => [$manager, route('pos.catalog.intake.identify', ['neu' => 1])],
        'Etiketten' => [$manager, route('pos.labels.copies', ['neu' => 1])],
        'Ausweise' => [$manager, route('pos.labels.cards')],
        'Vormerkungen' => [$manager, route('pos.reservations.index')],
        'Ausweise ausgeben' => [$manager, route('pos.labels.cards.issue', ['klasse' => 'alle'])],
        'Ausweismotive' => [$manager, route('pos.labels.cards.designs')],
        'Ausweis-Charge' => [$manager, route('pos.labels.cards')],
        'Buchwünsche' => [$manager, route('pos.wishes.index')],
        'Medien einsortieren' => [$manager, route('pos.shelving', ['regalbrett' => 'x'])],
        'Regalbretter' => [$manager, route('administration.shelves.index')],
        'Signaturen' => [$manager, route('administration.signatures.index')],
        'Themenbereiche' => [$manager, route('administration.topics.index')],
        'Benutzerkonten' => [$manager, route('administration.users.index')],
        'Konto anlegen' => [$manager, route('administration.users.create')],
        'Systemzustand' => [$manager, route('administration.system.index')],
        'Inventarnummern' => [$manager, route('administration.inventory.index')],
        'Alle Vorgänge' => [$manager, route('pos.processes')],
        'Portal Buchwünsche' => [$student, route('portal.wishes.index')],
        'Klassenlisten' => [$manager, route('pos.reports.class-loans', ['modus' => 'alle'])],
        'Statistik' => [$manager, route('pos.statistics')],
        'Hilfe' => [$manager, route('pos.help.show', ['topic' => 'ausleihe'])],
        'Verwaltung' => [$manager, route('administration.home')],
        'Schule' => [$manager, route('administration.school.index')],
        'Schuljahreswechsel' => [$manager, route('administration.transition.show')],
        'Öffnungszeiten' => [$manager, route('administration.calendar.index')],
        'Protokoll' => [$manager, route('administration.audit.index')],
        'Seiten' => [$manager, route('administration.pages.index')],
        'Seite bearbeiten' => [$manager, route('administration.pages.edit', ['slug' => 'impressum'])],
    ];

    $report = [];

    foreach ($guest as $name => $url) {
        $findings = accessibilityFindings((string) $this->get($url)->assertOk()->getContent());

        if ($findings !== []) {
            $report[$name] = $findings;
        }
    }

    foreach ($staff as $name => [$user, $url]) {
        $findings = accessibilityFindings((string) $this->actingAs($user)->get($url)->assertOk()->getContent());

        if ($findings !== []) {
            $report[$name] = $findings;
        }
    }

    expect($report)->toBe([]);
});

it('detects the problems it is meant to find', function (): void {
    $html = '<html><head></head><body><h1>A</h1><h1>B</h1><img src="x.png"><input name="q"><button></button><table><tr><th>Name</th></tr></table><a href="#nirgends">Weiter</a></body></html>';

    $findings = implode(' | ', accessibilityFindings($html));

    expect($findings)->toContain('html ohne lang-Attribut')
        ->toContain('Seite ohne Titel')
        ->toContain('2 Hauptüberschriften')
        ->toContain('Bild ohne alt')
        ->toContain('Formularfeld ohne Beschriftung')
        ->toContain('Schaltfläche ohne Namen')
        ->toContain('th ohne scope')
        ->toContain('Sprungmarke ohne Ziel');
});
