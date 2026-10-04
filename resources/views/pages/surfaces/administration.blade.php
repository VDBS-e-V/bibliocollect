@php($preview = $preview ?? false)

<x-app-shell surface="administration" title="Verwaltung" :preview="$preview">
    <x-ui.page-header
        kicker="Verwaltung"
        title="System und Regeln"
        lead="Konfiguration, Importe und datenschutzrelevante Aufgaben in einer ruhigen Arbeitsansicht."
    />

    <div class="bc-work-layout">
        <aside class="bc-section-nav" aria-label="Verwaltungsbereiche">
            <strong>Verwaltung</strong>
            <span aria-current="page">Übersicht</span>
            <span>Leihregeln</span>
            <span>Öffnungstage</span>
            <span>Importe</span>
            <span>Datenschutz</span>
            <span>Audit</span>
        </aside>

        <div class="bc-work-layout__main">
            <section class="bc-content-section" aria-labelledby="admin-tasks-heading">
                <div class="bc-section-heading"><h2 id="admin-tasks-heading">Bereiche</h2></div>
                <div class="bc-admin-list">
                    <div><strong>Leihregeln</strong><span>Fristen, Limits und Verlängerungen</span><span class="bc-status-text">später</span></div>
                    <div><strong>Öffnungstage</strong><span>Kalender und Bibliothekszeiten</span><span class="bc-status-text">T2</span></div>
                    <div><strong>Importe</strong><span>Schul- und Mediendaten mit Vorschau</span><span class="bc-status-text">später</span></div>
                    <div><strong>Datenschutz</strong><span>Aufbewahrung, Auskunft und Anonymisierung</span><span class="bc-status-text">T8</span></div>
                </div>
            </section>

            <section class="bc-content-section" aria-labelledby="permission-preview-heading">
                <div class="bc-section-heading"><h2 id="permission-preview-heading">Berechtigungsprinzip</h2></div>
                <x-ui.table>
                    <thead>
                        <tr><th scope="col">Rolle</th><th scope="col">Verwaltung</th><th scope="col">Grundsatz</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>Verwaltung</td><td><x-ui.badge variant="success">Zugang</x-ui.badge></td><td>Fachrechte werden einzeln vergeben</td></tr>
                        <tr><td>Technische Administration</td><td><x-ui.badge variant="success">Zugang</x-ui.badge></td><td>kein automatischer Zugriff auf Lesedaten</td></tr>
                        <tr><td>Schüler-AG</td><td><x-ui.badge>kein Standardzugang</x-ui.badge></td><td>Arbeitsrechte liegen im Bibliotheksbetrieb</td></tr>
                    </tbody>
                </x-ui.table>
            </section>
        </div>
    </div>
</x-app-shell>
