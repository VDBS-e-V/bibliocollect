{{-- Komponentenrunde K8: universelle Präsentation, bewusst keine Formulare mit Schreibfunktion. --}}
<section class="bc-core-gallery__section" id="bc-core-toolbars" aria-labelledby="bc-core-toolbars-heading">
    <x-ui.section-heading eyebrow="32 · Arbeitsnavigation" title="Seitentoolbars und mobile Navigation" description="Einheitliche Titel, Nebeninformationen und Aktionen – großzügig für öffentliche Bereiche, kompakt für interne Arbeitsflächen." />
    <div class="bc-core-gallery-v8__stack">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-toolbars-heading">Comfortable mit Aktionen</strong><span>x-ui.page-toolbar</span></div>
            <x-ui.page-toolbar title="Medien verwalten" description="Eine ruhige Toolbar mit einer klaren Hauptaktion und einer zurückhaltenden Nebenaktion.">
                <x-slot:actions>
                    <a class="bc-button bc-button--secondary" href="#bc-core-filter-panel">Filter ansehen</a>
                    <a class="bc-button bc-button--primary" href="#bc-core-advanced-forms">Eingaben ansehen →</a>
                </x-slot:actions>
            </x-ui.page-toolbar>
            <div class="bc-core-gallery-v8__spacing">
                <x-ui.page-toolbar title="Bibliotheksbetrieb" description="Kompaktes Dichteprofil" density="compact">
                    <x-slot:actions>
                        <a class="bc-button bc-button--tertiary bc-button--sm" href="#bc-core-busy-panel">Ladestatus prüfen</a>
                    </x-slot:actions>
                </x-ui.page-toolbar>
            </div>
        </div>
        <div class="bc-core-gallery-v8__two-col">
            <div class="bc-core-gallery__example">
                <div class="bc-core-gallery__example-head"><strong>Mobile Menüstruktur</strong><span>x-ui.mobile-navigation</span></div>
                <x-ui.mobile-navigation label="Bereiche öffnen" :items="[
                    ['label' => 'Medien', 'href' => '#bc-core-media-cards', 'active' => true],
                    ['label' => 'Ausleihfristen', 'href' => '#bc-core-library-states'],
                    ['label' => 'Tabellen', 'href' => '#bc-core-data-table'],
                    ['label' => 'Noch nicht verfügbar', 'disabled' => true],
                ]" />
                <p class="bc-core-gallery-v8__note">Aufklappen ohne JavaScript; Escape und Außenklick schließen das Menü mit der vorhandenen Komponentensteuerung. Keine produktiven Navigationsziele.</p>
            </div>
            <div class="bc-core-gallery__example">
                <div class="bc-core-gallery__example-head"><strong>Lange Beschriftungen</strong><span>Navigation · Longtext</span></div>
                <x-ui.mobile-navigation label="Organisation und Bibliotheksverwaltung" :items="[
                    ['label' => 'Medien mit besonders umfangreichen bibliografischen Beschreibungen', 'href' => '#bc-core-media-cards'],
                    ['label' => 'Exemplare, Signaturen und Rückgabefristen', 'href' => '#bc-core-copy-rows'],
                ]" />
                <p class="bc-core-gallery-v8__note">Der Text wird umbrochen, statt über den Bildschirmrand zu laufen.</p>
            </div>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-filter-panel" aria-labelledby="bc-core-filter-panel-heading">
    <x-ui.section-heading eyebrow="33 · Erweiterte Filter" title="Eine Filterseitenleiste, die auch mobil funktioniert" description="Ein aufklappbarer Filterbereich mit Auswahlfeldern, Suchfeld und aktiven Filtern. Er lässt sich ohne JavaScript und ohne versteckte Pflichtangaben bedienen." />
    <div class="bc-core-gallery-v8__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-filter-panel-heading">Filterbereich geöffnet</strong><span>x-ui.filter-panel</span></div>
            <x-ui.filter-panel title="Medien eingrenzen" :count="2" open clearHref="#bc-core-filter-panel">
                <x-ui.search-field name="bc-review-filter-text" label="Suchbegriff" placeholder="Titel oder Thema …" />
                <fieldset class="bc-core-gallery-v8__fieldset">
                    <legend>Medientyp</legend>
                    <x-ui.checkbox name="bc-review-book" label="Buch" checked />
                    <x-ui.checkbox name="bc-review-audio" label="Hörbuch" />
                </fieldset>
                <fieldset class="bc-core-gallery-v8__fieldset">
                    <legend>Verfügbarkeit</legend>
                    <x-ui.checkbox name="bc-review-available" label="Nur verfügbare Medien" checked />
                </fieldset>
            </x-ui.filter-panel>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Geschlossener Filterbereich</strong><span>Collapsed</span></div>
            <x-ui.filter-panel title="Zusätzliche Kriterien" :count="1">
                <x-ui.checkbox name="bc-review-cover" label="Mit Coverabbildung" checked />
                <x-ui.checkbox name="bc-review-recent" label="Neuzugänge" />
            </x-ui.filter-panel>
            <p class="bc-core-gallery-v8__note">Die Checkboxen verändern nur die Demo-Auswahl. Reale Suchparameter, Filterzählung und Zurücksetzen werden später durch das Fachsystem bereitgestellt.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-advanced-forms" aria-labelledby="bc-core-advanced-forms-heading">
    <x-ui.section-heading eyebrow="34 · Formulare" title="Zeichenzähler und Dateiauswahl mit Rückmeldung" description="Die vorhandene Textarea erhält einen optionalen Zeichenzähler. Eine neue Dateifläche unterstützt Klick, Tastatur und Drag-and-drop, ohne Daten zu übertragen." />
    <div class="bc-core-gallery-v8__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-advanced-forms-heading">Mehrzeilige Eingabe mit Zähler</strong><span>x-ui.textarea · showCount</span></div>
            <x-ui.textarea name="bc-review-summary" label="Kurze Medienbeschreibung" value="Dieses Beispiel kann direkt verändert werden." :maxlength="180" :rows="5" showCount hint="Die Anzahl der Zeichen wird während der Eingabe aktualisiert." />
            <p class="bc-core-gallery-v8__note">Die Anzahl ist mit dem Feld verknüpft. Ohne JavaScript bleibt der initiale Zähler und die native Maximalbegrenzung erhalten.</p>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Datei lokal auswählen</strong><span>x-ui.file-drop-zone</span></div>
            <x-ui.file-drop-zone name="bc-review-example-file" label="Coverabbildung auswählen" accept=".png,.jpg,.jpeg,image/webp" hint="PNG, JPG oder WebP. Nur eine lokale Dateivorschau; kein Upload." />
            <p class="bc-core-gallery-v8__note">Die Auswahl wird angezeigt, aber weder hochgeladen noch gespeichert. Die Prüfung von Dateityp und Dateigröße auf dem Server bleibt später verpflichtend.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-timeline" aria-labelledby="bc-core-timeline-heading">
    <x-ui.section-heading eyebrow="35 · Abläufe" title="Zeitliche Ereignisse und nachvollziehbare Veränderungen" description="Ein gemeinsames Muster für Statushistorie, Bestandsbewegungen und Verwaltungsaktivitäten – chronologisch und mit lesbaren Zeitangaben." />
    <div class="bc-core-gallery-v8__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-timeline-heading">Verlauf mit unterschiedlichen Zuständen</strong><span>x-ui.activity-timeline</span></div>
            <x-ui.activity-timeline title="Fiktive Exemplarhistorie" :items="[
                ['title' => 'Medium aufgenommen', 'datetime' => '2026-10-01', 'dateLabel' => '1. Oktober', 'description' => 'Ein Beispielmedium wurde in den Bestand aufgenommen.', 'status' => 'success'],
                ['title' => 'Ausleihe begonnen', 'datetime' => '2026-10-04', 'dateLabel' => '4. Oktober', 'description' => 'Eine fiktive Ausleihe wurde begonnen.'],
                ['title' => 'Rückgabe nähert sich', 'datetime' => '2026-10-12', 'dateLabel' => '12. Oktober', 'description' => 'Die visuelle 7-Tage-Warnung ist erreicht.', 'status' => 'warning'],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Kompakter Bearbeitungsverlauf</strong><span>Compact</span></div>
            <x-ui.activity-timeline title="Fiktiver Bearbeitungsverlauf" compact :items="[
                ['title' => 'Datensatz geprüft', 'dateLabel' => '09:15', 'status' => 'success'],
                ['title' => 'Angabe fehlt', 'dateLabel' => '09:18', 'status' => 'error', 'description' => 'Ein fehlender Wert wurde als Problem markiert.'],
                ['title' => 'Manuelle Prüfung ausstehend', 'dateLabel' => '09:20', 'status' => 'warning'],
            ]" />
            <p class="bc-core-gallery-v8__note">Farbpunkte unterstützen den Verlauf, aber Titel und Beschreibungen vermitteln den Status auch ohne Farbe.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-data-viz" aria-labelledby="bc-core-data-viz-heading">
    <x-ui.section-heading eyebrow="36 · Datenvisualisierung" title="Bunte Datenbalken mit immer lesbarer Tabelle" description="Zahlen dürfen kräftige VDBS-Farben verwenden. Die Grafik ist zusätzlich als reguläre HTML-Datentabelle vorhanden." />
    <div class="bc-core-gallery-v8__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-data-viz-heading">Medien nach Kategorie</strong><span>x-ui.metric-bars</span></div>
            <x-ui.metric-bars title="Fiktive Verteilung" :items="[
                ['label' => 'Bücher', 'value' => 28, 'tone' => 'green'],
                ['label' => 'Hörbücher', 'value' => 12, 'tone' => 'purple'],
                ['label' => 'Zeitschriften', 'value' => 9, 'tone' => 'orange'],
                ['label' => 'Andere', 'value' => 5, 'tone' => 'blue'],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Lange Kategorienamen</strong><span>Mobile / Longtext</span></div>
            <x-ui.metric-bars title="Fiktive Bestandsgruppen" :max="25" :items="[
                ['label' => 'Medien- und Bibliotheksgeschichte', 'value' => 21, 'tone' => 'purple'],
                ['label' => 'Gesellschaftliche Entwicklungen', 'value' => 15, 'tone' => 'green'],
                ['label' => 'Andere Themen', 'value' => 8, 'tone' => 'orange'],
            ]" />
            <p class="bc-core-gallery-v8__note">Diagramme sind reine Darstellung. Werte kommen später ausschließlich aus berechtigten Fach-Queries; die Tabelle bleibt dauerhaft sichtbar.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-busy-panel" aria-labelledby="bc-core-busy-panel-heading">
    <x-ui.section-heading eyebrow="37 · Laden und Ergebnis" title="Einheitlicher Ladezustand statt leerer Flächen" description="Unbestimmtes Laden, abgeschlossener Zustand und ein kompakter Platzhalter sind im selben Baustein abgebildet." />
    <div class="bc-core-gallery-v8__three-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-busy-panel-heading">Laden · Comfortable</strong><span>x-ui.busy-panel</span></div>
            <x-ui.busy-panel title="Medien werden geladen · Demo" description="Die Daten sind hier nicht wirklich in Bearbeitung." />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Laden · Compact</strong><span>compact</span></div>
            <x-ui.busy-panel title="Status wird geprüft · Demo" compact />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Fertig / Inhalt verfügbar</strong><span>busy=false</span></div>
            <x-ui.busy-panel title="Daten verfügbar" :busy="false">
                <p>Das Beispiel zeigt, wie Inhalte nach dem Laden erscheinen können.</p>
                <x-library.availability-status status="available" detail="Demo" />
            </x-ui.busy-panel>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-review-matrix" aria-labelledby="bc-core-review-matrix-heading">
    <x-ui.section-heading eyebrow="38 · Qualitätsreview" title="Dieselben Komponenten in allen wichtigen Zuständen prüfen" description="Diese Review-Matrix dokumentiert die noch notwendige manuelle Sichtprüfung. Ein Häkchen hier ist nur lokal in dieser Vorschau – keine automatische Freigabe." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-review-matrix-heading">Prüfliste für unser gemeinsames Review</strong><span>K8 · manuell</span></div>
        <div class="bc-core-gallery-v8__review">
            <fieldset>
                <legend>Ansichtsgrößen</legend>
                <label><input type="checkbox"> 320 px – keine abgeschnittenen Bedienelemente</label>
                <label><input type="checkbox"> 375 px – lange Titel und Hilfetexte lesbar</label>
                <label><input type="checkbox"> 768 px – Tablet-Umbruch</label>
                <label><input type="checkbox"> 1280 px – Desktop-Dichte und Hierarchie</label>
            </fieldset>
            <fieldset>
                <legend>Bedienung und Barrierefreiheit</legend>
                <label><input type="checkbox"> Tastatur: Tab, Enter, Space und Escape</label>
                <label><input type="checkbox"> Dunkelmodus: Farbkontrast und Fokus</label>
                <label><input type="checkbox"> Zoom 200–400 % und Textvergrößerung</label>
                <label><input type="checkbox"> Screenreader: Label, Status und Reihenfolge</label>
            </fieldset>
        </div>
        <p class="bc-core-gallery-v8__note">Diese Liste ersetzt keine automatisierten Tests. Die endgültige Freigabe erfolgt erst nach gemeinsamem Review und Prüfung der tatsächlichen Browserdarstellung.</p>
    </div>
</section>
