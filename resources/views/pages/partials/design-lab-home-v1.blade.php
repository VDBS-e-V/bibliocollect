{{-- Öffentliche Startseite V1: ausschließlich Designlab-Demo; GET-Navigation erlaubt, keine Datenänderungen. --}}
@php
    $mediaExamples = [
        ['title' => 'Zwischen den Zeilen', 'author' => 'Lena Beispiel', 'variant' => 'plum', 'eyebrow' => 'Roman'],
        ['title' => 'Was wäre, wenn?', 'author' => 'M. Zukunft', 'variant' => 'mint', 'eyebrow' => 'Wissen & Ideen'],
        ['title' => 'Die Stadt bei Nacht', 'author' => 'Noa Muster', 'variant' => 'ink', 'eyebrow' => 'Geschichten'],
        ['title' => 'Die wunderbare und unglaublich aufregende Reise in eine unbekannte Welt', 'author' => 'A. Beispielautor:in', 'variant' => 'sand', 'eyebrow' => 'Abenteuer'],
    ];
@endphp

<div class="bc-home-v1" id="bc-home-v1-top">
    <header class="bc-home-v1__review-head">
        <div>
            <span class="bc-home-v1__review-kicker">Seitenentwurf 01 · Öffentlich · Version 1</span>
            <h1>Die Startseite als Ganzes.</h1>
            <p>Auf Basis der freigegebenen VDBS-Komponenten. Dieser Entwurf ist noch keine Änderung an der echten Startseite.</p>
        </div>
        <div class="bc-home-v1__review-actions">
            <x-ui.button :href="route('public.design-lab.components')" variant="secondary" size="sm">Bausteine ansehen</x-ui.button>
            <a href="{{ route('public.design-lab.home') }}">Ältere Startseitenstudie vergleichen <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    <nav class="bc-home-v1__page-jump" aria-label="Abschnitte dieses Startseitenentwurfs">
        <a href="#bc-home-v1-search">Suche</a>
        <a href="#bc-home-v1-actions">Schnellzugriffe</a>
        <a href="#bc-home-v1-discovery">Medien entdecken</a>
        <a href="#bc-home-v1-world">Bibliothekswelt</a>
        <a href="#bc-home-v1-service">Service</a>
    </nav>

    <section class="bc-home-v1__hero" aria-labelledby="bc-home-v1-hero-title" id="bc-home-v1-search">
        <div class="bc-home-v1__hero-copy">
            <span class="bc-home-v1__eyebrow bc-home-v1__eyebrow--light">Deine Schulbibliothek · BiblioCollect</span>
            <h2 id="bc-home-v1-hero-title">Deine nächste <em>Geschichte</em> wartet.</h2>
            <p class="bc-home-v1__hero-description">Finde Bücher und Themen, entdecke neue Perspektiven und folge deiner Neugier.</p>
            <div class="bc-home-v1__search-wrap">
                <strong class="bc-home-v1__search-label">Was möchtest du entdecken?</strong>
                <x-library.catalog-search id="bc-home-v1-catalog-search" placeholder="Titel, Autor:in oder Thema eingeben …" />
                <p class="bc-home-v1__search-note">Die Suche öffnet den bestehenden Katalog. Es werden keine Beispieldaten gespeichert.</p>
            </div>
            <div class="bc-home-v1__hero-links">
                <span>Oder direkt zu:</span>
                <a href="{{ route('public.catalog.advanced') }}">Erweiterte Suche <span aria-hidden="true">↗</span></a>
                <a href="#bc-home-v1-service">Bibliotheksinfos <span aria-hidden="true">↓</span></a>
            </div>
        </div>
        <div class="bc-home-v1__hero-visual" aria-hidden="true">
            <span class="bc-home-v1__visual-ring"></span>
            <span class="bc-home-v1__visual-circle"></span>
            <span class="bc-home-v1__visual-caption">Eine Bibliothek. Unzählige Ideen.</span>
            <div class="bc-home-v1__book bc-home-v1__book--back">
                <x-library.media-cover title="Zwischen den Zeilen" author="Lena Beispiel" size="hero" variant="plum" />
            </div>
            <div class="bc-home-v1__book bc-home-v1__book--front">
                <x-library.media-cover title="Was wäre, wenn?" author="M. Zukunft" size="hero" variant="mint" />
            </div>
        </div>
    </section>

    <section class="bc-home-v1__section bc-home-v1__actions" id="bc-home-v1-actions" aria-labelledby="bc-home-v1-actions-title">
        <div class="bc-home-v1__section-head">
            <div>
                <span class="bc-home-v1__eyebrow">Einfach anfangen</span>
                <h2 id="bc-home-v1-actions-title">Wohin soll es gehen?</h2>
                <p>Vier Wege in die Bibliothekswelt – ohne verschachtelte Menüs.</p>
            </div>
            <span class="bc-home-v1__section-number" aria-hidden="true">01 / 04</span>
        </div>
        <div class="bc-home-v1__actions-grid">
            <x-library.quick-action :href="route('public.catalog.index')" title="Medien finden" description="Im gesamten Bestand stöbern." icon="search" />
            <x-library.quick-action :href="route('public.series.index')" title="Buchreihen" description="Zusammenhängende Geschichten entdecken." icon="book" />
            <x-library.quick-action :href="route('public.wishes.create')" title="Buchwunsch" description="Eine Idee für die Bibliothek vorschlagen." icon="heart" />
            @can('surface.portal.access')
                <x-library.quick-action :href="route('portal.home')" title="Mein Konto" description="Zum persönlichen Bereich wechseln." icon="user" />
            @else
                <x-library.quick-action :href="route('login')" title="Mein Konto" description="Für persönliche Funktionen anmelden." icon="user" />
            @endcan
        </div>
    </section>

    <section class="bc-home-v1__section bc-home-v1__discovery" id="bc-home-v1-discovery" aria-labelledby="bc-home-v1-discovery-title">
        <div class="bc-home-v1__section-head">
            <div>
                <span class="bc-home-v1__eyebrow">Katalog entdecken</span>
                <h2 id="bc-home-v1-discovery-title">Jedes Buch öffnet eine andere Tür.</h2>
                <p>Vier fiktive Beispiele demonstrieren Medienkarten, lange Titel und Coverfarben. Keine echten Empfehlungen oder Verfügbarkeiten.</p>
            </div>
            <x-ui.button :href="route('public.catalog.index')" variant="secondary">Zum echten Katalog <span aria-hidden="true">↗</span></x-ui.button>
        </div>
        <div class="bc-home-v1__media-grid">
            @foreach ($mediaExamples as $medium)
                <x-library.media-card :title="$medium['title']" :author="$medium['author']"
                    :coverVariant="$medium['variant']" :eyebrow="$medium['eyebrow']"
                    :density="$loop->last ? 'compact' : 'comfortable'" />
            @endforeach
        </div>
        <p class="bc-home-v1__media-footnote">Designbeispiele mit Comfortable- und Compact-Karten. In der späteren Live-Ansicht werden hier ausschließlich tatsächliche Katalogdaten angezeigt.</p>
    </section>

    <section class="bc-home-v1__section bc-home-v1__editorial" id="bc-home-v1-world" aria-labelledby="bc-home-v1-world-title">
        <div class="bc-home-v1__section-head">
            <div>
                <span class="bc-home-v1__eyebrow">Mehr als ein Regal</span>
                <h2 id="bc-home-v1-world-title">Raum für Neugier.</h2>
                <p>Ein visueller und redaktioneller Gegenpol zur direkten Suche.</p>
            </div>
            <span class="bc-home-v1__section-number" aria-hidden="true">03 / 04</span>
        </div>
        <x-library.feature-overlap
            eyebrow="Ideen finden"
            title="Geschichten verbinden. Wissen wächst."
            description="Ob Sachbuch, Roman oder neue Lieblingsreihe: Der Katalog eröffnet unterschiedliche Wege in die Welt der Medien."
            :href="route('public.series.index')"
            action-label="Buchreihen entdecken"
            tone="mint" />
    </section>

    <section class="bc-home-v1__section bc-home-v1__service" id="bc-home-v1-service" aria-labelledby="bc-home-v1-service-title">
        <div class="bc-home-v1__section-head">
            <div>
                <span class="bc-home-v1__eyebrow">Gut zu wissen</span>
                <h2 id="bc-home-v1-service-title">Deine Bibliothek. Deine Möglichkeiten.</h2>
                <p>Informationen verständlich bereitstellen, ohne nicht bestätigte Öffnungszeiten oder Dienstleistungen zu erfinden.</p>
            </div>
            <span class="bc-home-v1__section-number" aria-hidden="true">04 / 04</span>
        </div>
        <div class="bc-home-v1__service-grid">
            <x-library.notice-panel variant="info" title="Öffnungszeiten und Schließtage">
                <p>Hier werden später die gültigen Zeiten der jeweiligen Schulbibliothek eingebunden. In dieser Vorschau sind bewusst keine Uhrzeiten eingetragen.</p>
            </x-library.notice-panel>
            <div class="bc-home-v1__service-card">
                <span class="bc-home-v1__eyebrow">Dein nächster Schritt</span>
                <h3>Du hast einen Buchwunsch?</h3>
                <p>Schlage neue Titel vor oder entdecke, welche Buchreihen bereits verfügbar sind.</p>
                <div class="bc-home-v1__service-links">
                    <x-ui.button :href="route('public.wishes.create')">Buchwunsch öffnen</x-ui.button>
                    <a href="{{ route('public.series.index') }}">Buchreihen ansehen <span aria-hidden="true">↗</span></a>
                </div>
            </div>
        </div>
    </section>

    <footer class="bc-home-v1__closing" aria-label="Abschluss des Startseitenentwurfs">
        <div>
            <span class="bc-home-v1__eyebrow">Der erste Seitenentwurf</span>
            <strong>Ein Design. Viele Geschichten.</strong>
            <p>Komponenten freigegeben, Seitenentwurf noch offen: Rückmeldungen erfolgen hier im Designlab, bevor produktive Seiten umgebaut werden.</p>
        </div>
        <x-ui.back-to-top href="#bc-home-v1-top" />
    </footer>
</div>
