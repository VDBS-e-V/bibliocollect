@php
    // Rein redaktionelle Übersicht auf Basis des Masterplans.
    // Die Werte sind Inventar-/Planungszahlen, keine live erhobenen Anwendungsmetriken.
    $families = [
        ['id' => 'F01', 'title' => 'Design Tokens', 'count' => 24, 'priority' => 'P0', 'group' => 'Fundament', 'examples' => ['Farbrollen und VDBS-Marke', 'Typografie und Abstände', 'Raster, Fokus und Dunkelmodus']],
        ['id' => 'C01', 'title' => 'Navigation & Layout', 'count' => 20, 'priority' => 'P0', 'group' => 'Oberfläche', 'examples' => ['Header, Footer, Breadcrumbs', 'Seitenleiste und mobile Navigation', 'Responsive Container']],
        ['id' => 'C02', 'title' => 'Buttons & Aktionen', 'count' => 17, 'priority' => 'P0', 'group' => 'Oberfläche', 'examples' => ['Primär- und Sekundäraktionen', 'Icon- und Kontextaktionen', 'Laden, Fokus, Disabled']],
        ['id' => 'C03', 'title' => 'Formulare', 'count' => 30, 'priority' => 'P0', 'group' => 'Oberfläche', 'examples' => ['Textfeld, Auswahl, Upload', 'ISBN und Barcode', 'Validierung und Hilfetexte']],
        ['id' => 'C04', 'title' => 'Feedback & Hilfe', 'count' => 23, 'priority' => 'P0', 'group' => 'Oberfläche', 'examples' => ['Notice Panel und Status-Badges', 'Dialog, Toast und Ladezustände', 'Fehler und Empty States']],
        ['id' => 'C05', 'title' => 'Medien & Inhalte', 'count' => 24, 'priority' => 'P1', 'group' => 'Bibliothek', 'examples' => ['Original- und Ersatzcover', 'Medien- und Themenkarten', 'Editorials und Feature Overlap']],
        ['id' => 'C06', 'title' => 'Suche & Filter', 'count' => 24, 'priority' => 'P1', 'group' => 'Bibliothek', 'examples' => ['Katalogsuche und Vorschläge', 'Facetten und Filter-Chips', 'Trefferliste und Paginierung']],
        ['id' => 'C07', 'title' => 'Listen & Tabellen', 'count' => 22, 'priority' => 'P1', 'group' => 'Daten', 'examples' => ['Datenraster und Sortierung', 'Zeilen- und Stapelaktionen', 'Import- und Protokolllisten']],
        ['id' => 'C08', 'title' => 'Kennzahlen & Charts', 'count' => 17, 'priority' => 'P1', 'group' => 'Daten', 'examples' => ['Bento und Stat Tiles', 'Diagramme und Legenden', 'Zeitraumauswahl und Datenstand']],
        ['id' => 'C09', 'title' => 'Prozesse & Schritte', 'count' => 19, 'priority' => 'P1', 'group' => 'Betrieb', 'examples' => ['Process Stepper', 'Importprüfung und Mapping', 'Bestätigen und Abschließen']],
        ['id' => 'C10', 'title' => 'Scanner & Umlauf', 'count' => 22, 'priority' => 'P1', 'group' => 'Betrieb', 'examples' => ['Barcodefeld und Scanstatus', 'Ausleihe, Rückgabe, Vormerkung', 'Inventur und Standortwechsel']],
        ['id' => 'C11', 'title' => 'Konto & Datenschutz', 'count' => 18, 'priority' => 'P1', 'group' => 'Betrieb', 'examples' => ['Anmeldung und Kontoaktivierung', 'Rollen- und Sperrstatus', 'Fristen und persönliche Daten']],
        ['id' => 'C12', 'title' => 'Administration', 'count' => 21, 'priority' => 'P2', 'group' => 'Verwaltung', 'examples' => ['Schuljahr und Schulregeln', 'Rollenmatrix und Protokolle', 'System- und Backupstatus']],
        ['id' => 'C13', 'title' => 'Druck & Kommunikation', 'count' => 21, 'priority' => 'P2', 'group' => 'Verwaltung', 'examples' => ['Belege, Ausweise, Etiketten', 'Druckansichten und Export', 'E-Mail-Vorlagen']],
        ['id' => 'C14', 'title' => 'Qualität & Sonderfälle', 'count' => 21, 'priority' => 'P0', 'group' => 'Fundament', 'examples' => ['Tastatur und Screenreader', 'Lange Titel und fehlende Daten', 'Zoom, Mobil, Dunkelmodus']],
    ];
    $templateGroups = [
        [
            'name' => 'Entdecken & Lesen',
            'detail' => 'Öffentliche Inhalte und Katalog',
            'templates' => [
                ['id' => 'L01', 'name' => 'Public Landing', 'desc' => 'Startseite mit Suche und Highlights'],
                ['id' => 'L02', 'name' => 'Editorial Story', 'desc' => 'Themen mit überlappenden Flächen'],
                ['id' => 'L03', 'name' => 'Search Results', 'desc' => 'Treffer, Filter und Sortierung'],
                ['id' => 'L04', 'name' => 'Advanced Search', 'desc' => 'Erweiterte Recherche'],
                ['id' => 'L05', 'name' => 'Record Detail', 'desc' => 'Medien-, Exemplar- und Personendetail'],
                ['id' => 'L06', 'name' => 'Collection Browser', 'desc' => 'Leselisten, Reihen und Sammlungen'],
                ['id' => 'L17', 'name' => 'Article & Help', 'desc' => 'Anleitungen, FAQ und Informationen'],
            ],
        ],
        [
            'name' => 'Konto & Bibliotheksarbeit',
            'detail' => 'Persönliche und operative Aufgaben',
            'templates' => [
                ['id' => 'L07', 'name' => 'Record Editor', 'desc' => 'Daten erstellen und bearbeiten'],
                ['id' => 'L08', 'name' => 'Personal Overview', 'desc' => 'Ausleihen und Vormerkungen'],
                ['id' => 'L09', 'name' => 'Operations Dashboard', 'desc' => 'Bento-Kennzahlen und Aufgaben'],
                ['id' => 'L10', 'name' => 'Scan Workbench', 'desc' => 'Ausleihe, Rückgabe und Scanner'],
                ['id' => 'L11', 'name' => 'Data List', 'desc' => 'Tabellen und Sammelaktionen'],
                ['id' => 'L12', 'name' => 'Workflow Wizard', 'desc' => 'Import und mehrstufige Prozesse'],
                ['id' => 'L13', 'name' => 'Data Review', 'desc' => 'Prüfung und Änderungsvergleich'],
            ],
        ],
        [
            'name' => 'Verwalten & Konfigurieren',
            'detail' => 'Regeln, Strukturen und System',
            'templates' => [
                ['id' => 'L14', 'name' => 'Settings Panel', 'desc' => 'Einstellungen mit Erläuterungen'],
                ['id' => 'L15', 'name' => 'Hierarchy Manager', 'desc' => 'Regale, Standorte und Klassifikation'],
                ['id' => 'L16', 'name' => 'Calendar Editor', 'desc' => 'Öffnungszeiten und Schließtage'],
                ['id' => 'L19', 'name' => 'System Status', 'desc' => 'Diagnosen und Aufgabenstatus'],
            ],
        ],
        [
            'name' => 'System & Ausgabe',
            'detail' => 'Identität, Druck und Kommunikation',
            'templates' => [
                ['id' => 'L18', 'name' => 'Identity Form', 'desc' => 'Login und Kontoaktivierung'],
                ['id' => 'L20', 'name' => 'Print Document', 'desc' => 'Belege, Etiketten und Ausweise'],
                ['id' => 'L21', 'name' => 'Mail Message', 'desc' => 'Benachrichtigungen und Erinnerungen'],
                ['id' => 'L22', 'name' => 'Error Page', 'desc' => 'Fehler und Wartung'],
            ],
        ],
    ];
    $areas = [
        ['title' => 'Öffentlicher Bereich', 'sub' => 'Entdecken & recherchieren', 'desc' => 'Startseite, Katalog, Buchreihen, Leselisten und Buchwünsche', 'class' => 'public', 'icon' => '01'],
        ['title' => 'Persönliches Portal', 'sub' => 'Meine Bibliothek', 'desc' => 'Ausleihen, Verlängerungen, Vormerkungen und Konto', 'class' => 'portal', 'icon' => '02'],
        ['title' => 'Bibliotheksbetrieb', 'sub' => 'Medien & Menschen', 'desc' => 'Scanner, Rückgabe, Inventur, Medien- und Personenverwaltung', 'class' => 'operations', 'icon' => '03'],
        ['title' => 'Administration', 'sub' => 'Schulen & Regeln', 'desc' => 'Schuljahre, Berechtigungen, Öffnungszeiten und Systemstatus', 'class' => 'admin', 'icon' => '04'],
    ];
    $phases = [
        ['number' => '0', 'title' => 'Bestandsaufnahme', 'description' => 'Views und CSS prüfen'],
        ['number' => '1', 'title' => 'Foundations', 'description' => 'Tokens und Raster festlegen'],
        ['number' => '2', 'title' => 'Komponenten', 'description' => 'Basis-UI gestalten'],
        ['number' => '3', 'title' => 'Patterns', 'description' => 'Bibliotheksspezifische Muster'],
        ['number' => '4', 'title' => 'Templates', 'description' => '22 Grundlayouts entwerfen'],
        ['number' => '5', 'title' => 'Pilotintegration', 'description' => 'Ein Workflow je Bereich'],
        ['number' => '6', 'title' => 'Rollout', 'description' => 'Alle Seiten schrittweise'],
        ['number' => '7', 'title' => 'Pflege', 'description' => 'Tests, Doku und Governance'],
    ];
    $exampleSteps = [
        ['label' => 'Grundlagen', 'status' => 'done'],
        ['label' => 'Muster entwickeln', 'status' => 'current'],
        ['label' => 'Seiten umstellen', 'status' => 'upcoming'],
    ];
@endphp

<div class="bc-system-map" id="system-top">
    <header class="bc-system-map__hero">
        <div class="bc-system-map__hero-copy">
            <span class="bc-system-map__eyebrow">BiblioCollect · Designsystem-Masterplan</span>
            <h1>Ein Design.<br><span>Vier Bereiche.</span><br>Alle Möglichkeiten.</h1>
            <p>Von einer einzigen Komponente zu einer kompletten Bibliotheksanwendung. Hier kannst du Struktur, Vorlagen und Gestaltungsregeln gemeinsam prüfen.</p>
            <div class="bc-system-map__hero-actions">
                <a href="#system-templates" class="bc-system-map__hero-button">22 Templates entdecken <span aria-hidden="true">↗</span></a>
                <a href="{{ route('public.design-lab.components') }}" class="bc-system-map__hero-text-link">Komponenten ansehen <span aria-hidden="true">→</span></a>
            </div>
        </div>
        <div class="bc-system-map__hero-art" aria-hidden="true">
            <span class="bc-system-map__hero-orbit"></span>
            <span class="bc-system-map__hero-orbit bc-system-map__hero-orbit--secondary"></span>
            <div class="bc-system-map__hero-book bc-system-map__hero-book--back">
                <x-library.media-cover title="Was wäre, wenn?" author="M. Zukunft" variant="mint" size="hero"/>
            </div>
            <div class="bc-system-map__hero-book bc-system-map__hero-book--front">
                <x-library.media-cover title="Zwischen den Zeilen" author="Lena Beispiel" variant="plum" size="hero"/>
            </div>
            <div class="bc-system-map__hero-label"><strong>Lesen verändert Perspektiven.</strong><small>Eine Anwendung, viele Möglichkeiten</small></div>
        </div>
    </header>

    <nav class="bc-system-map__jump" aria-label="Abschnitte des Designsystems">
        <a href="#system-architecture">Architektur</a>
        <a href="#system-foundations">Grundlagen</a>
        <a href="#system-families">Elemente</a>
        <a href="#system-patterns">Patterns</a>
        <a href="#system-templates">Templates</a>
        <a href="#system-areas">Bereiche</a>
        <a href="#system-roadmap">Umsetzung</a>
    </nav>

    <section class="bc-system-map__numbers" aria-label="Umfang der Designsystem-Planung">
        <div><strong>15</strong><span>Elementfamilien</span><small>Tokens, Komponenten und Qualität</small></div>
        <div><strong>323</strong><span>Planungspositionen</span><small>Bausteine, Varianten und Anforderungen</small></div>
        <div><strong>22</strong><span>Grundtemplates</span><small>Wiederverwendbare Seitenaufbauten</small></div>
        <div><strong>67</strong><span>Seitenszenarien</span><small>Fachliche Anwendungsfälle im Masterplan</small></div>
    </section>

    <section class="bc-system-map__section" id="system-architecture" aria-labelledby="system-architecture-heading">
        <header class="bc-system-map__heading"><span class="bc-system-map__eyebrow">01 / Architektur</span><h2 id="system-architecture-heading">Aus kleinen Bausteinen werden ganze Seiten.</h2><p>Einheitliche Grundlagen statt individueller Lösungen für jede Ansicht.</p></header>
        <ol class="bc-system-map__architecture">
            <li><small>01</small><strong>Tokens</strong><span>Farben, Typografie, Abstände</span></li>
            <li><small>02</small><strong>Komponenten</strong><span>Felder, Buttons, Status</span></li>
            <li><small>03</small><strong>Patterns</strong><span>Bento, Overlap, Stepper</span></li>
            <li><small>04</small><strong>Templates</strong><span>Wiederverwendbare Seiten</span></li>
            <li><small>05</small><strong>Fachseiten</strong><span>Echte Daten und Berechtigungen</span></li>
        </ol>
    </section>

    <section class="bc-system-map__section" id="system-foundations" aria-labelledby="system-foundations-heading">
        <header class="bc-system-map__heading"><span class="bc-system-map__eyebrow">02 / Foundations</span><h2 id="system-foundations-heading">Ein wiedererkennbarer Auftritt.</h2><p>Wir behalten das festgelegte VDBS-Farbsystem und die vorhandene Typografie bei.</p></header>
        <div class="bc-system-map__foundation-grid">
            <div class="bc-system-map__foundation-card">
                <h3>Markenfarben</h3>
                <div class="bc-system-map__swatches">
                    <div><span class="bc-system-map__swatch bc-system-map__swatch--green"></span><strong>Grün</strong><small>#2DC08E</small></div>
                    <div><span class="bc-system-map__swatch bc-system-map__swatch--purple"></span><strong>Lila</strong><small>#58275A</small></div>
                    <div><span class="bc-system-map__swatch bc-system-map__swatch--ink"></span><strong>Ink</strong><small>#0F172A</small></div>
                    <div><span class="bc-system-map__swatch bc-system-map__swatch--paper"></span><strong>Neutral</strong><small>Flächen</small></div>
                </div>
                <p>Die funktionalen Statusfarben kommen aus den semantischen Tokens, nicht aus frei erfundenen Akzentfarben.</p>
            </div>
            <div class="bc-system-map__foundation-card bc-system-map__foundation-card--type">
                <h3>Typografie</h3>
                <strong class="bc-system-map__sample-type">Aa</strong>
                <span>Lato · Lesetext und Benutzeroberfläche</span>
                <p>Klare Hierarchie, gut lesbare Absätze und lange deutsche Titel ohne abgeschnittene Informationen.</p>
            </div>
            <div class="bc-system-map__foundation-card">
                <h3>Rhythmus & Raster</h3>
                <div class="bc-system-map__spacing-sample" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span></div>
                <p>Flexible Container, verbindliche Abstände, sinnvoll eingesetzte Überlagerungen und responsive Umbrüche.</p>
            </div>
        </div>
    </section>

    <section class="bc-system-map__section" id="system-families" aria-labelledby="system-families-heading">
        <header class="bc-system-map__heading bc-system-map__heading--split">
            <div><span class="bc-system-map__eyebrow">03 / Komponentenbibliothek</span><h2 id="system-families-heading">Alles, was die Anwendung braucht.</h2><p>15 Familien aus dem Masterplan. Die Beispiele lassen sich direkt aufklappen.</p></div>
            <a href="{{ route('public.design-lab.components') }}" class="bc-system-map__plain-link">Live-Komponenten ansehen <span aria-hidden="true">↗</span></a>
        </header>
        <div class="bc-system-map__family-grid">
            @foreach ($families as $family)
                <details class="bc-system-map__family">
                    <summary>
                        <span class="bc-system-map__family-head"><span class="bc-system-map__family-id">{{ $family['id'] }}</span><span class="bc-system-map__family-priority">{{ $family['priority'] }}</span></span>
                        <strong>{{ $family['title'] }}</strong>
                        <span class="bc-system-map__family-tail"><span>{{ $family['count'] }} Positionen · {{ $family['group'] }}</span><span class="bc-system-map__family-chevron" aria-hidden="true">+</span></span>
                    </summary>
                    <ul>
                        @foreach ($family['examples'] as $example)
                            <li>{{ $example }}</li>
                        @endforeach
                    </ul>
                </details>
            @endforeach
        </div>
        <p class="bc-system-map__footnote">P0 = unverzichtbare Grundlagen · P1 = Hauptabläufe · P2 = Spezial- und Folgeelemente. Die Zahlen beschreiben den Soll-Katalog, nicht den Stand fertig gebauter Komponenten.</p>
    </section>

    <section class="bc-system-map__section" id="system-patterns" aria-labelledby="system-patterns-heading">
        <header class="bc-system-map__heading"><span class="bc-system-map__eyebrow">04 / Patterns</span><h2 id="system-patterns-heading">Wiederkehrende Ideen, bewusst eingesetzt.</h2><p>Diese vier Grundmuster sind bereits im Designstudio als Blade-Komponenten vorhanden.</p></header>
        <div class="bc-system-map__pattern-grid">
            <article class="bc-system-map__pattern">
                <div class="bc-system-map__pattern-preview bc-system-map__pattern-preview--overlap" aria-hidden="true">
                    <div class="bc-system-map__pattern-photo"><img src="/brand/vdbs/figures-chain.svg" alt="" loading="lazy"></div>
                    <div class="bc-system-map__pattern-overlay"><strong>Raum für Ideen.</strong><span>Entdecken →</span></div>
                </div>
                <div class="bc-system-map__pattern-copy"><span>Pattern 01</span><h3>Feature Overlap</h3><p>Visuelle Fläche mit überlagerter Textkarte für redaktionelle Inhalte.</p></div>
            </article>
            <article class="bc-system-map__pattern">
                <div class="bc-system-map__pattern-preview bc-system-map__pattern-preview--bento" aria-hidden="true">
                    <div class="bc-system-map__mini-bento"><span>Medien<strong>1.248</strong></span><span>Ausleihen<strong>87</strong></span><span>Vormerkungen<strong>12</strong></span></div>
                </div>
                <div class="bc-system-map__pattern-copy"><span>Pattern 02</span><h3>Bento Grid</h3><p>Unterschiedlich gewichtete Kennzahlen und Aufgaben auf einen Blick.</p></div>
            </article>
            <article class="bc-system-map__pattern">
                <div class="bc-system-map__pattern-preview bc-system-map__pattern-preview--stepper">
                    <x-library.process-stepper :steps="$exampleSteps" label="Beispiel: Designumsetzung" style="--bc-steps: 3;" />
                </div>
                <div class="bc-system-map__pattern-copy"><span>Pattern 03</span><h3>Process Stepper</h3><p>Verständliche Schrittfolge mit erledigten, aktiven und offenen Phasen.</p></div>
            </article>
            <article class="bc-system-map__pattern">
                <div class="bc-system-map__pattern-preview bc-system-map__pattern-preview--notice">
                    <x-library.notice-panel variant="success" title="Beispiel: Vorgang erfolgreich" compact><p>Die Rückmeldung bleibt kurz und verständlich.</p></x-library.notice-panel>
                </div>
                <div class="bc-system-map__pattern-copy"><span>Pattern 04</span><h3>Notice Panel</h3><p>Informationen, Erfolge, Warnungen und Fehler in einer konsistenten Form.</p></div>
            </article>
        </div>
    </section>

    <section class="bc-system-map__section" id="system-templates" aria-labelledby="system-templates-heading">
        <header class="bc-system-map__heading bc-system-map__heading--split"><div><span class="bc-system-map__eyebrow">05 / Seitentemplates</span><h2 id="system-templates-heading">22 Baupläne. Nicht 67 Insellösungen.</h2><p>Ein Template beschreibt das Layout. Daten, Rechte und konkrete Abläufe liefert weiterhin die Anwendung.</p></div><a href="{{ route('public.design-review') }}" class="bc-system-map__plain-link">Bisherigen Vorlagenatlas öffnen <span aria-hidden="true">↗</span></a></header>
        @foreach ($templateGroups as $group)
            <div class="bc-system-map__template-group">
                <div class="bc-system-map__template-intro"><h3>{{ $group['name'] }}</h3><p>{{ $group['detail'] }}</p></div>
                <div class="bc-system-map__template-grid">
                    @foreach ($group['templates'] as $template)
                        <article class="bc-system-map__template">
                            <span>{{ $template['id'] }}</span>
                            <h4>{{ $template['name'] }}</h4>
                            <p>{{ $template['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach
        <p class="bc-system-map__footnote">Die 22 Templates sind eine geplante Soll-Struktur. Bereits existierende Beispielseiten zeigen einige davon – die übrigen werden in folgenden Designrunden ausgearbeitet.</p>
    </section>

    <section class="bc-system-map__section" id="system-areas" aria-labelledby="system-areas-heading">
        <header class="bc-system-map__heading"><span class="bc-system-map__eyebrow">06 / Anwendungsbereiche</span><h2 id="system-areas-heading">Ein Designsystem für die ganze Bibliothek.</h2><p>Die vier Bereiche teilen sich Komponenten, haben aber unterschiedliche Anforderungen an Informationsdichte und Bediengeschwindigkeit.</p></header>
        <div class="bc-system-map__area-grid">
            @foreach ($areas as $area)
                <article class="bc-system-map__area bc-system-map__area--{{ $area['class'] }}">
                    <span>{{ $area['icon'] }} / {{ $area['sub'] }}</span>
                    <h3>{{ $area['title'] }}</h3>
                    <p>{{ $area['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="bc-system-map__section" id="system-roadmap" aria-labelledby="system-roadmap-heading">
        <header class="bc-system-map__heading"><span class="bc-system-map__eyebrow">07 / Umsetzung</span><h2 id="system-roadmap-heading">Von der Idee zur einheitlichen Anwendung.</h2><p>Ein bewusster Rollout – mit Tests und Fachabnahme, statt alle produktiven Seiten auf einmal umzubauen.</p></header>
        <ol class="bc-system-map__roadmap">
            @foreach ($phases as $phase)
                <li><span class="bc-system-map__roadmap-index">{{ $phase['number'] }}</span><div><strong>{{ $phase['title'] }}</strong><span>{{ $phase['description'] }}</span></div></li>
            @endforeach
        </ol>
        <div class="bc-system-map__conclusion">
            <div><span class="bc-system-map__eyebrow">Nächste Designrunde</span><h3>Erst die Bausteine freigeben. Dann die echten Seiten umstellen.</h3><p>Designvorschauen und produktive Fachseiten bleiben bewusst getrennt. Änderungen an Ausleihen, Nutzerdaten oder Rollen erfolgen nicht über das Designstudio.</p></div>
            <a href="{{ route('public.design-lab.components') }}">Zur Komponentenbibliothek <span aria-hidden="true">→</span></a>
        </div>
    </section>

    <footer class="bc-system-map__footer-links">
        <a href="{{ route('public.design-lab') }}">← Zur Designlab-Übersicht</a>
        <a href="#system-top">Zum Seitenanfang ↑</a>
    </footer>
</div>
