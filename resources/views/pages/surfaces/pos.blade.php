@php($preview = $preview ?? false)

<x-app-shell surface="pos" title="Bibliotheksbetrieb" :preview="$preview">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Ausleihe und Rückgabe"
        lead="Arbeitsoberfläche für Barcode-Scanner und Tastatur."
    />

    <div class="bc-pos-toolbar" role="toolbar" aria-label="Vorgangsart">
        <button type="button" class="bc-pos-toolbar__item bc-pos-toolbar__item--active" disabled>Ausleihe</button>
        <button type="button" class="bc-pos-toolbar__item" disabled>Rückgabe</button>
        <button type="button" class="bc-pos-toolbar__item" disabled>Verlängern</button>
        <button type="button" class="bc-pos-toolbar__item" disabled>Abholen</button>
    </div>

    <div class="bc-pos-layout">
        <section class="bc-work-panel" aria-labelledby="scan-heading">
            <div class="bc-section-heading">
                <h2 id="scan-heading">Scannen</h2>
            </div>
            <x-ui.input
                label="Bibliotheksnummer oder Medienbarcode"
                name="barcode-preview"
                hint="Der Scanner arbeitet wie eine Tastatur. Die Verarbeitung folgt in T4."
                placeholder="Barcode scannen oder Nummer eingeben"
                disabled
            />
            <div class="bc-action-row">
                <x-ui.button disabled>Übernehmen</x-ui.button>
                <x-ui.button variant="secondary" disabled>Vorgang leeren</x-ui.button>
            </div>
        </section>

        <aside class="bc-work-panel" aria-labelledby="status-heading">
            <div class="bc-section-heading"><h2 id="status-heading">Arbeitsstatus</h2></div>
            <dl class="bc-definition-list">
                <div><dt>Vorgang</dt><dd>Ausleihe</dd></div>
                <div><dt>Person</dt><dd>nicht gewählt</dd></div>
                <div><dt>Medien</dt><dd>0</dd></div>
                <div><dt>System</dt><dd><x-ui.badge>Vorschau</x-ui.badge></dd></div>
            </dl>
        </aside>
    </div>

    <section class="bc-content-section" aria-labelledby="transaction-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="transaction-heading">Aktueller Vorgang</h2>
            <span>keine Positionen</span>
        </div>
        <x-ui.table>
            <thead><tr><th scope="col">Barcode</th><th scope="col">Medium</th><th scope="col">Status</th></tr></thead>
            <tbody><tr><td colspan="3" class="bc-table__empty">Medien erscheinen nach dem Scan in dieser Liste.</td></tr></tbody>
        </x-ui.table>
    </section>
</x-app-shell>
