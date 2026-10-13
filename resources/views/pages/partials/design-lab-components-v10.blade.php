{{-- K10: Component-only examples, no saving, searching or backend mutations. --}}
<section class="bc-core-gallery__section" id="bc-core-results-overview" aria-labelledby="bc-core-results-overview-heading">
    <x-ui.section-heading eyebrow="44 · Ergebnisübersichten" title="Treffer verstehen, bevor man filtert" description="Eine universelle Ergebniszusammenfassung neben den bereits vorhandenen Filterchips, Leerzuständen und Paginierungsbausteinen." />
    <div class="bc-core-gallery-v10__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-results-overview-heading">Ergebnisse mit Suchbegriff</strong><span>x-ui.result-summary</span></div>
            <x-ui.result-summary :count="28" query="Bibliotheksgeschichte" description="Fiktive Trefferzahl für den Komponentenvergleich.">
                <x-slot:filters>
                    <x-ui.filter-chip label="Bücher" active />
                    <x-ui.filter-chip label="Nur verfügbare Medien" active />
                </x-slot:filters>
                <x-slot:actions><a href="#bc-core-pagination-size" class="bc-button bc-button--tertiary bc-button--sm">Seitenwechsel ansehen →</a></x-slot:actions>
            </x-ui.result-summary>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Ohne Treffer · kompakt</strong><span>x-ui.result-summary + x-ui.empty-state</span></div>
            <x-ui.result-summary :count="0" query="Unauffindbarer Beispieltitel" density="compact" />
            <div class="bc-core-gallery-v10__spaced">
                <x-ui.empty-state title="Keine Medien gefunden" description="Verändere die Filter oder verwende einen allgemeineren Suchbegriff." compact />
            </div>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.result-summary&gt;</code><span>count, query, label, description, density und optionale Filter-/Actions-Slots. Fachsuche und Filterzustände werden später angebunden.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-pagination-size" aria-labelledby="bc-core-pagination-size-heading">
    <x-ui.section-heading eyebrow="45 · Seitenwechsel" title="Einträge pro Seite und Pagination als ein System" description="Eine native Auswahl bestimmt künftig die Seitengröße; die bestehende Pagination übernimmt weiterhin den Seitenwechsel. Im Designlab bleiben beide Funktionen reine Vorschauen." />
    <div class="bc-core-gallery-v10__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-pagination-size-heading">Comfortable · 20 Ergebnisse</strong><span>x-ui.page-size-selector + x-ui.pagination</span></div>
            <div class="bc-core-gallery-v10__pagination">
                <x-ui.page-size-selector name="bc-demo-per-page" label="Einträge pro Seite" :value="20" :options="[10,20,50,100]" hint="Die Auswahl ist nur eine Designvorschau und startet noch keine Suchanfrage." />
                <x-ui.pagination :current="2" :total="8" previous="#bc-core-pagination-size" next="#bc-core-pagination-size" label="Beispiel einer Seitennavigation" />
            </div>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · letzte Seite</strong><span>density=compact</span></div>
            <div class="bc-core-gallery-v10__pagination">
                <x-ui.page-size-selector name="bc-demo-per-page-compact" label="Ergebnisse je Seite" :value="50" :options="[20,50,100]" density="compact" />
                <x-ui.pagination :current="4" :total="4" previous="#bc-core-pagination-size" label="Beispiel: letzte Ergebnisseite" />
            </div>
            <p class="bc-core-gallery-v10__note">Die Seitengröße ist ein natives Select. Erst ein später umschließendes GET-Formular oder ein expliziter Handler darf die Fachliste tatsächlich neu laden.</p>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.page-size-selector&gt;</code><span>name, value, options, label, hint, density. Kein verstecktes Autosubmit und keine zweite Pagination-Komponente.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-step-actions" aria-labelledby="bc-core-step-actions-heading">
    <x-ui.section-heading eyebrow="46 · Mehrstufige Abläufe" title="Fortschritt und Schrittaktionen sauber trennen" description="Der vorhandene Stepper zeigt die Position im Prozess. Ein zusätzlicher, universeller Aktionsbaustein stellt Zurück, Weiter und Abbrechen bereit." />
    <div class="bc-core-gallery-v10__stack">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-step-actions-heading">Aktiver Zwischenschritt</strong><span>x-library.process-stepper + x-ui.step-actions</span></div>
            <div class="bc-core-gallery-v10__stepper">
                <x-library.process-stepper label="Beispiel-Assistent" style="--bc-steps:4" :steps="[
                    ['label'=>'Datei auswählen','status'=>'done'],
                    ['label'=>'Daten zuordnen','status'=>'current'],
                    ['label'=>'Prüfen','status'=>'upcoming'],
                    ['label'=>'Fertig','status'=>'upcoming']
                ]" />
            </div>
            <x-ui.step-actions previous="#bc-core-step-actions" next="#bc-core-step-actions" cancel="#bc-core-navigation" previousLabel="Datei auswählen" nextLabel="Daten prüfen" label="Beispiel-Schrittaktionen" />
            <p class="bc-core-gallery-v10__note">Die Links führen nur innerhalb der Komponentenbibliothek zu Beispielen. Sie speichern nichts und wechseln keinen produktiven Assistentenschritt.</p>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Beginn und Abschluss</strong><span>Deaktivierte Richtungen</span></div>
            <div class="bc-core-gallery-v10__grid">
                <div>
                    <strong class="bc-core-gallery-v10__subheading">Erster Schritt</strong>
                    <x-ui.step-actions next="#bc-core-step-actions" cancel="#bc-core-navigation" label="Erster Beispieldurchlauf" density="compact" />
                </div>
                <div>
                    <strong class="bc-core-gallery-v10__subheading">Letzter Schritt</strong>
                    <x-ui.step-actions previous="#bc-core-step-actions" nextLabel="Abgeschlossen" label="Letzter Beispieldurchlauf" density="compact" />
                </div>
            </div>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.step-actions&gt;</code><span>previous, next, cancel, passende Beschriftungen, label, density. Fehlende Ziele sind deaktiviert und niemals href="#".</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-context-strip" aria-labelledby="bc-core-context-strip-heading">
    <x-ui.section-heading eyebrow="47 · Detailkontext" title="Auf einen Blick wissen, welchen Datensatz man bearbeitet" description="Eine schmale Kontextleiste verbindet Rücknavigation, eindeutigen Datensatztitel, strukturierte Metadaten, auffälligen Status und optionale Aktionen." />
    <div class="bc-core-gallery-v10__stack">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-context-strip-heading">Medienkontext · Comfortable</strong><span>x-ui.context-strip</span></div>
            <x-ui.context-strip title="Die außergewöhnlich lange Geschichte von einer Bibliothek zwischen zwei Welten" backHref="#bc-core-media-cards" :items="[
                ['label'=>'Barcode','value'=>'BC-00003146'],
                ['label'=>'Standort','value'=>'Regal B'],
                ['label'=>'Medientyp','value'=>'Buch']
            ]">
                <x-slot:status><x-library.due-date-status :daysRemaining="-2" compact /></x-slot:status>
                <x-slot:actions><a class="bc-button bc-button--secondary bc-button--sm" href="#bc-core-copy-rows">Exemplarzeilen →</a></x-slot:actions>
            </x-ui.context-strip>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Verfügbare Exemplare · Compact</strong><span>density=compact</span></div>
            <x-ui.context-strip title="Zwischen den Zeilen" label="Kompakte Beispieldetails" density="compact" :items="[
                ['label'=>'Exemplare','value'=>'3'],
                ['label'=>'Signatur','value'=>'B-142']
            ]">
                <x-slot:status><x-library.availability-status status="available" compact /></x-slot:status>
            </x-ui.context-strip>
            <p class="bc-core-gallery-v10__note">Metadaten sind echte dl-/dt-/dd-Paare, nicht nur typografisch getrennte Textstücke. Die Fachseiten steuern später Daten und Linkziele selbst.</p>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.context-strip&gt;</code><span>title, backHref, backLabel, label, items, density sowie Status-/Actions-Slots. Keine neue Seitentoolbar.</span></div>
</section>
