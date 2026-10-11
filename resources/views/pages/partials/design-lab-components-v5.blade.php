{{-- Components-first K5/K6: demonstration data, no POST, no domain mutations. --}}
<section class="bc-core-gallery__section" id="bc-core-filter-toolbar" aria-labelledby="bc-core-filter-toolbar-heading">
    <x-ui.section-heading eyebrow="18 · Filtern" title="Filterleisten für jede Oberfläche" description="Ein gemeinsamer Rahmen für Suchfelder, Filterchips und Aktionen – großzügig oder kompakt, ohne Fachlogik im Baustein." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-filter-toolbar-heading">Comfortable · Suchergebnis</strong><span>x-ui.filter-toolbar</span></div>
        <x-ui.filter-toolbar title="Medien eingrenzen" :resultCount="24">
            <x-slot:filters>
                <x-ui.filter-chip label="Alle Medien" />
                <x-ui.filter-chip label="Bücher" active />
                <x-ui.filter-chip label="Verfügbar" active removable href="#bc-core-filter-toolbar" />
            </x-slot:filters>
            <x-slot:actions>
                <a class="bc-button bc-button--tertiary bc-button--sm" href="#bc-core-data-table">Tabellenbeispiele ansehen →</a>
            </x-slot:actions>
        </x-ui.filter-toolbar>
        <div class="bc-core-gallery-v5__spacing">
            <x-ui.filter-toolbar title="Verwaltungsfilter" :resultCount="8" density="compact">
                <x-slot:filters>
                    <x-ui.filter-chip label="Nur offene Vorgänge" active />
                    <x-ui.filter-chip label="Heute" />
                </x-slot:filters>
            </x-ui.filter-toolbar>
        </div>
        <p class="bc-core-gallery-v5__note">Die Trefferzahlen sind fiktiv. In einer echten Suche kommen Treffer, URL-Parameter und Filterung aus dem entsprechenden Fachbereich.</p>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-data-table" aria-labelledby="bc-core-data-table-heading">
    <x-ui.section-heading eyebrow="19 · Daten und Auswahl" title="Sortierbare Tabellenköpfe und Mehrfachauswahl" description="Die Datentabelle bleibt semantisch. Auswahlzustände sind bedienbar; Links zeigen Sortiermöglichkeiten, führen hier aber keine Datensortierung aus." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-data-table-heading">Mehrfachauswahl im Review ausprobieren</strong><span>x-ui.sort-header / x-ui.bulk-action-bar</span></div>
        <div class="bc-core-gallery-v5__selection" data-ui-bulk-demo>
            <x-ui.bulk-action-bar :selected="1" :total="3" label="Ausgewählte Medien" data-ui-bulk-bar>
                <x-slot:actions>
                    <button type="button" class="bc-button bc-button--secondary bc-button--sm" data-ui-bulk-clear>Auswahl aufheben</button>
                    <button type="button" class="bc-button bc-button--primary bc-button--sm" disabled>Aktion ausführen (Demo)</button>
                </x-slot:actions>
            </x-ui.bulk-action-bar>
            <div class="bc-core-gallery-v5__table-scroller">
                <x-ui.table>
                    <caption>Fiktive Medienauswahl. Die Sortierlinks führen nur zu dieser Beispielsektion.</caption>
                    <thead>
                        <tr>
                            <th scope="col"><x-ui.checkbox name="bc-demo-select-all" label="Alle auswählen" data-ui-select-all /></th>
                            <x-ui.sort-header label="Titel" direction="ascending" href="#bc-core-data-table" />
                            <x-ui.sort-header label="Rückgabe" href="#bc-core-data-table" />
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><x-ui.checkbox name="bc-demo-select-row[]" value="b1" label="Zwischen den Zeilen auswählen" checked data-ui-select-row /></td>
                            <th scope="row">Zwischen den Zeilen</th>
                            <td>19.10.2026</td>
                            <td><x-library.due-date-status :daysRemaining="7" compact /></td>
                        </tr>
                        <tr>
                            <td><x-ui.checkbox name="bc-demo-select-row[]" value="b2" label="Was wäre, wenn? auswählen" data-ui-select-row /></td>
                            <th scope="row">Was wäre, wenn?</th>
                            <td>20.10.2026</td>
                            <td><x-library.due-date-status :daysRemaining="8" compact /></td>
                        </tr>
                        <tr>
                            <td><x-ui.checkbox name="bc-demo-select-row[]" value="b3" label="Die außergewöhnlich lange Geschichte von einer Bibliothek zwischen den Welten auswählen" data-ui-select-row /></td>
                            <th scope="row">Die außergewöhnlich lange Geschichte von einer Bibliothek zwischen den Welten</th>
                            <td>11.10.2026</td>
                            <td><x-library.due-date-status :daysRemaining="-1" compact /></td>
                        </tr>
                    </tbody>
                </x-ui.table>
            </div>
            <p class="bc-core-gallery-v5__note">Die Checkboxen steuern ausschließlich die angezeigte Auswahlzahl. Es wird nichts an einen Server gesendet; die eigentliche Sammelaktion bleibt absichtlich deaktiviert.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-media-cards" aria-labelledby="bc-core-media-cards-heading">
    <x-ui.section-heading eyebrow="20 · Medien" title="Wiederverwendbare Medienkarten" description="Gemeinsames Cover, lange Titel, Autor:innen und Status. Als Preview sowohl großzügig als auch kompakt." />
    <div class="bc-core-gallery-v5__media-grid">
        <x-library.media-card title="Zwischen den Zeilen" author="Lena Beispiel" eyebrow="Neuer Titel" coverVariant="plum">
            <x-slot:status><x-library.availability-status status="available" detail="1 Exemplar" /></x-slot:status>
        </x-library.media-card>
        <x-library.media-card title="Die außergewöhnlich lange und wundersame Geschichte über ein verschwundenes Buch in einer sehr alten Bibliothek" author="A. Beispielautor:in" coverVariant="mint" density="compact">
            <x-slot:status><x-library.availability-status status="on-loan" compact /></x-slot:status>
        </x-library.media-card>
        <x-library.media-card title="Was wäre, wenn?" author="Nora Beispiel" coverVariant="ink" density="comfortable">
            <x-slot:status><x-library.availability-status status="reserved" detail="Vormerkung vorhanden" /></x-slot:status>
        </x-library.media-card>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.media-card&gt;</code><span>title, author, cover, coverVariant, href, density, eyebrow, Status-/Actions-Slot. Ohne eigene Datenbankzugriffe.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-library-states" aria-labelledby="bc-core-library-states-heading">
    <x-ui.section-heading eyebrow="21 · Bibliothek" title="Verfügbarkeit und Rückgabefristen" description="Farben dürfen auffallen: Grün für verfügbar, Lila für vorgemerkt, kräftiges Orange vor der Frist und klares Rot bei Überfälligkeit. Normale Ausleihen bleiben neutral – Bedeutung immer mit Text." />
    <div class="bc-core-gallery-v5__state-grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-library-states-heading">Verfügbarkeit</strong><span>x-library.availability-status</span></div>
            <div class="bc-core-gallery-v5__status-stack">
                <x-library.availability-status status="available" detail="Im Regal" />
                <x-library.availability-status status="on-loan" detail="Derzeit ausgeliehen" />
                <x-library.availability-status status="reserved" detail="Vormerkung möglich" />
                <x-library.availability-status status="unavailable" />
                <x-library.availability-status status="unknown" />
            </div>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Fälligkeit · sieben Tage Vorwarnung</strong><span>x-library.due-date-status</span></div>
            <div class="bc-core-gallery-v5__status-stack">
                <x-library.due-date-status :daysRemaining="8" />
                <x-library.due-date-status :daysRemaining="7" />
                <x-library.due-date-status :daysRemaining="1" />
                <x-library.due-date-status :daysRemaining="0" />
                <x-library.due-date-status :daysRemaining="-2" />
                <x-library.due-date-status />
            </div>
        </div>
    </div>
    <p class="bc-core-gallery-v5__note">Die kräftigen VDBS-Statusfarben sind eine bewusste Designentscheidung: 8 Tage neutral, ab 7 Tagen orange, überfällig rot. Die 7-Tage-Regel betrifft nur die visuelle Warnung; E-Mail-Erinnerungen und Ausleihlogik bleiben getrennt.</p>
</section>

<section class="bc-core-gallery__section" id="bc-core-scan-input" aria-labelledby="bc-core-scan-input-heading">
    <x-ui.section-heading eyebrow="22 · Eingaben im Betrieb" title="Scannerfeld mit einheitlicher Formularlogik" description="Ein gemeinsamer Baustein für die manuelle Barcodeeingabe oder angeschlossene Scanner. Fokus und Fehlermeldung sind klar erkennbar." />
    <div class="bc-core-gallery-v5__scan-grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-scan-input-heading">Standard</strong><span>x-library.scan-input</span></div>
            <x-library.scan-input name="bc-demo-scan-code" label="Exemplarbarcode" hint="Du kannst den Barcode manuell eingeben. Die Demo sucht nicht im Katalog." />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Fehlerzustand</strong><span>x-library.scan-input</span></div>
            <x-library.scan-input name="bc-demo-scan-error" label="Barcode prüfen" error="Dieses Format wird im Beispiel nicht erkannt." />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.scan-input&gt;</code><span>name, label, placeholder, hint, error. Die Scanhardware und Ausleihlogik werden erst in echten Workflows verbunden.</span></div>
</section>
