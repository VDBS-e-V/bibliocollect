<x-app-shell surface="public" title="Start">
    <section class="bc-search-stage" aria-labelledby="catalog-heading">
        <div class="bc-search-stage__content">
            <p class="mb-2 text-sm font-bold uppercase tracking-[0.12em] text-app-text-muted">VDBS Bibliothekssoftware</p>
            <h1 id="catalog-heading" class="text-3xl font-black tracking-tight sm:text-4xl">BiblioCollect</h1>
            <p class="mt-3 max-w-2xl text-lg text-app-text-muted">Finden, ausleihen, organisieren: BiblioCollect verbindet öffentlichen Katalog, persönliches Konto und den Bibliotheksbetrieb in einer VDBS-Anwendung.</p>
            <div class="bc-search" role="search" aria-label="Katalogsuche Vorschau">
                <label class="sr-only" for="catalog-search">Titel, Autor:in, ISBN oder Stichwort</label>
                <input id="catalog-search" class="bc-search__input" type="search" placeholder="Titel, Autor:in, ISBN oder Stichwort …" disabled>
                <x-ui.button disabled>Suchen</x-ui.button>
            </div>
            <p class="mt-2 text-sm text-app-text-muted">Die echte Katalogsuche folgt mit dem Catalog-Modul in T3.</p>
        </div>
    </section>

    <section class="mt-8 grid gap-4 md:grid-cols-3" aria-label="Projektstand">
        <x-ui.card class="p-5">
            <p class="text-sm font-bold uppercase tracking-wide text-app-text-muted">T0 · abgeschlossen</p>
            <h2 class="mt-2 text-xl font-black">Foundation</h2>
            <p class="mt-2 text-sm text-app-text-muted">Laravel 13, Livewire 4, Vite, Pest, PHPStan, Pint und grüne CI.</p>
        </x-ui.card>
        <x-ui.card class="p-5">
            <p class="text-sm font-bold uppercase tracking-wide text-app-text-muted">T1 · jetzt</p>
            <h2 class="mt-2 text-xl font-black">Anwendungsinfrastruktur</h2>
            <p class="mt-2 text-sm text-app-text-muted">Permissions, Rollen-Bundles, Navigation, vier Surfaces und VDBS-App-Shell.</p>
        </x-ui.card>
        <x-ui.card class="p-5">
            <p class="text-sm font-bold uppercase tracking-wide text-app-text-muted">Als Nächstes</p>
            <h2 class="mt-2 text-xl font-black">Identity, Patrons & School</h2>
            <p class="mt-2 text-sm text-app-text-muted">Onlinekonto, Ausleihkonto und Schulkontext bleiben fachlich getrennte Bausteine.</p>
        </x-ui.card>
    </section>

    @if (app()->environment('local'))
        <section class="mt-10" aria-labelledby="preview-heading">
            <x-ui.page-header
                kicker="Nur lokal"
                title="Entwicklungsansichten"
                lead="Diese Links umgehen keine Produktionsberechtigungen: Die Preview-Routen werden ausschließlich in der lokalen Umgebung registriert."
            />
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <x-ui.card class="p-5">
                    <h2 class="text-lg font-black">Mein Konto</h2>
                    <p class="mt-2 text-sm text-app-text-muted">Vorschau des Portals für eigene Ausleihen, Vormerkungen und Profilfunktionen.</p>
                    <x-ui.button class="mt-4" variant="secondary" :href="route('preview.portal')">Portal ansehen</x-ui.button>
                </x-ui.card>
                <x-ui.card class="p-5">
                    <h2 class="text-lg font-black">Bibliotheksbetrieb</h2>
                    <p class="mt-2 text-sm text-app-text-muted">Scanner- und tastaturorientierte Arbeitsoberfläche für AG und Mitarbeitende.</p>
                    <x-ui.button class="mt-4" variant="secondary" :href="route('preview.pos')">POS ansehen</x-ui.button>
                </x-ui.card>
                <x-ui.card class="p-5">
                    <h2 class="text-lg font-black">Verwaltung</h2>
                    <p class="mt-2 text-sm text-app-text-muted">Ruhige, datenreiche Oberfläche für Regeln, Importe, Datenschutz und Betrieb.</p>
                    <x-ui.button class="mt-4" variant="secondary" :href="route('preview.administration')">Verwaltung ansehen</x-ui.button>
                </x-ui.card>
            </div>
        </section>
    @endif
</x-app-shell>
