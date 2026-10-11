<x-app-shell surface="public" title="Design-Review">
    @php
        // Ausschließlich statische Vorschauwerte. Keine Bestands-, Konto- oder Personendaten.
        $templateLinks = [
            ['id' => 'startseite', 'number' => '01', 'label' => 'Startseite'],
            ['id' => 'suche', 'number' => '02', 'label' => 'Suche & Filter'],
            ['id' => 'detail', 'number' => '03', 'label' => 'Mediendetail'],
            ['id' => 'listen', 'number' => '04', 'label' => 'Listen & Tabellen'],
            ['id' => 'formulare', 'number' => '05', 'label' => 'Formulare'],
            ['id' => 'portal', 'number' => '06', 'label' => 'Persönliches Portal'],
            ['id' => 'scan', 'number' => '07', 'label' => 'Ausleihe & Scanner'],
            ['id' => 'assistent', 'number' => '08', 'label' => 'Assistenten'],
            ['id' => 'sammlungen', 'number' => '09', 'label' => 'Sammlungen'],
            ['id' => 'dashboard', 'number' => '10', 'label' => 'Dashboard'],
            ['id' => 'hilfeseite', 'number' => '11', 'label' => 'Information & Hilfe'],
            ['id' => 'druck', 'number' => '12', 'label' => 'Druckvorlagen'],
        ];

        $demoTitles = [
            ['title' => 'Zwischen den Zeilen', 'author' => 'Lena Beispiel', 'variant' => 'plum'],
            ['title' => 'Was wäre, wenn?', 'author' => 'M. Zukunft', 'variant' => 'mint'],
            ['title' => 'Die Stadt bei Nacht', 'author' => 'Noa Muster', 'variant' => 'ink'],
            ['title' => 'Das geheime Archiv', 'author' => 'Alex Wörter', 'variant' => 'sand'],
        ];
    @endphp

    <div class="bc-review" id="design-review-top">
        <header class="bc-review-intro">
            <p class="bc-review-eyebrow">BiblioCollect · Designreview</p>
            <h1>Alle Vorlagen.<br><span>Ein Designsystem.</span></h1>
            <p>Zwölf Seitentypen und die wichtigsten gemeinsamen Komponenten – zum direkten visuellen Vergleich. Alle Medien, Personen und Vorgänge auf dieser Seite sind <strong>fiktive Beispieldaten</strong>. Es werden keine echten Ausleihen oder Verwaltungsaktionen ausgeführt.</p>
            <div class="bc-review-intro__meta">
                <span>VDBS-Designsystem</span>
                <span>12 Templates</span>
                <span>Öffentlich · Portal · Betrieb · Verwaltung</span>
            </div>
        </header>

        <div class="bc-review-layout">
            <nav class="bc-review-nav" aria-label="Designvorlagen">
                <strong>Vorlagenübersicht</strong>
                <ol>
                    @foreach ($templateLinks as $link)
                        <li><a href="#{{ $link['id'] }}"><span>{{ $link['number'] }}</span>{{ $link['label'] }}</a></li>
                    @endforeach
                </ol>
                <a class="bc-review-nav__extra" href="#bausteine">Komponenten & Farben <span aria-hidden="true">↗</span></a>
            </nav>

            <div class="bc-review-main">
                <div class="bc-review-note">
                    <strong>Neu: Interaktive Designstudie 04</strong>
                    <p><a href="{{ route('public.design-lab') }}">Fünf neue Musterseiten mit überlappenden Features, Process Stepper, Bento-Dashboard und Komponentenbibliothek ansehen →</a></p>
                </div>
                <div class="bc-review-note">
                    <strong>So überprüfst du die Entwürfe</strong>
                    <p>Vergleiche pro Vorlage Navigation, Lesbarkeit, Informationsdichte und mobile Darstellung. Mit dem Theme-Schalter im Seitenkopf kannst du auch den Dunkelmodus testen. Die Beispielschaltflächen sind absichtlich deaktiviert oder rein illustrativ.</p>
                </div>

                {{-- T01 – Öffentliche Startseite --}}
                <section class="bc-review-section" id="startseite" aria-labelledby="t01">
                    <header class="bc-review-section__heading">
                        <div><span class="bc-review-index">T01 · Öffentlich</span><h2 id="t01">Startseite</h2><p>Einladender Einstieg, eine zentrale Suche, sichtbare Angebote.</p></div>
                        <a href="{{ route('public.home') }}">Echte Startseite ansehen <span aria-hidden="true">↗</span></a>
                    </header>
                    <div class="bc-review-canvas bc-review-canvas--flush">
                        <div class="bc-review-hero-demo">
                            <div class="bc-review-hero-demo__copy">
                                <span class="bc-review-overline">Deine Schulbibliothek</span>
                                <h3>Hier beginnt <em>deine nächste</em> Geschichte.</h3>
                                <p>Finde Bücher, neue Perspektiven und Geschichten.</p>
                                <div class="bc-review-search-dummy"><span>Suche nach Titel oder Autor:in</span><span aria-hidden="true">⌕</span></div>
                            </div>
                            <div class="bc-review-hero-demo__books" aria-hidden="true">
                                <div><x-library.media-cover title="Zwischen den Zeilen" author="Lena Beispiel" variant="plum" size="hero" /></div>
                                <div><x-library.media-cover title="Was wäre, wenn?" author="M. Zukunft" variant="mint" size="hero" /></div>
                            </div>
                        </div>
                        <div class="bc-review-quick-preview"><span>Medien finden <b>↗</b></span><span>Mein Konto <b>↗</b></span><span>Buchwunsch <b>↗</b></span></div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Bleibt die Suche der wichtigste Einstieg? Sind Schriftgrößen und Hero-Proportionen auch mobil angenehm?</p>
                </section>

                {{-- T02 – öffentliche und interne Suche --}}
                <section class="bc-review-section" id="suche" aria-labelledby="t02">
                    <header class="bc-review-section__heading">
                        <div><span class="bc-review-index">T02 · Öffentlich / Betrieb</span><h2 id="t02">Suche & Filter</h2><p>Ergebnisliste mit Suchwerkzeugen und Verfügbarkeit.</p></div>
                        <a href="{{ route('public.catalog.index') }}">Katalog öffnen <span aria-hidden="true">↗</span></a>
                    </header>
                    <div class="bc-review-canvas">
                        <div class="bc-review-page-title"><small>Bibliothek / Katalog</small><h3>Medien entdecken</h3><p>Suchbegriff: „Geschichten“</p></div>
                        <div class="bc-review-toolbar">
                            <div class="bc-review-fake-search">Geschichten <span aria-hidden="true">⌕</span></div>
                            <span class="bc-review-pill bc-review-pill--active">Alle</span>
                            <span class="bc-review-pill">Bücher</span>
                            <span class="bc-review-pill">E-Medien</span>
                        </div>
                        <div class="bc-review-results">
                            <aside class="bc-review-filter" aria-label="Filterbeispiel"><strong>Filtern</strong><div><span>Medientyp</span><small>⌄</small></div><div><span>Verfügbarkeit</span><small>⌄</small></div><div><span>Themen</span><small>⌄</small></div></aside>
                            <div class="bc-review-result-list">
                                <div class="bc-review-results-top"><strong>3 Treffer</strong><span>Sortierung: Relevanz ⌄</span></div>
                                @foreach (array_slice($demoTitles, 0, 3) as $item)
                                    <div class="bc-review-result">
                                        <div><x-library.media-cover :title="$item['title']" :author="$item['author']" :variant="$item['variant']" size="small" /></div>
                                        <div><strong>{{ $item['title'] }}</strong><p>{{ $item['author'] }} · Buch</p><x-ui.badge variant="{{ $loop->first ? 'success' : 'neutral' }}">{{ $loop->first ? 'Verfügbar' : 'Bestand prüfen' }}</x-ui.badge></div>
                                        <span class="bc-review-result__arrow" aria-hidden="true">↗</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind Suche, Filter, Sortierung und Verfügbarkeit eindeutig voneinander getrennt?</p>
                </section>

                {{-- T03 – Detail --}}
                <section class="bc-review-section" id="detail" aria-labelledby="t03">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T03 · Öffentlich</span><h2 id="t03">Medien-Detailseite</h2><p>Cover, Metadaten, Exemplare und verfügbare Aktionen.</p></div></header>
                    <div class="bc-review-canvas">
                        <div class="bc-review-page-title"><small>Katalog / Buch</small><h3>Zwischen den Zeilen</h3><p>Ein fiktiver Jugendroman für die Vorschau.</p></div>
                        <div class="bc-review-detail">
                            <div class="bc-review-detail__cover"><x-library.media-cover title="Zwischen den Zeilen" author="Lena Beispiel" variant="plum" size="large" /></div>
                            <div class="bc-review-detail__body">
                                <x-ui.badge variant="success">Verfügbar</x-ui.badge>
                                <h4>Ein Buch über Freundschaft und eigene Entscheidungen</h4>
                                <dl><div><dt>Autor:in</dt><dd>Lena Beispiel</dd></div><div><dt>Medientyp</dt><dd>Buch</dd></div><div><dt>Sprache</dt><dd>Deutsch</dd></div><div><dt>Standort</dt><dd>Schulbibliothek · Regal B</dd></div></dl>
                                <div class="bc-review-demo-actions"><x-ui.button type="button" disabled>Vormerken (Demo)</x-ui.button><x-ui.button variant="secondary" type="button" disabled>Zur Merkliste (Demo)</x-ui.button></div>
                            </div>
                        </div>
                        <div class="bc-review-subhead"><strong>Exemplare</strong><span>1 von 2 verfügbar · Beispielanzeige</span></div>
                        <div class="bc-review-list-row"><span>Exemplar 01 · Standort Regal B</span><x-ui.badge variant="success">Verfügbar</x-ui.badge></div>
                        <div class="bc-review-list-row"><span>Exemplar 02 · Standort Regal B</span><x-ui.badge variant="neutral">Ausgeliehen</x-ui.badge></div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Ist sofort ersichtlich, ob das Medium nutzbar ist und wo es steht?</p>
                </section>

                {{-- T04 – Liste --}}
                <section class="bc-review-section" id="listen" aria-labelledby="t04">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T04 · Portal / Betrieb / Verwaltung</span><h2 id="t04">Listen & Tabellen</h2><p>Für Ausleihen, Konten, Bestände und Verwaltungsdaten.</p></div></header>
                    <div class="bc-review-canvas">
                        <div class="bc-review-page-title"><small>Bibliotheksbetrieb</small><h3>Aktuelle Ausleihen</h3><p>Kompakte Datenansicht mit priorisierten Statusinformationen.</p></div>
                        <div class="bc-review-toolbar"><span class="bc-review-fake-search">Ausleihen durchsuchen <span aria-hidden="true">⌕</span></span><span class="bc-review-pill">Status: Alle ⌄</span></div>
                        <x-ui.table>
                            <thead><tr><th scope="col">Medium</th><th scope="col">Ausgeliehen an</th><th scope="col">Rückgabe</th><th scope="col">Status</th></tr></thead>
                            <tbody>
                                <tr><td>Zwischen den Zeilen</td><td>Testperson A</td><td>18.10.2026</td><td><x-ui.badge variant="neutral">Aktiv</x-ui.badge></td></tr>
                                <tr><td>Die Stadt bei Nacht</td><td>Testperson B</td><td>07.10.2026</td><td><x-ui.badge variant="warning">Überfällig</x-ui.badge></td></tr>
                                <tr><td>Was wäre, wenn?</td><td>Testperson C</td><td>26.10.2026</td><td><x-ui.badge variant="success">Verlängert</x-ui.badge></td></tr>
                            </tbody>
                        </x-ui.table>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind Reihen und Zustände schnell erfassbar? Funktioniert die Tabelle auch auf schmalen Bildschirmen?</p>
                </section>

                {{-- T05 – Formular --}}
                <section class="bc-review-section" id="formulare" aria-labelledby="t05">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T05 · Alle Oberflächen</span><h2 id="t05">Formulare</h2><p>Beschriftung, Hilfetext, Validierung und eindeutige Aktionen.</p></div></header>
                    <div class="bc-review-canvas">
                        <div class="bc-review-page-title"><small>Beispiel · nicht absendbar</small><h3>Neues Medium erfassen</h3><p>Pflichtangaben klar markieren, zusätzliche Eingaben bei Bedarf ergänzen.</p></div>
                        <div class="bc-review-form">
                            <x-ui.input label="Titel" name="review_title" value="Zwischen den Zeilen" hint="Titel vom Buchcover übernehmen." disabled />
                            <x-ui.input label="Autor:in" name="review_author" value="Lena Beispiel" disabled />
                            <x-ui.select label="Medientyp" name="review_type" disabled><option>Buch</option><option>Zeitschrift</option></x-ui.select>
                            <x-ui.input label="ISBN (mit Fehlerbeispiel)" name="review_isbn" value="000" error="Bitte eine gültige ISBN eingeben." disabled />
                            <div class="bc-review-demo-actions"><x-ui.button disabled>Speichern (Demo)</x-ui.button><x-ui.button variant="secondary" disabled>Abbrechen (Demo)</x-ui.button></div>
                        </div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind Hilfetexte und Fehler eindeutig? Ist die Hauptaktion visuell erkennbar?</p>
                </section>

                {{-- T06 – Portal --}}
                <section class="bc-review-section" id="portal" aria-labelledby="t06">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T06 · Mein Portal</span><h2 id="t06">Persönliche Übersicht</h2><p>Alles Relevante für die eigene Bibliotheksnutzung.</p></div></header>
                    <div class="bc-review-canvas">
                        <div class="bc-review-page-title"><small>Mein Konto · Beispielprofil</small><h3>Guten Tag, Alex.</h3><p>Hier findest du deine Ausleihen, Vormerkungen und Listen.</p></div>
                        <div class="bc-review-stats"><div><small>Aktive Ausleihen</small><strong>3</strong></div><div><small>Vormerkungen</small><strong>1</strong></div><div><small>Merkliste</small><strong>8</strong></div></div>
                        <div class="bc-review-subhead"><strong>Deine Ausleihen</strong><span>Fiktive Kontodaten</span></div>
                        <div class="bc-review-list-row"><span>Zwischen den Zeilen <small>Rückgabe: 18.10.</small></span><x-ui.badge variant="neutral">Aktiv</x-ui.badge></div>
                        <div class="bc-review-list-row"><span>Die Stadt bei Nacht <small>Rückgabe: 07.10.</small></span><x-ui.badge variant="warning">Frist prüfen</x-ui.badge></div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind wichtige Fristen schneller zu erkennen als weniger dringende Informationen?</p>
                </section>

                {{-- T07 – POS --}}
                <section class="bc-review-section" id="scan" aria-labelledby="t07">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T07 · Bibliotheksbetrieb</span><h2 id="t07">Scanner & Ausleihe</h2><p>Arbeitsoberfläche für schnellen, fehlerarmen Bibliotheksbetrieb.</p></div></header>
                    <div class="bc-review-canvas bc-review-canvas--work">
                        <div class="bc-review-page-title"><small>Bibliotheksbetrieb / Ausleihe</small><h3>Medium ausleihen</h3><p>Scannerfokus und Rückmeldungen stehen im Vordergrund.</p></div>
                        <div class="bc-review-scanner"><div class="bc-review-scanner__icon" aria-hidden="true">▥</div><div><strong>Barcode scannen</strong><p>Barcode mit dem Scanner erfassen oder manuell eingeben.</p></div></div>
                        <div class="bc-review-form bc-review-form--inline"><x-ui.input label="Barcode" name="review_barcode" value="000123456789" disabled /><x-ui.button disabled>Übernehmen (Demo)</x-ui.button></div>
                        <x-ui.alert variant="success" title="Beispiel für eine erfolgreiche Ausleihe">Der Vorgang wurde in dieser Vorschau nicht wirklich durchgeführt.</x-ui.alert>
                        <div class="bc-review-list-row"><span>Bereit für den nächsten Scan</span><x-ui.badge variant="neutral">Scanner bereit</x-ui.badge></div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind Scannerzustand, nächster Schritt und Rückmeldung unmissverständlich?</p>
                </section>

                {{-- T08 – Assistent --}}
                <section class="bc-review-section" id="assistent" aria-labelledby="t08">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T08 · Betrieb / Verwaltung</span><h2 id="t08">Schritt-für-Schritt-Assistent</h2><p>Mehrstufige Abläufe wie Medienimport und Schuljahreswechsel.</p></div></header>
                    <div class="bc-review-canvas">
                        <div class="bc-review-page-title"><small>Medienimport · Vorschau</small><h3>Import vorbereiten</h3><p>Jeder Schritt zeigt Aufgabe und Bearbeitungsstand.</p></div>
                        <ol class="bc-review-steps"><li class="bc-review-steps__done"><span>✓</span><strong>Datei wählen</strong></li><li class="bc-review-steps__active"><span>2</span><strong>Daten prüfen</strong></li><li><span>3</span><strong>Übernehmen</strong></li></ol>
                        <x-ui.alert variant="info" title="Bitte Daten prüfen">Prüfe die erkannten Medien, bevor du den Import bestätigst. Es wurden noch keine Daten verändert.</x-ui.alert>
                        <div class="bc-review-subhead"><strong>Vorschau</strong><span>3 Beispiel-Datensätze</span></div>
                        <div class="bc-review-list-row"><span>3 Titel erkannt</span><x-ui.badge variant="success">Gültig</x-ui.badge></div>
                        <div class="bc-review-demo-actions"><x-ui.button variant="secondary" disabled>Zurück (Demo)</x-ui.button><x-ui.button disabled>Weiter (Demo)</x-ui.button></div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Wird klar, in welchem Schritt man sich befindet und ob eine Aktion Daten verändert?</p>
                </section>

                {{-- T09 – Sammlung --}}
                <section class="bc-review-section" id="sammlungen" aria-labelledby="t09">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T09 · Öffentlich / Portal</span><h2 id="t09">Sammlungen & Entdecken</h2><p>Buchreihen, Leselisten und thematische Empfehlungen.</p></div></header>
                    <div class="bc-review-canvas">
                        <div class="bc-review-page-title"><small>Leseliste · Beispiel</small><h3>Geschichten, die neugierig machen</h3><p>Eine kleine Auswahl zum Stöbern, ohne zusätzliche Bedienflächen.</p></div>
                        <div class="bc-review-covers">
                            @foreach ($demoTitles as $item)
                                <div><x-library.media-cover :title="$item['title']" :author="$item['author']" :variant="$item['variant']" size="small" /><strong>{{ $item['title'] }}</strong><small>{{ $item['author'] }}</small></div>
                            @endforeach
                        </div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind alle vier Covervarianten gut lesbar und harmonisch im selben Raster?</p>
                </section>

                {{-- T10 – Dashboard --}}
                <section class="bc-review-section" id="dashboard" aria-labelledby="t10">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T10 · Betrieb / Verwaltung</span><h2 id="t10">Dashboard & Status</h2><p>Entscheidungsrelevante Kennzahlen und nächste Aufgaben.</p></div></header>
                    <div class="bc-review-canvas">
                        <div class="bc-review-page-title"><small>Verwaltung · fiktive Zahlen</small><h3>Bibliothek im Überblick</h3><p>Wichtige Kennzahlen mit klaren Beschriftungen.</p></div>
                        <div class="bc-review-stats bc-review-stats--four"><div><small>Medien</small><strong>1.248</strong></div><div><small>Aktive Ausleihen</small><strong>87</strong></div><div><small>Vormerkungen</small><strong>12</strong></div><div><small>Offene Aufgaben</small><strong>4</strong></div></div>
                        <div class="bc-review-two-col"><div><h4>Nächste Aufgaben</h4><div class="bc-review-list-row"><span>Bestandsprüfung vorbereiten</span><span aria-hidden="true">↗</span></div><div class="bc-review-list-row"><span>4 Buchwünsche prüfen</span><span aria-hidden="true">↗</span></div></div><div><h4>Systemzustand</h4><x-ui.alert variant="success" title="Beispielstatus">Keine bekannten Störungen.</x-ui.alert></div></div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind alle Kennzahlen verständlich beschriftet und führen Aufgaben zu klaren Handlungen?</p>
                </section>

                {{-- T11 – Information --}}
                <section class="bc-review-section" id="hilfeseite" aria-labelledby="t11">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T11 · Öffentlich / Hilfe</span><h2 id="t11">Informations- & Hilfeseite</h2><p>Lesefreundliche Artikel, verständliche Anleitungen und FAQ.</p></div></header>
                    <div class="bc-review-canvas">
                        <div class="bc-review-article">
                            <small>Hilfe / Ausleihe</small><h3>Wie kann ich ein Buch verlängern?</h3>
                            <p>In deinem Bibliothekskonto siehst du alle aktiven Ausleihen. Dort kannst du prüfen, ob eine Verlängerung möglich ist.</p>
                            <h4>So geht es</h4>
                            <ol><li>Öffne dein Bibliothekskonto.</li><li>Wähle das ausgeliehene Medium.</li><li>Prüfe, ob eine Verlängerung möglich ist.</li></ol>
                            <h4>Häufige Fragen</h4>
                            <details><summary>Was passiert bei einer Vormerkung?</summary><p>Wenn ein Medium vorgemerkt ist, kann eine Verlängerung eingeschränkt sein.</p></details>
                            <details><summary>Wo finde ich meine Rückgabefrist?</summary><p>Die Rückgabefrist wird im persönlichen Bibliothekskonto angezeigt.</p></details>
                        </div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind Texte und Hilfeelemente ohne überflüssige Gestaltung gut lesbar?</p>
                </section>

                {{-- T12 – Druck --}}
                <section class="bc-review-section" id="druck" aria-labelledby="t12">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">T12 · Betrieb / Verwaltung</span><h2 id="t12">Druckvorlagen</h2><p>Ausleihbelege, Etiketten und Bibliotheksausweise.</p></div></header>
                    <div class="bc-review-canvas bc-review-canvas--print">
                        <div class="bc-review-paper">
                            <div class="bc-review-paper__logo">VDBS <span>BiblioCollect</span></div>
                            <div class="bc-review-paper__rule"></div>
                            <strong>Ausleihbeleg</strong>
                            <small>Vorschau · keine echte Ausleihe</small>
                            <dl><div><dt>Medium</dt><dd>Zwischen den Zeilen</dd></div><div><dt>Ausleihe</dt><dd>11.10.2026</dd></div><div><dt>Rückgabe</dt><dd>25.10.2026</dd></div></dl>
                            <p>Bitte bewahre diesen Beleg auf.</p>
                        </div>
                        <div class="bc-review-print-note"><strong>Druckansicht</strong><p>Eigene Abstände, klare Schrift und ohne große Navigationsflächen. Die spätere Integration muss vorhandene Druck- und Etikettenformate respektieren.</p><x-ui.badge variant="neutral">Beispiel, nicht druckverbindlich</x-ui.badge></div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind Schwarzweißdruck, Papierbreiten und Mindestschriftgrößen sinnvoll?</p>
                </section>

                {{-- Gemeinsame Komponenten und Tokens --}}
                <section class="bc-review-section" id="bausteine" aria-labelledby="review-components">
                    <header class="bc-review-section__heading"><div><span class="bc-review-index">Designsystem · Komponenten</span><h2 id="review-components">Gemeinsame Bausteine</h2><p>Farben, Schriften, Schaltflächen, Status und Beispielzustände.</p></div></header>
                    <div class="bc-review-canvas">
                        <h3 class="bc-review-block-title">Markenfarben</h3>
                        <div class="bc-review-swatches">
                            <div><span style="--review-swatch: #2DC08E"></span><strong>VDBS-Grün</strong><small>#2DC08E</small></div>
                            <div><span style="--review-swatch: #58275A"></span><strong>VDBS-Lila</strong><small>#58275A</small></div>
                            <div><span style="--review-swatch: #0F172A"></span><strong>Ink</strong><small>#0F172A</small></div>
                            <div><span style="--review-swatch: #F5F7F6"></span><strong>Neutral</strong><small>#F5F7F6</small></div>
                        </div>
                        <p class="bc-review-mini-note">Die weiteren VDBS-Gruppenfarben sind weiterhin Figuren, Blasen und Gruppenmarkierungen vorbehalten.</p>
                        <h3 class="bc-review-block-title">Typografie</h3>
                        <div class="bc-review-type"><strong>Wissen beginnt mit Neugier.</strong><p>Gut lesbare Absätze, verständliche Links und eine erkennbare Struktur – vom ersten Blick bis zum Detail.</p><small>Lato · Text und UI | Neuland · markante Akzente | Source Serif 4 · redaktionelle Zitate</small></div>
                        <h3 class="bc-review-block-title">Schaltflächen</h3>
                        <div class="bc-review-demo-actions"><x-ui.button disabled>Primär (Demo)</x-ui.button><x-ui.button variant="secondary" disabled>Sekundär (Demo)</x-ui.button><x-ui.button variant="primary" disabled>Deaktiviert (Demo)</x-ui.button></div>
                        <h3 class="bc-review-block-title">Statusanzeigen</h3>
                        <div class="bc-review-statuses"><x-ui.badge variant="success">Verfügbar</x-ui.badge><x-ui.badge variant="neutral">In Bearbeitung</x-ui.badge><x-ui.badge variant="warning">Frist beachten</x-ui.badge><x-ui.badge variant="danger">Aktion fehlgeschlagen</x-ui.badge></div>
                        <h3 class="bc-review-block-title">Hinweise</h3>
                        <div class="bc-review-alert-grid"><x-ui.alert variant="info" title="Information">Ergänzende Hinweise unterstützen den Ablauf.</x-ui.alert><x-ui.alert variant="error" title="Fehlerbeispiel">Eine wichtige Angabe ist noch offen.</x-ui.alert></div>
                        <h3 class="bc-review-block-title">Covervarianten</h3>
                        <div class="bc-review-covers">
                            @foreach ($demoTitles as $item)
                                <div><x-library.media-cover :title="$item['title']" :author="$item['author']" :variant="$item['variant']" size="small" /><strong>{{ $item['title'] }}</strong><small>{{ ucfirst($item['variant']) }}</small></div>
                            @endforeach
                        </div>
                    </div>
                    <p class="bc-review-criteria"><strong>Prüfen:</strong> Sind Komponenten über öffentliche und interne Oberflächen hinweg visuell konsistent?</p>
                </section>

                <footer class="bc-review-end">
                    <strong>Alle 12 Vorlagen geprüft?</strong>
                    <p>Notiere am besten die T-Nummern, bei denen du Änderungen möchtest. Diese Vorschauseite speichert keine Freigaben und ändert keine Produktdaten.</p>
                    <a href="#design-review-top">Zurück zum Anfang ↑</a>
                </footer>
            </div>
        </div>
    </div>
</x-app-shell>
