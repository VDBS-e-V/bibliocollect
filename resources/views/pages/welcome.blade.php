<x-app-shell surface="public" title="Startseite">
    <x-content.block key="home.notice" />

    @php
        // Kuratierte Empfehlungen zuerst, anschließend Neuerscheinungen.
        // Keine doppelten Karten für Titel, die in beiden Listen stehen.
        $recommendedTiles = $showcase['recommended'] ?? [];
        $newTiles = $showcase['new'] ?? [];
        $discoverTiles = collect(array_merge($recommendedTiles, $newTiles))->unique('id')->take(4)->values()->all();
        $heroTiles = array_slice($discoverTiles, 0, 2);
    @endphp

    <div class="bc-library-home">
        {{-- 01 · Markenbühne nach der vom Projekt bereitgestellten HTML-Vorlage. --}}
        <section class="bc-home-hero" aria-labelledby="home-heading">
            <div class="bc-home-hero__layout">
                <div class="bc-home-hero__copy">
                    <span class="bc-home-eyebrow bc-home-eyebrow--light">Deine Schulbibliothek</span>
                    <h1 id="home-heading">Hier beginnt <span>deine nächste</span> Geschichte.</h1>
                    <p>Finde Bücher, die dich begeistern. Entdecke neue Themen und nutze die Bibliothek ganz nach deinen Ideen.</p>
                    <x-library.catalog-search id="catalog-search" placeholder="Buchtitel, Autor:in oder Thema …" />
                    <div class="bc-home-hero__links">
                        <span>Direkt entdecken:</span>
                        <a href="{{ route('public.catalog.index') }}">Alle Medien</a>
                        <a href="{{ route('public.series.index') }}">Buchreihen</a>
                        <a href="{{ route('public.catalog.advanced') }}">Erweiterte Suche</a>
                    </div>
                </div>

                <div class="bc-home-hero__art" aria-hidden="true">
                    <span class="bc-home-hero__circle bc-home-hero__circle--main"></span>
                    <span class="bc-home-hero__circle bc-home-hero__circle--ring"></span>
                    <span class="bc-home-hero__caption">
                        <small>Aus dem Katalog</small>
                        <strong>Lesen öffnet Welten.</strong>
                    </span>
                    <div class="bc-home-hero__book bc-home-hero__book--back">
                        @if (isset($heroTiles[0]))
                            <x-library.media-cover :src="$heroTiles[0]['cover']" :title="$heroTiles[0]['title']" :author="$heroTiles[0]['authors']" size="hero" />
                        @else
                            <x-library.media-cover title="Neue Ideen" author="Bücher entdecken" variant="mint" size="hero" />
                        @endif
                    </div>
                    <div class="bc-home-hero__book bc-home-hero__book--front">
                        @if (isset($heroTiles[1]))
                            <x-library.media-cover :src="$heroTiles[1]['cover']" :title="$heroTiles[1]['title']" :author="$heroTiles[1]['authors']" size="hero" />
                        @else
                            <x-library.media-cover title="Lesen öffnet Welten" author="Deine Bibliothek" variant="plum" size="hero" />
                        @endif
                    </div>
                    <span class="bc-home-hero__footnote">Ein Ort für alle Ideen.</span>
                </div>
            </div>
        </section>

        {{-- 02 · Vier wesentliche Aufgaben, keine wiederholten Serviceleisten. --}}
        <section class="bc-home-actions" aria-labelledby="home-actions-heading">
            <div class="bc-home-heading">
                <span class="bc-home-eyebrow">Schnell zum Ziel</span>
                <h2 id="home-actions-heading">Was möchtest du tun?</h2>
                <p>Die wichtigsten Möglichkeiten auf einen Blick.</p>
            </div>
            <div class="bc-home-actions__grid">
                <x-library.quick-action :href="route('public.catalog.index')" title="Medien finden" description="Den gesamten Bestand durchsuchen." icon="search" />
                @can('surface.portal.access')
                    <x-library.quick-action :href="route('portal.home')" title="Ausleihen verwalten" description="Verlängern und vormerken." icon="user" />
                @else
                    <x-library.quick-action :href="route('login')" title="Ausleihen verwalten" description="Für Verlängerungen und Vormerkungen anmelden." icon="user" />
                @endcan
                <x-library.quick-action :href="route('public.series.index')" title="Themen entdecken" description="Buchreihen und Lesestoff entdecken." icon="book" />
                <x-library.quick-action :href="route('public.wishes.create')" title="Buchwunsch abgeben" description="Deine Ideen für neue Medien." icon="heart" />
            </div>
        </section>

        {{-- 03 · Ein Medienbereich statt mehrerer gleichförmiger Raster. --}}
        <section class="bc-home-discovery" aria-labelledby="home-discovery-heading">
            <div class="bc-home-discovery__inner">
                <div class="bc-home-heading bc-home-heading--split">
                    <div>
                        <span class="bc-home-eyebrow">Aus dem Katalog</span>
                        <h2 id="home-discovery-heading">Entdecke etwas Neues</h2>
                        <p>Stöbere durch Bücher, die neu im Regal stehen oder besonders empfohlen werden.</p>
                    </div>
                    <a class="bc-home-text-link" href="{{ route('public.catalog.index') }}">Alle Medien ansehen <span aria-hidden="true">→</span></a>
                </div>

                @if ($discoverTiles !== [])
                    <div class="bc-home-discovery__labels">
                        @if ($recommendedTiles !== []) <span>Empfohlene Medien</span> @endif
                        @if ($newTiles !== []) <span>Neue Medien</span> @endif
                    </div>
                    <ul class="bc-home-discovery__grid">
                        @foreach ($discoverTiles as $tile)
                            <li>
                                <a class="bc-home-media" href="{{ route('public.catalog.show', ['titleId' => $tile['id']]) }}">
                                    <span class="bc-home-media__cover">
                                        <x-library.media-cover :src="$tile['cover']" :title="$tile['title']" :author="$tile['authors']" size="normal" />
                                    </span>
                                    <strong>{{ $tile['title'] }}</strong>
                                    @if ($tile['authors'] !== '')<small>{{ $tile['authors'] }}</small>@endif
                                </a>
                                <div class="bc-home-media__availability"><x-ui.badge :variant="$tile['variant']">{{ $tile['badge'] }}</x-ui.badge></div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="bc-home-discovery__empty">
                        <p>Hier erscheinen Empfehlungen und Neuerscheinungen, sobald Medien dafür ausgewählt sind.</p>
                        <a href="{{ route('public.catalog.index') }}">Im Katalog stöbern <span aria-hidden="true">→</span></a>
                    </div>
                @endif

                @if (($showcase['topics'] ?? []) !== [])
                    <div class="bc-home-discovery__topics">
                        <h3>Empfohlene Themen</h3>
                        <ul>
                            @foreach ($showcase['topics'] as $topic)
                                <li><a href="{{ route('public.topic', ['key' => $topic['key']]) }}">{{ $topic['name'] }} <span aria-hidden="true">↗</span></a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>

        {{-- 04 · Redaktionelles Gegenstück zu Suche und Katalog, bewusst keine weitere Kachelwand. --}}
        <section class="bc-home-editorial" aria-labelledby="home-editorial-heading">
            <div class="bc-home-editorial__visual" aria-hidden="true">
                <span class="bc-home-eyebrow bc-home-eyebrow--light">Unser Ort</span>
                <div class="bc-home-editorial__books">
                    <span></span><span></span><span></span>
                </div>
                <strong>Mehr als Regale voller Bücher.</strong>
            </div>
            <div class="bc-home-editorial__copy">
                <span class="bc-home-eyebrow">Deine Bibliothek</span>
                <h2 id="home-editorial-heading">Ein Ort zum Lernen. Ein Ort zum Bleiben.</h2>
                <p>Deine Schulbibliothek bietet Raum für Geschichten, neue Perspektiven und gemeinsames Entdecken. Im Katalog findest du die Medien, die dazu gehören.</p>
                <a class="bc-home-outline-link" href="{{ route('public.catalog.index') }}">Bibliothek entdecken <span aria-hidden="true">→</span></a>
            </div>
        </section>

        {{-- 05 · Nur verlässliche, konfigurierbare Betriebsinformationen. --}}
        <section class="bc-home-info" aria-labelledby="home-info-heading">
            <div class="bc-home-heading">
                <span class="bc-home-eyebrow">Gut zu wissen</span>
                <h2 id="home-info-heading">Alles Wichtige auf einen Blick</h2>
            </div>
            <div class="bc-home-info__grid">
                <article class="bc-home-info__panel" id="oeffnungszeiten">
                    <h3>Öffnungszeiten</h3>
                    @if ($hours === [])
                        <p>Die Öffnungszeiten werden noch eingetragen. Frag bitte in der Schule nach.</p>
                    @else
                        <table class="bc-home-hours">
                            <caption class="sr-only">Öffnungszeiten der Bibliothek</caption>
                            <tbody>
                                @foreach ($hours as $hour)
                                    <tr><th scope="row">{{ $hour['day'] }}</th><td>{{ $hour['time'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                    @if ($closures !== [])
                        <div class="bc-home-closures">
                            <h4>Geschlossen in den nächsten Wochen</h4>
                            <ul>
                                @foreach ($closures as $closure)
                                    <li>{{ $closure['date'] }}@if ($closure['reason']) · {{ $closure['reason'] }}@endif</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </article>
                <article class="bc-home-info__panel bc-home-info__panel--soft">
                    <h3>So funktioniert die Ausleihe</h3>
                    <p>Mit deinem Bibliotheksausweis kannst du bis zu {{ $maxLoans }} Medien gleichzeitig für {{ $loanDays }} Tage ausleihen.</p>
                    @if ($maxRenewals > 0)
                        <p>Verlängerungen sind bis zu {{ $maxRenewals }} Mal möglich, solange niemand den Titel vorgemerkt hat.</p>
                    @endif
                    @if ($reservations)
                        <p>Ausgeliehene Titel kannst du vormerken.</p>
                    @endif
                    @guest
                        <a class="bc-home-text-link" href="{{ route('identity.claim.create') }}">Onlinekonto aktivieren <span aria-hidden="true">→</span></a>
                    @else
                        @can('surface.portal.access')
                            <a class="bc-home-text-link" href="{{ route('portal.home') }}">Zum Bibliothekskonto <span aria-hidden="true">→</span></a>
                        @endcan
                    @endguest
                </article>
            </div>
        </section>
    </div>
</x-app-shell>
