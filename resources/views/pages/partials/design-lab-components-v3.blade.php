{{-- Komponentengalerie v3 – reine Vorschau ohne POST-Formulare oder Datenübertragung. --}}
<section class="bc-core-gallery__section" id="bc-core-inputs-advanced" aria-labelledby="bc-core-inputs-advanced-heading">
    <x-ui.section-heading eyebrow="10 · Formulare" title="Mehrzeilige Eingaben und Vorschläge" description="Einheitliche Formfelder: Label auf Höhe des Feldes, Erklärung und Fehler immer unter dem Eingabeelement." />
    <div class="bc-core-gallery-v2__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-inputs-advanced-heading">Textarea mit Erklärung</strong><span>x-ui.textarea</span></div>
            <x-ui.textarea
                name="bc-demo-description"
                label="Beschreibung"
                :rows="4"
                :maxlength="600"
                hint="Bis zu 600 Zeichen. Lange Texte bleiben vollständig lesbar."
                value="Ein Beispieltext über Bücher, die uns neue Perspektiven eröffnen."
            />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Autocomplete mit nativer Vorschlagsliste</strong><span>x-ui.autocomplete</span></div>
            <x-ui.autocomplete
                name="bc-demo-topic"
                label="Themengebiet"
                placeholder="Thema eintippen …"
                :options="['Abenteuer', 'Biografie', 'Geschichte', 'Natur', 'Technik']"
                hint="Einige Browser bieten Vorschläge während der Eingabe an. Es erfolgt keine Netzwerkanfrage."
            />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Datei auswählen</strong><span>x-ui.file-upload</span></div>
            <x-ui.file-upload
                name="bc-demo-cover-file"
                label="Datei für ein Cover auswählen"
                accept="image/png,image/jpeg,image/webp"
                hint="PNG, JPEG oder WebP. Hier wird keine Datei übertragen oder gespeichert."
            />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Native Eingabetypen</strong><span>x-ui.input</span></div>
            <div class="bc-core-gallery-v3__field-stack">
                <x-ui.input name="bc-demo-date" type="date" label="Datum" hint="Native Datumseingabe mit derselben Formularstruktur." />
                <x-ui.input name="bc-demo-quantity" type="number" min="0" max="100" label="Anzahl" value="3" />
            </div>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.textarea&gt; · &lt;x-ui.file-upload&gt; · &lt;x-ui.autocomplete&gt;</code><span>Mit Label, Hint, optionalem Error, Disabled und ARIA-Zuordnung; keine automatischen Uploads oder Fremd-APIs.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-form-groups" aria-labelledby="bc-core-form-groups-heading">
    <x-ui.section-heading eyebrow="11 · Formularstruktur" title="Abschnitte und Fehler verständlich gruppieren" description="Semantische Feldgruppen und eine verlinkbare Fehlerübersicht für längere Formulare." />
    <div class="bc-core-gallery-v3__form-layout">
        <div>
            <x-ui.form-section title="Bibliografische Angaben" description="Zusammengehörige Felder erhalten eine gemeinsame Gruppenüberschrift.">
                <x-ui.input name="bc-demo-name" label="Titel" value="Zwischen den Zeilen" />
                <x-ui.select name="bc-demo-media-kind" label="Medientyp">
                    <option>Buch</option>
                    <option>Hörbuch</option>
                </x-ui.select>
                <x-ui.input name="bc-demo-long-author" label="Autor:in" value="A. Beispielautor:in" />
                <x-ui.input name="bc-demo-year" type="number" min="1450" max="2100" label="Erscheinungsjahr" value="2026" />
            </x-ui.form-section>
        </div>
        <div class="bc-core-gallery-v3__stack">
            <div class="bc-core-gallery__example">
                <div class="bc-core-gallery__example-head"><strong id="bc-core-form-groups-heading">Fehlerübersicht</strong><span>x-ui.error-summary</span></div>
                <x-ui.error-summary id="bc-demo-error-summary" :items="[
                    ['label' => 'Beschreibung überprüfen', 'href' => '#bc-demo-invalid-text'],
                    ['label' => 'Bitte einen zulässigen Dateityp wählen', 'href' => '#bc-demo-invalid-upload'],
                ]" />
            </div>
            <div class="bc-core-gallery__example">
                <div class="bc-core-gallery-v3__field-stack">
                    <x-ui.textarea name="bc-demo-invalid-text" label="Beschreibung (Fehlerbeispiel)" value="Ein ungültiger Wert" error="Bitte ergänze die Beschreibung." :rows="2" />
                    <x-ui.file-upload name="bc-demo-invalid-upload" label="Datei (Fehlerbeispiel)" error="Das Dateiformat wird nicht unterstützt." accept="image/png,image/jpeg" disabled />
                </div>
            </div>
        </div>
    </div>
    <p class="bc-core-gallery-v2__note">Die Fehlerübersicht verlinkt auf die betroffenen Felder. In echten Formularen braucht es zusätzlich serverseitige Validierung und eine geeignete Fokusführung nach einem fehlgeschlagenen Speicherversuch.</p>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.form-section&gt; · &lt;x-ui.error-summary&gt;</code><span>Fieldset/Legend, verständliche Meldungen, Fokusziele; ohne Änderung produktiver Validierungsregeln.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-filter-chips" aria-labelledby="bc-core-filter-chips-heading">
    <x-ui.section-heading eyebrow="12 · Filter" title="Filter sichtbar und kombinierbar" description="Aktive Filter werden als eindeutige Auswahl dargestellt – und nur bei vorhandenem Ziel als Link." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-filter-chips-heading">Filter-Chips</strong><span>x-ui.filter-chip</span></div>
        <div class="bc-core-gallery-v3__chips">
            <x-ui.filter-chip label="Alle Medien" />
            <x-ui.filter-chip label="Bücher" active />
            <x-ui.filter-chip label="Nur verfügbare Medien" active removable href="#bc-core-filter-chips" />
            <x-ui.filter-chip label="Abenteuer und fantastische Geschichten" active />
            <x-ui.filter-chip label="Filter zurücksetzen" href="#bc-core-filter-chips" />
        </div>
        <p class="bc-core-gallery-v2__note">Die Links sind hier Demonstrationsanker. Auf echten Suchseiten müssen sie Filterparameter zuverlässig ändern oder entfernen.</p>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.filter-chip&gt;</code><span>label, active, href, removable · aktive Anzeige ohne Fake-Klickfläche möglich.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-list-rows" aria-labelledby="bc-core-list-rows-heading">
    <x-ui.section-heading eyebrow="13 · Listen" title="Dieselbe Medienzeile in zwei Dichten" description="Einzeilige Metadaten und lange Titel bleiben lesbar, während Zusatzaktionen an der Seite stehen." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-list-rows-heading">Comfortable</strong><span>x-ui.list-row</span></div>
        <x-ui.list-row
            title="Zwischen den Zeilen"
            subtitle="Lena Beispiel · Jugendroman"
            meta="Regal B"
            :href="route('public.design-lab.components').'#bc-core-list-rows'"
        >
            <x-slot:media>
                <div class="bc-core-gallery-v3__mini-cover bc-core-gallery-v3__mini-cover--plum" aria-hidden="true"></div>
            </x-slot:media>
            <x-slot:trailing><x-ui.badge variant="success">Verfügbar</x-ui.badge></x-slot:trailing>
        </x-ui.list-row>
        <x-ui.list-row
            density="compact"
            title="Die außergewöhnlich lange Geschichte von der Bibliothek zwischen zwei unerwarteten Welten"
            subtitle="A. Beispielautor:in · Eine Zeile mit viel Inhalt"
            meta="Rückgabe in 7 Tagen"
        >
            <x-slot:media>
                <div class="bc-core-gallery-v3__mini-cover bc-core-gallery-v3__mini-cover--mint" aria-hidden="true"></div>
            </x-slot:media>
            <x-slot:trailing><x-ui.badge variant="warning">Bald fällig</x-ui.badge></x-slot:trailing>
        </x-ui.list-row>
        <p class="bc-core-gallery-v2__note">Die zweite Zeile verwendet Compact. Sie ist keine fertige Portalseite: Die Fälligkeitswarnung ist ein Beispielstatus.</p>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.list-row&gt;</code><span>title, subtitle, meta, href, density sowie Media-/Trailing-Slots · einsetzbar für Medien, Ausleihen und Verwaltung.</span></div>
</section>
