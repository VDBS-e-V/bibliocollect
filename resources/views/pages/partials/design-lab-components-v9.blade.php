{{-- K9: vier universelle Blade-Komponenten und Review bestehender Leere-/Pagination-Zustände. --}}
<section class="bc-core-gallery__section" id="bc-core-date-range" aria-labelledby="bc-core-date-range-heading">
    <x-ui.section-heading eyebrow="39 · Datumsfelder" title="Zeiträume ohne verschobene Eingabefelder" description="Ein gemeinsames Datumsbereich-Pattern für Bestandslisten, Berichte und Termine. Labels oben, Hinweise unter den Feldern – passend zum bestätigten Formularsystem." />
    <div class="bc-core-gallery-v9__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-date-range-heading">Standard: zwei echte Datumsfelder</strong><span>x-ui.date-range</span></div>
            <x-ui.date-range name="bc-demo-period" label="Ausleihzeitraum" from="2026-10-01" to="2026-10-31" hint="Datumseingaben bleiben auch auf dem Handy einzeln gut bedienbar." />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact und Fehlerzustand</strong><span>density=compact</span></div>
            <x-ui.date-range name="bc-demo-period-error" label="Berichtszeitraum" from="2026-10-18" to="2026-10-10" error="Das Enddatum muss nach dem Anfangsdatum liegen." density="compact" />
            <p class="bc-core-gallery-v9__note">Diese Fehlermeldung ist eine feste Designvorschau. Reale Validierung erfolgt später serverseitig.</p>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.date-range&gt;</code><span>name, label, from, to, hint, error, min, max, density. Labels und Hilfetexte mit echten IDs.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-segmented-choice" aria-labelledby="bc-core-segmented-choice-heading">
    <x-ui.section-heading eyebrow="40 · Ansichten" title="Segmentierte Auswahl ohne zusätzliche JavaScript-Abhängigkeit" description="Die kompakte Alternative zu einer langen Reihe von Radiobuttons. Zustand und Tastaturbedienung kommen weiterhin von nativen Radiofeldern." />
    <div class="bc-core-gallery-v9__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-segmented-choice-heading">Comfortable: Ansichtsmodus</strong><span>x-ui.segmented-choice</span></div>
            <x-ui.segmented-choice name="bc-demo-mode" label="Ansicht wählen" selected="grid" hint="Zwischen den Optionen kann mit den Pfeiltasten gewechselt werden." :options="[
                ['value' => 'grid', 'label' => 'Kacheln'],
                ['value' => 'list', 'label' => 'Liste'],
                ['value' => 'table', 'label' => 'Tabelle'],
            ]" />
            <p class="bc-core-gallery-v9__note">Die Auswahl verändert nur den gewählten Radiostatus, nicht den Inhalt der Beispielseite.</p>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact mit deaktivierter Option</strong><span>density=compact</span></div>
            <x-ui.segmented-choice name="bc-demo-density" label="Darstellungsdichte" density="compact" selected="standard" :options="[
                ['value' => 'standard', 'label' => 'Standard'],
                ['value' => 'compact', 'label' => 'Kompakt'],
                ['value' => 'special', 'label' => 'Nicht verfügbar', 'disabled' => true],
            ]" />
            <p class="bc-core-gallery-v9__note">Ein deaktiver Eintrag ist visuell reduziert und nicht auswählbar.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-record-cards" aria-labelledby="bc-core-record-cards-heading">
    <x-ui.section-heading eyebrow="41 · Datenansichten" title="Datensatzkarten als Alternative zu breiten Tabellen" description="Mehrspaltige Fach- und Verwaltungstabellen können auf kleinen Bildschirmen in lesbare, beschriftete Datensatzkarten wechseln." />
    <div class="bc-core-gallery-v9__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-record-cards-heading">Comfortable und Status</strong><span>x-ui.record-card</span></div>
            <x-ui.record-card title="Zwischen den Zeilen" eyebrow="Exemplar · BC-00003145" :items="[
                ['label' => 'Standort', 'value' => 'Regal B · 3. Reihe'],
                ['label' => 'Ausgeliehen seit', 'value' => '4. Oktober 2026'],
                ['label' => 'Rückgabe', 'value' => '19. Oktober 2026'],
                ['label' => 'Medientyp', 'value' => 'Buch'],
            ]">
                <x-slot:status><x-library.due-date-status :daysRemaining="7" compact /></x-slot:status>
                <x-slot:actions><a href="#bc-core-data-table" class="bc-button bc-button--tertiary bc-button--sm">Zur Tabellenansicht →</a></x-slot:actions>
            </x-ui.record-card>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact mit sehr langem Titel</strong><span>density=compact</span></div>
            <x-ui.record-card title="Eine außerordentlich lange bibliografische Beschreibung über die Geschichte sehr unterschiedlicher Bibliotheken und ihre Bestände" eyebrow="Datensatz" density="compact" :items="[
                ['label' => 'Signatur', 'value' => 'SACH/ME/2026/01429'],
                ['label' => 'Kategorie', 'value' => 'Medien- und Bibliotheksgeschichte'],
                ['label' => 'Verantwortlich', 'value' => 'Arbeitsgruppe Bestandsprüfung'],
                ['label' => 'Status', 'value' => 'Noch in Bearbeitung'],
            ]" />
            <p class="bc-core-gallery-v9__note">Keine abgeschnittenen Titel und kein zwangsweise horizontales Scrollen. Die Karten sind ohne eigene Datenbanklogik nutzbar.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-status-legend" aria-labelledby="bc-core-status-legend-heading">
    <x-ui.section-heading eyebrow="42 · Farbsemantik" title="Eine gemeinsame, bewusst bunte Statuslegende" description="Kräftige VDBS-Farben dürfen wichtige Zustände hervorheben. Zur Sicherheit steht die Bedeutung immer auch als Text dabei." />
    <div class="bc-core-gallery-v9__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-status-legend-heading">Bibliotheksstatus</strong><span>x-ui.status-legend</span></div>
            <x-ui.status-legend label="Legende der Ausleihzustände" :items="[
                ['tone' => 'green', 'label' => 'Verfügbar', 'description' => 'direkt nutzbar'],
                ['tone' => 'purple', 'label' => 'Vorgemerkt'],
                ['tone' => 'orange', 'label' => 'Bald fällig', 'description' => 'ab sieben Tagen'],
                ['tone' => 'red', 'label' => 'Überfällig'],
                ['tone' => 'neutral', 'label' => 'Unbekannt'],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Kompakt für Datenberichte</strong><span>Compact</span></div>
            <x-ui.status-legend label="Legende der Datenkategorien" density="compact" :items="[
                ['tone' => 'green', 'label' => 'Bücher'],
                ['tone' => 'purple', 'label' => 'Hörbücher'],
                ['tone' => 'orange', 'label' => 'Zeitschriften'],
                ['tone' => 'blue', 'label' => 'Weitere Medien'],
            ]" />
            <p class="bc-core-gallery-v9__note">Die Farbsymbole sind dekorativ; alle Kategorien sind als Text zugänglich.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-empty-pagination" aria-labelledby="bc-core-empty-pagination-heading">
    <x-ui.section-heading eyebrow="43 · Bestehende Bausteine" title="Leere Ergebnisse und Pagination sauber kombinieren" description="Diese zwei Komponenten gab es bereits. Wir überprüfen sie gemeinsam mit den neuen Datenkarten, statt doppelte Implementierungen anzulegen." />
    <div class="bc-core-gallery-v9__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-empty-pagination-heading">Keine Treffer – klare nächste Schritte</strong><span>x-ui.empty-state</span></div>
            <x-ui.empty-state title="Keine passenden Medien gefunden" description="Versuche weniger Filter oder einen anderen Suchbegriff." tone="brand">
                <x-slot:actions>
                    <a class="bc-button bc-button--secondary" href="#bc-core-filter-panel">Filterbeispiele ansehen</a>
                </x-slot:actions>
            </x-ui.empty-state>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Seitenwechsel – mit echten Linkzielen</strong><span>x-ui.pagination</span></div>
            <x-ui.pagination :current="2" :total="5" previous="#bc-core-empty-pagination" next="#bc-core-empty-pagination" label="Beispiel-Seitennavigation" />
            <p class="bc-core-gallery-v9__note">Die Links führen in dieser Vorschau zum gleichen Abschnitt. In realen Listen müssen URLs und Datensätze zur jeweiligen Seite passen.</p>
            <div class="bc-core-gallery-v9__spacing">
                <x-ui.pagination :current="1" :total="1" label="Einseitiges Beispiel" />
            </div>
        </div>
    </div>
</section>
