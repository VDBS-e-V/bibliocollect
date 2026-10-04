@php($preview = $preview ?? false)

<x-app-shell surface="portal" title="Mein Konto" :preview="$preview">
    <x-ui.page-header
        kicker="Portal"
        title="Mein Konto"
        lead="Hier sehen Nutzer:innen später ausschließlich ihre eigenen Bibliotheksvorgänge."
    />

    <div class="mt-7 grid gap-4 md:grid-cols-3">
        <x-ui.card class="p-5">
            <p class="text-sm font-bold text-app-text-muted">Ausgeliehen</p>
            <p class="mt-2 text-3xl font-black">–</p>
            <p class="mt-1 text-sm text-app-text-muted">Eigene aktive Ausleihen</p>
        </x-ui.card>
        <x-ui.card class="p-5">
            <p class="text-sm font-bold text-app-text-muted">Vormerkungen</p>
            <p class="mt-2 text-3xl font-black">–</p>
            <p class="mt-1 text-sm text-app-text-muted">Zeitraum und Click & Collect</p>
        </x-ui.card>
        <x-ui.card class="p-5">
            <p class="text-sm font-bold text-app-text-muted">Onlinekonto</p>
            <p class="mt-3"><x-ui.badge>Vorschau</x-ui.badge></p>
            <p class="mt-2 text-sm text-app-text-muted">Verknüpfung mit Ausleihkonto folgt in T2.</p>
        </x-ui.card>
    </div>

    <x-ui.alert class="mt-7" title="Datenschutzprinzip">
        Das Portal wird nur eigene Daten anzeigen. Lehrkräfte erhalten durch ihre Lehrerrolle keinen Zugriff auf Ausleihen von Schüler:innen.
    </x-ui.alert>
</x-app-shell>
