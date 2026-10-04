@php($preview = $preview ?? false)

<x-app-shell surface="administration" title="Verwaltung" :preview="$preview">
    <x-ui.page-header
        kicker="Administration"
        title="Verwaltung"
        lead="Datenreiche Aufgaben bleiben ruhig, klar strukturiert und durch Einzelberechtigungen abgesichert."
    />

    <div class="mt-7 grid gap-4 md:grid-cols-3">
        <x-ui.card class="p-5"><h2 class="font-black">Regeln</h2><p class="mt-2 text-sm text-app-text-muted">Leihfristen, Limits und Öffnungstage werden später fachlich konfiguriert.</p></x-ui.card>
        <x-ui.card class="p-5"><h2 class="font-black">Importe</h2><p class="mt-2 text-sm text-app-text-muted">Schul- und Mediendaten erhalten Vorschau und Konfliktbehandlung.</p></x-ui.card>
        <x-ui.card class="p-5"><h2 class="font-black">Datenschutz</h2><p class="mt-2 text-sm text-app-text-muted">Sensible Vorgänge werden separat berechtigt und auditierbar.</p></x-ui.card>
    </div>

    <section class="mt-8" aria-labelledby="permission-preview-heading">
        <h2 id="permission-preview-heading" class="text-xl font-black">Berechtigungsprinzip</h2>
        <x-ui.table class="mt-4">
            <thead>
                <tr><th scope="col">Rolle</th><th scope="col">Verwaltungsoberfläche</th><th scope="col">Fachrechte</th></tr>
            </thead>
            <tbody>
                <tr><td>Verwaltung</td><td><x-ui.badge variant="success">Zugang</x-ui.badge></td><td>werden einzeln ergänzt</td></tr>
                <tr><td>Technische Administration</td><td><x-ui.badge variant="success">Zugang</x-ui.badge></td><td>kein automatischer Lesedatenzugriff</td></tr>
                <tr><td>Schüler-AG</td><td><x-ui.badge>kein Standardzugang</x-ui.badge></td><td>Arbeitsrechte im POS</td></tr>
            </tbody>
        </x-ui.table>
    </section>
</x-app-shell>
