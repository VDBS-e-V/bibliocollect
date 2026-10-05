<x-app-shell surface="public" title="Katalog">
    <section class="bc-home-intro" aria-labelledby="home-heading">
        <div class="bc-home-intro__copy">
            <p class="bc-eyebrow">BiblioCollect · VDBS</p>
            <h1 id="home-heading">Die Schulbibliothek.</h1>
            <p class="bc-home-intro__lead">Medien finden, ausleihen und gemeinsam entdecken. BiblioCollect verbindet den öffentlichen Katalog mit den Services der Schulbibliothek – klar, zugänglich und ohne unnötige Hürden.</p>
        </div>

        <div class="bc-home-intro__brand" aria-label="Über BiblioCollect">
            <strong>Für den Schulalltag gemacht.</strong>
            <p>Die Bibliothek bleibt auch ohne Onlinekonto vollständig nutzbar. Digitale Funktionen ergänzen die Ausleihe vor Ort.</p>
            <img class="bc-home-intro__figures" src="/brand/vdbs/figures-chain.svg" alt="" aria-hidden="true">
        </div>
    </section>

    <section class="bc-catalog-search" aria-labelledby="catalog-heading">
        <div class="bc-catalog-search__heading">
            <h2 id="catalog-heading">Katalog durchsuchen</h2>
            <p>Suche nach Titel, Autor:in, ISBN oder Stichwort</p>
        </div>

        <form method="get" action="{{ route('public.catalog.index') }}" class="bc-catalog-search__form bc-home-catalog-form" role="search" aria-label="Katalog durchsuchen">
            <label class="sr-only" for="catalog-search">Suchbegriff</label>
            <input
                id="catalog-search"
                name="q"
                class="bc-catalog-search__input"
                type="search"
                placeholder="Was möchtest du finden?"
                minlength="2"
                maxlength="120"
                autocomplete="off"
            >
            <x-ui.button type="submit">Suchen</x-ui.button>
        </form>

        <div class="bc-catalog-search__meta">
            <span>Titel, Verantwortliche, ISBN, Verlag, Medientyp und Sprache durchsuchen.</span>
            <a href="{{ route('public.catalog.index') }}">Alle Titel und Filter öffnen →</a>
        </div>
    </section>

    <div class="bc-home-layout">
        <div class="bc-home-layout__main">
            <section class="bc-content-section" aria-labelledby="quick-heading">
                <div class="bc-section-heading">
                    <h2 id="quick-heading">Schnell finden</h2>
                </div>
                <div class="bc-link-list">
                    <div class="bc-link-list__row">
                        <div><a href="{{ route('public.catalog.index') }}"><strong>Im Katalog stöbern</strong></a><span>Alle erfassten Titel, Ausgaben und Bestandsinformationen entdecken</span></div>
                        <span class="bc-status-text">aktiv</span>
                    </div>
                    <div class="bc-link-list__row">
                        <div><strong>Veranstaltungen</strong><span>Termine, Aktionen und Anmeldungen</span></div>
                        <span class="bc-status-text">später</span>
                    </div>
                    <div class="bc-link-list__row">
                        <div><strong>Leselisten</strong><span>Freigegebene Listen von Lehrkräften</span></div>
                        <span class="bc-status-text">später</span>
                    </div>
                    <div class="bc-link-list__row">
                        <div><strong>Öffnungszeiten</strong><span>Bibliothekstage und aktuelle Hinweise</span></div>
                        <span class="bc-status-text">ab T2</span>
                    </div>
                </div>
            </section>

            <section class="bc-content-section" aria-labelledby="info-heading">
                <div class="bc-section-heading">
                    <h2 id="info-heading">Gut zu wissen</h2>
                </div>
                <div class="bc-notice-row">
                    <strong>Ausleihe vor Ort</strong>
                    <p>Ein Onlinekonto ist für die Nutzung der Bibliothek nicht erforderlich. Bibliotheksnummer und Ausleihkonto funktionieren unabhängig davon.</p>
                </div>
                <div class="bc-notice-row">
                    <strong>Selbstbedienung</strong>
                    <p>Mit einem optionalen Onlinekonto können später eigene Ausleihen, Vormerkungen und weitere persönliche Funktionen genutzt werden.</p>
                </div>
            </section>
        </div>

        <aside class="bc-home-layout__aside" aria-label="Service">
            <section class="bc-side-panel" aria-labelledby="account-heading">
                <h2 id="account-heading">Mein Konto</h2>
                <p>Eigene Ausleihen, Vormerkungen und Kontodaten verwalten.</p>
                @if (app()->environment('local'))
                    <x-ui.button class="mt-4" variant="secondary" :href="route('preview.portal')">Portal-Vorschau</x-ui.button>
                @else
                    <x-ui.button class="mt-4" variant="secondary" disabled>Zum Konto</x-ui.button>
                @endif
            </section>

            <section class="bc-side-panel bc-side-panel--quiet" aria-labelledby="help-heading">
                <h2 id="help-heading">Hilfe & Orientierung</h2>
                <p>Informationen zur Bibliotheksnutzung, zu Ausleihe und Vormerkungen werden hier später gebündelt.</p>
            </section>

            @if (app()->environment('local'))
                <section class="bc-side-panel bc-side-panel--quiet" aria-labelledby="dev-heading">
                    <h2 id="dev-heading">Entwicklungsansichten</h2>
                    <ul class="bc-plain-links">
                        <li><a href="{{ route('preview.pos') }}">Bibliotheksbetrieb</a></li>
                        <li><a href="{{ route('preview.administration') }}">Verwaltung</a></li>
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</x-app-shell>
