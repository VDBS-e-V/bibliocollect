{{-- Universelle Komponentenrunde K2/K3 – ohne produktive Daten und Schreibvorgänge. --}}
<section class="bc-core-gallery__section" id="bc-core-navigation" aria-labelledby="bc-core-navigation-heading">
    <x-ui.section-heading eyebrow="06 · Navigation" title="Tabs, Breadcrumbs und Seitenwechsel" description="Kleine Navigationsbausteine statt einer neuen Navigation für jede Fachseite." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-navigation-heading">Pfadnavigation und verlinkte Tabs</strong><span>x-ui.breadcrumbs / x-ui.tabs</span></div>
        <x-ui.breadcrumbs :items="[
            ['label' => 'Designlab', 'href' => route('public.design-lab')],
            ['label' => 'Komponenten', 'href' => route('public.design-lab.components')],
            ['label' => 'Navigation'],
        ]" />
        <div class="bc-core-gallery-v2__spaced">
            <x-ui.tabs label="Komponentengalerie" :items="[
                ['label' => 'Navigation', 'href' => '#bc-core-navigation', 'active' => true],
                ['label' => 'Auswahlfelder', 'href' => '#bc-core-controls'],
                ['label' => 'Ladezustände', 'href' => '#bc-core-status'],
                ['label' => 'Noch nicht verfügbar', 'disabled' => true],
            ]" />
        </div>
        <div class="bc-core-gallery-v2__spaced">
            <x-ui.pagination :current="2" :total="5" previous="#bc-core-navigation" next="#bc-core-controls" label="Beispiel einer Seitennavigation" />
        </div>
        <p class="bc-core-gallery-v2__note">Die Tabs sind hier normale Navigation mit Ankerlinks, keine ARIA-Tabpanels ohne Tastatursteuerung. Für echte Seiten verwenden wir reale URLs und keine Demo-Anker.</p>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.breadcrumbs&gt; · &lt;x-ui.tabs&gt; · &lt;x-ui.pagination&gt;</code><span>Linkziele, aktive Seite, Disabled und Responsive – alle Bausteine auch in Compact nutzbar.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-controls" aria-labelledby="bc-core-controls-heading">
    <x-ui.section-heading eyebrow="07 · Formulare" title="Checkboxen, Radios und Schalter" description="Native Bedienelemente mit einheitlicher Beschriftung. Sie funktionieren mit Tastatur und Touch." />
    <div class="bc-core-gallery-v2__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-controls-heading">Checkboxen</strong><span>x-ui.checkbox</span></div>
            <div class="bc-core-gallery-v2__stack">
                <x-ui.checkbox name="bc-demo-available" label="Nur verfügbare Medien anzeigen" checked hint="Beispielauswahl für eine Filterfunktion." />
                <x-ui.checkbox name="bc-demo-cover" label="Medien mit Cover bevorzugen" />
                <x-ui.checkbox name="bc-demo-disabled-choice" label="Nicht auswählbar" disabled />
            </div>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Radio-Gruppe</strong><span>x-ui.radio</span></div>
            <fieldset class="bc-core-gallery-v2__fieldset">
                <legend>Darstellung wählen</legend>
                <x-ui.radio name="bc-demo-display" value="list" label="Liste" checked />
                <x-ui.radio name="bc-demo-display" value="grid" label="Raster" />
                <x-ui.radio name="bc-demo-display" value="cards" label="Karten (später)" disabled />
            </fieldset>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Ein-/Ausschalten</strong><span>x-ui.switch</span></div>
            <div class="bc-core-gallery-v2__stack">
                <x-ui.switch name="bc-demo-notifications" label="Hinweise anzeigen" checked hint="Der Wechsel wirkt in dieser isolierten Vorschau noch nicht auf andere Seiten." />
                <x-ui.switch name="bc-demo-switch-disabled" label="Gesperrte Einstellung" disabled />
            </div>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Wiederverwendbares Suchfeld</strong><span>x-ui.search-field</span></div>
            <x-ui.search-field name="bc-demo-query" label="Medien durchsuchen" placeholder="Titel, Autor:in oder Thema …" hint="Designvorschau ohne Suchanfrage; die produktive Suche wird später angebunden." />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.checkbox&gt; · &lt;x-ui.radio&gt; · &lt;x-ui.switch&gt; · &lt;x-ui.search-field&gt;</code><span>Eine gemeinsame Eingabesprache; keine eigenen Farben, keine produktiven Daten.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-status" aria-labelledby="bc-core-status-heading">
    <x-ui.section-heading eyebrow="08 · Status" title="Fortschritt und Ladezustände" description="Klare Rückmeldung: bekanntes Ergebnis als Prozentwert, unbestimmte Verarbeitung ohne erfundene Fortschrittszahl." />
    <div class="bc-core-gallery-v2__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-status-heading">Bekannter Fortschritt</strong><span>x-ui.progress</span></div>
            <div class="bc-core-gallery-v2__stack">
                <x-ui.progress label="Beispiel: 24 von 40 Einträgen" :value="24" :max="40" description="60 % abgeschlossen (nur Demodaten)." />
                <x-ui.progress label="Kompakte Variante" :value="3" :max="5" compact />
            </div>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Ladeplatzhalter</strong><span>x-ui.skeleton</span></div>
            <p class="bc-core-gallery-v2__note" role="status">Beispiel: Eine Liste wird geladen.</p>
            <x-ui.skeleton :lines="4" />
            <p class="bc-core-gallery-v2__note">Skeletons sind dekorative Platzhalter. Die Statusinformation bleibt auch für Screenreader als Text vorhanden.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-disclosures" aria-labelledby="bc-core-disclosures-heading">
    <x-ui.section-heading eyebrow="09 · Detailinformationen" title="Ein- und ausklappbare Abschnitte" description="Klar strukturiert, auch ohne JavaScript nutzbar." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-disclosures-heading">Disclosure / FAQ</strong><span>x-ui.disclosure</span></div>
        <x-ui.disclosure title="Wann verwende ich ein Akkordeon?" open>
            <p>Für zusätzliche Informationen, die nicht zwingend sofort sichtbar sein müssen. Kritische Fehler und Fristen bleiben immer außerhalb versteckter Bereiche erkennbar.</p>
        </x-ui.disclosure>
        <x-ui.disclosure title="Ist diese Komponente barrierefrei bedienbar?">
            <p>Sie verwendet die nativen HTML-Elemente <code>details</code> und <code>summary</code>. Die Funktion lässt sich daher auch mit Tastatur bedienen.</p>
        </x-ui.disclosure>
        <x-ui.disclosure title="Was unterscheidet Comfortable und Compact?" compact>
            <p>Das Dichteprofil verändert Abstand und Höhe, nicht die grundlegende Lesbarkeit oder die VDBS-Marke.</p>
        </x-ui.disclosure>
    </div>
</section>
