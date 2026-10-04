@php($preview = $preview ?? false)

<x-app-shell surface="portal" title="Mein Konto" :preview="$preview">
    <x-ui.page-header
        kicker="Mein Konto"
        title="Übersicht"
        lead="Eigene Ausleihen, Vormerkungen und Kontodaten auf einen Blick."
    />

    <div class="bc-work-layout">
        <aside class="bc-section-nav" aria-label="Kontobereiche">
            <strong>Mein Konto</strong>
            <span aria-current="page">Übersicht</span>
            <span>Ausleihen</span>
            <span>Vormerkungen</span>
            <span>Persönliche Daten</span>
        </aside>

        <div class="bc-work-layout__main">
            <section class="bc-content-section" aria-labelledby="loan-heading">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="loan-heading">Aktuelle Ausleihen</h2>
                    <span>0 Medien</span>
                </div>
                <x-ui.table>
                    <thead>
                        <tr><th scope="col">Titel</th><th scope="col">Fällig</th><th scope="col">Status</th><th scope="col">Aktion</th></tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4" class="bc-table__empty">Noch keine Fachdaten vorhanden. Die Ausleihübersicht folgt mit Circulation.</td></tr>
                    </tbody>
                </x-ui.table>
            </section>

            <section class="bc-content-section" aria-labelledby="reservation-heading">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="reservation-heading">Vormerkungen</h2>
                    <span>0 Vormerkungen</span>
                </div>
                <p class="bc-section-copy">Zeitraumsvormerkungen und Click & Collect werden hier getrennt und nachvollziehbar angezeigt.</p>
            </section>

            <x-ui.alert title="Datenschutz">
                Dieses Portal zeigt nur die eigenen Bibliotheksvorgänge. Eine Lehrerrolle erhält dadurch keinen Zugriff auf Ausleihen von Schüler:innen.
            </x-ui.alert>
        </div>
    </div>
</x-app-shell>
