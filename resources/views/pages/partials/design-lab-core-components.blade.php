{{-- Komponenten zuerst: echte, wiederverwendbare Blade-Bausteine statt einzelner Seitenlayouts. --}}
<div class="bc-core-gallery">
    <header class="bc-core-gallery__intro">
        <span class="bc-design-lab__kicker">BiblioCollect / Designsystem · Basisbibliothek 01</span>
        <h1>Erst die Bausteine.<br><em>Dann die Seiten.</em></h1>
        <p>Eine einheitliche Komponentenbibliothek für öffentlichen Katalog, persönliches Portal, Bibliotheksbetrieb und Administration. Hier vergleichen wir Varianten und Zustände – ohne die produktiven Seiten anzupassen.</p>
        <div class="bc-core-gallery__facts" aria-label="Gestaltungsregeln">
            <span><strong>Eine</strong> VDBS-Marke</span>
            <span><strong>Zwei</strong> Dichteprofile</span>
            <span><strong>Vier</strong> Anwendungsbereiche</span>
        </div>
    </header>

    <nav class="bc-core-gallery__jump" aria-label="Komponentengruppen">
        <details class="bc-core-gallery__nav-group" data-ui-dismissable>
            <summary>Grundlagen</summary>
            <div class="bc-core-gallery__nav-links">
                <a href="#bc-core-actions">Aktionen</a>
                <a href="#bc-core-surfaces">Flächen</a>
                <a href="#bc-core-feedback">Feedback</a>
                <a href="#bc-core-details">Metadaten</a>
                <a href="#bc-core-patterns">Größere Patterns</a>
            </div>
        </details>
        <details class="bc-core-gallery__nav-group" data-ui-dismissable>
            <summary>Navigation</summary>
            <div class="bc-core-gallery__nav-links">
                <a href="#bc-core-navigation">Breadcrumbs und Tabs</a>
                <a href="#bc-core-overlays">Menüs und Hilfen</a>
                <a href="#bc-core-toolbars">Toolbars und Mobile</a>
                <a href="#bc-core-step-actions">Schrittaktionen</a>
                <a href="#bc-core-section-index">Abschnittsindex</a>
                <a href="#bc-core-context-strip">Detailleiste</a>
                <a href="#bc-core-disclosures">Akkordeon</a>
                <a href="#bc-core-dialogs">Dialoge</a>
                <a href="#bc-core-command-palette">Schnellzugriff</a>
                <a href="#bc-core-side-panel">Seitenpanel</a>
                <a href="#bc-core-confirmation-flow">Bestätigungsablauf</a>
            </div>
        </details>
        <details class="bc-core-gallery__nav-group" data-ui-dismissable>
            <summary>Formulare</summary>
            <div class="bc-core-gallery__nav-links">
                <a href="#bc-core-forms">Eingaben</a>
                <a href="#bc-core-controls">Auswahlfelder</a>
                <a href="#bc-core-inputs-advanced">Erweiterte Eingaben</a>
                <a href="#bc-core-form-groups">Formulargruppen</a>
                <a href="#bc-core-advanced-forms">Zähler und Dateien</a>
                <a href="#bc-core-date-range">Datumsbereich</a>
                <a href="#bc-core-segmented-choice">Segmentierte Auswahl</a>
                <a href="#bc-core-choice-cards">Auswahlkarten</a>
                <a href="#bc-core-combobox">Durchsuchbare Combobox</a>
                <a href="#bc-core-date-time">Datum und Uhrzeit</a>
                <a href="#bc-core-time-range">Zeitfenster</a>
                <a href="#bc-core-form-actions">Formularaktionen</a>
                <a href="#bc-core-selection-summary">Auswahlübersicht</a>
                <a href="#bc-core-inline-edit">Inline-Bearbeitung</a>
                <a href="#bc-core-scan-input">Barcodefeld</a>
            </div>
        </details>
        <details class="bc-core-gallery__nav-group" data-ui-dismissable>
            <summary>Daten und Status</summary>
            <div class="bc-core-gallery__nav-links">
                <a href="#bc-core-filter-chips">Filterchips</a>
                <a href="#bc-core-filter-toolbar">Filterleiste</a>
                <a href="#bc-core-filter-panel">Filterpanel</a>
                <a href="#bc-core-data-table">Tabellen und Auswahl</a>
                <a href="#bc-core-data-grid">Erweitertes Datengitter</a>
                <a href="#bc-core-list-rows">Listenzeilen</a>
                <a href="#bc-core-timeline">Zeitachse</a>
                <a href="#bc-core-data-viz">Datenbalken</a>
                <a href="#bc-core-record-cards">Datensatzkarten</a>
                <a href="#bc-core-status-legend">Statuslegende</a>
                <a href="#bc-core-empty-pagination">Leere Zustände & Pagination</a>
                <a href="#bc-core-results-overview">Ergebnisübersicht</a>
                <a href="#bc-core-pagination-size">Seitengröße</a>
                <a href="#bc-core-result-assistance">Ergebnishilfe</a>
                <a href="#bc-core-status">Fortschritt</a>
                <a href="#bc-core-loading">Spinner</a>
                <a href="#bc-core-busy-panel">Ladeflächen</a>
                <a href="#bc-core-notifications">Statusmeldungen</a>
            </div>
        </details>
        <details class="bc-core-gallery__nav-group" data-ui-dismissable>
            <summary>Bibliothek</summary>
            <div class="bc-core-gallery__nav-links">
                <a href="#bc-core-media-cards">Medienkarten</a>
                <a href="#bc-core-library-states">Bibliotheksstatus</a>
                <a href="#bc-core-copy-rows">Exemplare</a>
                <a href="#bc-core-patron-choice">Personenauswahl</a>
                <a href="#bc-core-scan-feedback">Scan-Feedback</a>
                <a href="#bc-core-cover-variants">Covervarianten</a>
            </div>
        </details>
        <details class="bc-core-gallery__nav-group" data-ui-dismissable>
            <summary>Verwaltung</summary>
            <div class="bc-core-gallery__nav-links">
                <a href="#bc-core-hierarchy">Hierarchien</a>
                <a href="#bc-core-week-schedule">Wochenplan</a>
                <a href="#bc-core-print-preview">Etiketten</a>
                <a href="#bc-core-message-preview">Nachrichten</a>
                <a href="#bc-core-attachment-list">Dateianlagen</a>
                <a href="#bc-core-criteria-checklist">Prüfkriterien</a>
                <a href="#bc-core-contact-card">Kontaktkarte</a>
                <a href="#bc-core-permission-matrix">Berechtigungsmatrix</a>
                <a href="#bc-core-schedule-exceptions">Sonderöffnungszeiten</a>
                <a href="#bc-core-review-overview">Bearbeitungsübersicht</a>
                <a href="#bc-core-change-review">Änderungsvergleich</a>
            </div>
        </details>
        <details class="bc-core-gallery__nav-group" data-ui-dismissable>
            <summary>Qualität</summary>
            <div class="bc-core-gallery__nav-links">
                <a href="#bc-core-review-matrix">Review-Matrix</a>
            </div>
        </details>
    </nav>

    <section class="bc-core-gallery__density" aria-labelledby="bc-core-density-heading">
        <div>
            <span class="bc-design-lab__kicker">Grundlage · Dichte</span>
            <h2 id="bc-core-density-heading">Dieselben Bausteine. Zwei Dichten.</h2>
            <p>Öffentliche Bereiche verwenden eher Comfortable. Interne Arbeitsoberflächen sind kompakt. Das persönliche Portal kombiniert beide Dichten.</p>
        </div>
        <fieldset class="bc-core-gallery__density-choices">
            <legend>Dichte der Vorschau</legend>
            <label><input type="radio" name="bc-core-density" id="bc-core-density-comfortable" value="comfortable" checked><span>Comfortable</span></label>
            <label><input type="radio" name="bc-core-density" id="bc-core-density-compact" value="compact"><span>Compact</span></label>
        </fieldset>
        <div class="bc-core-gallery__density-scene">
            <div class="bc-core-gallery__scene-header">
                <div>
                    <strong>Bibliotheksübersicht</strong>
                    <span>Beispieldaten · keine Aktionen</span>
                </div>
                <x-ui.badge variant="success">Bereit</x-ui.badge>
            </div>
            <div class="bc-core-gallery__scene-body">
                <x-ui.card as="article" tone="muted" class="bc-core-gallery__scene-tile">
                    <small>Aktive Ausleihen</small>
                    <strong>87</strong>
                    <span>Beispielwert</span>
                </x-ui.card>
                <x-ui.card as="article" tone="brand" class="bc-core-gallery__scene-tile">
                    <small>Vormerkungen</small>
                    <strong>12</strong>
                    <span>Beispielwert</span>
                </x-ui.card>
            </div>
            <div class="bc-core-gallery__scene-bottom">
                <label class="bc-core-gallery__scene-label" for="bc-core-density-search">Medien suchen</label>
                <input type="search" id="bc-core-density-search" placeholder="Titel oder Thema …" aria-describedby="bc-core-density-help">
                <small id="bc-core-density-help">Die Suche ist hier nur eine Vorschau.</small>
            </div>
        </div>
        <p class="bc-core-gallery__spec">Schaltbar im Designlab: Padding, Modulabstand, Eingabehöhe und Überschriftenhierarchie. Farben und Schriftfamilien bleiben gleich. Pixelwerte sind weiterhin Gestaltungsvorschläge.</p>
    </section>

    <section class="bc-core-gallery__section" id="bc-core-actions" aria-labelledby="bc-core-actions-heading">
        <x-ui.section-heading title="Buttons und Aktionen" eyebrow="01 · Basis" description="Klare Priorität, einheitliche Größen und eindeutige Tastaturzustände." />
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-actions-heading">Varianten</strong><span>Bestehendes x-ui.button · ergänzt</span></div>
            <div class="bc-core-gallery__button-row">
                <x-ui.button variant="primary" size="lg">Primäraktion</x-ui.button>
                <x-ui.button variant="secondary" size="md">Sekundäraktion</x-ui.button>
                <x-ui.button variant="tertiary" size="md">Tertiäraktion</x-ui.button>
                <x-ui.button variant="danger" size="md">Kritische Aktion</x-ui.button>
                <x-ui.button variant="secondary" disabled>Deaktiviert</x-ui.button>
            </div>
            <div class="bc-core-gallery__example-footer">Die Schaltflächen demonstrieren das Design. Sie führen keine Aktionen aus. In echten Seiten sind kritische Aktionen zusätzlich zu bestätigen.</div>
        </div>
        <div class="bc-core-gallery__component-note"><code>&lt;x-ui.button&gt;</code><span>variant: primary, secondary, tertiary, danger · size: sm, md, lg · href oder type</span></div>
    </section>

    <section class="bc-core-gallery__section" id="bc-core-forms" aria-labelledby="bc-core-forms-heading">
        <x-ui.section-heading title="Eingabefelder und Formularzustände" eyebrow="02 · Formulare" description="Gleiche Beschriftung, Hilfetexte und Fehlerlogik für alle Bereiche." />
        <div class="bc-core-gallery__example" aria-labelledby="bc-core-forms-heading">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-forms-heading">Normal, Hilfe, Fehler und deaktiviert</strong><span>x-ui.input / x-ui.select</span></div>
            <div class="bc-core-gallery__form-grid">
                <x-ui.input name="bc-demo-title" id="bc-demo-title" label="Medientitel" value="Zwischen den Zeilen" hint="Ein Titel darf auch mehrere Zeilen umfassen." />
                <x-ui.input name="bc-demo-isbn" id="bc-demo-isbn" label="ISBN oder Barcode" placeholder="z. B. 978..." />
                <x-ui.input name="bc-demo-error" id="bc-demo-error" label="Beispiel für einen Fehler" value="Ungültige Eingabe" error="Bitte prüfe diese Angabe." />
                <x-ui.select name="bc-demo-type" id="bc-demo-type" label="Medientyp" hint="Bitte einen Typ auswählen.">
                    <option>Buch</option><option>Hörbuch</option><option>Zeitschrift</option>
                </x-ui.select>
                <x-ui.input name="bc-demo-disabled" id="bc-demo-disabled" label="Deaktiviertes Feld" value="Nicht bearbeitbar" disabled />
            </div>
            <div class="bc-core-gallery__example-footer">Die Felder stehen direkt unter ihren Labels auf derselben Höhe. Hilfetexte und Fehlermeldungen folgen unterhalb des Feldes. Diese Beispiele übertragen keine Daten.</div>
        </div>
        <div class="bc-core-gallery__component-note"><code>&lt;x-ui.input&gt; / &lt;x-ui.select&gt;</code><span>label, hint, error, disabled · Fokus und aria-describedby</span></div>
    </section>

    <section class="bc-core-gallery__section" id="bc-core-surfaces" aria-labelledby="bc-core-surfaces-heading">
        <x-ui.section-heading title="Flächen und Abschnittsüberschriften" eyebrow="03 · Struktur" description="Ein gemeinsames Card-/Panel-Bauteil, unabhängig von der jeweiligen Fachseite." />
        <div class="bc-core-gallery__surface-grid" aria-labelledby="bc-core-surfaces-heading">
            <x-ui.card as="article" tone="default" density="comfortable">
                <span class="bc-core-gallery__tag">Standard</span>
                <h3 id="bc-core-surfaces-heading">Normale Oberfläche</h3>
                <p>Für Inhalte, Formulare oder wiederverwendbare Module.</p>
            </x-ui.card>
            <x-ui.card as="article" tone="muted" density="compact">
                <span class="bc-core-gallery__tag">Muted · Compact</span>
                <h3>Arbeitsmodul</h3>
                <p>Weniger Abstand und wenig dekorativer Schatten.</p>
            </x-ui.card>
            <x-ui.card as="article" tone="brand" density="comfortable">
                <span class="bc-core-gallery__tag">Brand soft</span>
                <h3>Wichtiger Hinweis</h3>
                <p>Eine ruhige Markenfläche für besondere Inhalte.</p>
            </x-ui.card>
            <x-ui.card as="article" tone="stage" density="comfortable">
                <span class="bc-core-gallery__tag">Stage</span>
                <h3>Starker Auftakt</h3>
                <p>Für größere Einstiegsflächen, nicht für normale Tabellen.</p>
            </x-ui.card>
        </div>
        <div class="bc-core-gallery__example bc-core-gallery__section-heading-preview">
            <x-ui.section-heading title="Medien und weitere Informationen" eyebrow="Abschnittstitel" description="Eine Überschrift mit optionaler Aktion. Die Seitenhierarchie bleibt semantisch korrekt." :level="3" compact>
                <x-slot:actions>
                    <x-ui.button variant="tertiary" size="sm" :href="route('public.design-lab.components').'#bc-core-feedback'">Weiter zu Feedback ↗</x-ui.button>
                </x-slot:actions>
            </x-ui.section-heading>
        </div>
        <div class="bc-core-gallery__component-note"><code>&lt;x-ui.card&gt; / &lt;x-ui.section-heading&gt;</code><span>tone, density, as · eyebrow, title, description, level und Actions-Slot</span></div>
    </section>

    <section class="bc-core-gallery__section" id="bc-core-feedback" aria-labelledby="bc-core-feedback-heading">
        <x-ui.section-heading title="Status, Rückmeldungen und Leerzustände" eyebrow="04 · Feedback" description="Nicht jeder neutrale Zustand ist ein Alarm. Wichtige Zustände brauchen Text und eine eindeutige Bedeutung." />
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-feedback-heading">Badges und Hinweise</strong><span>Vorhandene Systembausteine</span></div>
            <div class="bc-core-gallery__button-row">
                <x-ui.badge>Neutral</x-ui.badge>
                <x-ui.badge variant="success">Erfolgreich</x-ui.badge>
                <x-ui.badge variant="warning">Bald fällig</x-ui.badge>
                <x-ui.badge variant="danger">Überfällig</x-ui.badge>
            </div>
            <div class="bc-core-gallery__notice-grid">
                <x-library.notice-panel variant="info" title="Information"><p>Eine ergänzende Erklärung ohne unmittelbaren Handlungsdruck.</p></x-library.notice-panel>
                <x-library.notice-panel variant="warning" title="Bitte prüfen" compact><p>Ein Schritt braucht Aufmerksamkeit.</p></x-library.notice-panel>
            </div>
        </div>
        <div class="bc-core-gallery__empty-grid">
            <x-ui.empty-state title="Noch keine Medien" description="In dieser Liste sind noch keine Medien enthalten." tone="neutral">
                <x-slot:actions><x-ui.button variant="secondary" size="sm" :href="route('public.design-lab.components').'#bc-core-details'">Metadaten ansehen ↗</x-ui.button></x-slot:actions>
            </x-ui.empty-state>
            <x-ui.empty-state title="Keine Treffer gefunden" description="Verändere deine Suchbegriffe oder setze die Filter zurück." tone="brand" compact />
        </div>
        <div class="bc-core-gallery__component-note"><code>&lt;x-ui.empty-state&gt;</code><span>title, description, tone, compact und optionaler Actions-Slot · wiederverwendbar in Portal, Suche und Verwaltung</span></div>
    </section>

    <section class="bc-core-gallery__section" id="bc-core-details" aria-labelledby="bc-core-details-heading">
        <x-ui.section-heading title="Metadaten und Tabellen" eyebrow="05 · Daten" description="Gleiche Datenbeschreibung in Medien-, Personen- und Verwaltungsansichten." />
        <div class="bc-core-gallery__data-grid">
            <div class="bc-core-gallery__example">
                <div class="bc-core-gallery__example-head"><strong id="bc-core-details-heading">Beschreibungsliste</strong><span>x-ui.description-list</span></div>
                <x-ui.description-list density="compact" :items="[
                    ['label' => 'Medientitel', 'value' => 'Eine außergewöhnlich lange Geschichte über Bücher und Entdeckungen'],
                    ['label' => 'Sprache', 'value' => 'Deutsch'],
                    ['label' => 'Standort', 'value' => 'Bibliothek / Regal B'],
                    ['label' => 'Verfügbarkeit', 'value' => 'Ausgeliehen'],
                ]"/>
            </div>
            <div class="bc-core-gallery__example">
                <div class="bc-core-gallery__example-head"><strong>Datentabelle</strong><span>Bestehendes x-ui.table</span></div>
                <x-ui.table>
                    <thead><tr><th scope="col">Medien</th><th scope="col">Fällig</th><th scope="col">Status</th></tr></thead>
                    <tbody>
                        <tr><th scope="row">Zwischen den Zeilen</th><td>19.10.</td><td><x-ui.badge variant="warning">Bald fällig</x-ui.badge></td></tr>
                        <tr><th scope="row">Was wäre, wenn?</th><td>26.10.</td><td><x-ui.badge>Ausgeliehen</x-ui.badge></td></tr>
                        <tr><th scope="row">Die Stadt bei Nacht</th><td>10.10.</td><td><x-ui.badge variant="danger">Überfällig</x-ui.badge></td></tr>
                    </tbody>
                </x-ui.table>
            </div>
        </div>
        <div class="bc-core-gallery__component-note"><code>&lt;x-ui.description-list&gt; / &lt;x-ui.table&gt;</code><span>Metadaten, Tabellenkopf und statusbezogene Zellen; ohne Bindung an Fachlogik</span></div>
    </section>

    @include('pages.partials.design-lab-components-v2')

    @include('pages.partials.design-lab-components-v3')

    @include('pages.partials.design-lab-components-v4')

    @include('pages.partials.design-lab-components-v5')

    @include('pages.partials.design-lab-components-v6')

    @include('pages.partials.design-lab-components-v7')

    @include('pages.partials.design-lab-components-v8')

    @include('pages.partials.design-lab-components-v9')

    @include('pages.partials.design-lab-components-v10')

    @include('pages.partials.design-lab-components-v11')
    @include('pages.partials.design-lab-components-v12')
    @include('pages.partials.design-lab-components-v13')
    @include('pages.partials.design-lab-components-v14')
    @include('pages.partials.design-lab-components-v15')

    <section class="bc-core-gallery__section bc-core-gallery__section--final" id="bc-core-patterns">
        <span class="bc-design-lab__kicker">Nächste Ebene</span>
        <h2>Aus Basisbausteinen werden Patterns.</h2>
        <p>Darunter bleiben unsere bereits entwickelten größeren Bausteine: Feature Overlap, Process Stepper, Bento-Metriken, Meldungen und Mediencover. Sie setzen dieselben universellen Regeln ein.</p>
    </section>
</div>
