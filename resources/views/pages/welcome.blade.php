<x-app-shell surface="public" title="Startseite">
    <x-content.block key="home.notice" />

    @php
        // Bewährte Live-Daten unverändert: kuratierte Empfehlungen, Neuerscheinungen,
        // Themen, Öffnungszeiten, Schließtage und Leihregeln kommen weiterhin aus
        // dem bestehenden PublicHomeController und HomeShowcase.
        $recommendedTiles = $showcase['recommended'] ?? [];
        $newTiles = $showcase['new'] ?? [];
        $discoverTiles = collect(array_merge($recommendedTiles, $newTiles))->unique('id')->take(4)->values()->all();
        $heroTiles = array_slice($discoverTiles, 0, 2);
        $recommendedIds = collect($recommendedTiles)->pluck('id')->all();
        $showsRecommended = collect($discoverTiles)->contains(fn (array $tile): bool => in_array($tile['id'], $recommendedIds, true));
        $showsNew = collect($discoverTiles)->contains(fn (array $tile): bool => ! in_array($tile['id'], $recommendedIds, true));
        $scheduleItems = collect($hours)->map(static fn (array $hour): array => [
            'day' => $hour['day'],
            'hours' => $hour['time'],
        ])->all();
    @endphp

    {{-- Echte Startseite auf dem Feature-Branch: Layout aus der abgenommenen V1,
         Daten und Berechtigungen aus der bestehenden produktiven Anwendung. --}}
    <div class="bc-library-home bc-home-v1 bc-home-live" id="bc-home-top">
        <section class="bc-home-hero bc-home-v1__hero" aria-labelledby="home-heading" id="home-search">
            <div class="bc-home-v1__hero-copy">
                <span class="bc-home-v1__eyebrow bc-home-v1__eyebrow--light">Deine Schulbibliothek · BiblioCollect</span>
                <h1 id="home-heading">Deine nächste <em>Geschichte</em> wartet.</h1>
                <p class="bc-home-v1__hero-description">Finde Bücher und Themen, entdecke neue Perspektiven und folge deiner Neugier.</p>
                <div class="bc-home-v1__search-wrap">
                    <strong class="bc-home-v1__search-label">Was möchtest du entdecken?</strong>
                    <x-library.catalog-search id="catalog-search" placeholder="Titel, Autor:in oder Thema eingeben …" />
                </div>
                <div class="bc-home-v1__hero-links">
                    <span>Oder direkt zu:</span>
                    <a href="{{ route('public.catalog.advanced') }}">Erweiterte Suche <span aria-hidden="true">↗</span></a>
                    <a href="#oeffnungszeiten">Öffnungszeiten <span aria-hidden="true">↓</span></a>
                </div>
            </div>
            <div class="bc-home-v1__hero-visual" aria-hidden="true">
                <span class="bc-home-v1__visual-ring"></span>
                <span class="bc-home-v1__visual-circle"></span>
                <span class="bc-home-v1__visual-caption">Eine Bibliothek. Unzählige Ideen.</span>
                <div class="bc-home-v1__book bc-home-v1__book--back">
                    @if (isset($heroTiles[0]))
                        <x-library.media-cover :src="$heroTiles[0]['cover']" :title="$heroTiles[0]['title']" :author="$heroTiles[0]['authors']" size="hero" />
                    @else
                        <x-library.media-cover title="Neue Ideen" author="Deine Bibliothek" size="hero" variant="plum" />
                    @endif
                </div>
                <div class="bc-home-v1__book bc-home-v1__book--front">
                    @if (isset($heroTiles[1]))
                        <x-library.media-cover :src="$heroTiles[1]['cover']" :title="$heroTiles[1]['title']" :author="$heroTiles[1]['authors']" size="hero" />
                    @else
                        <x-library.media-cover title="Lesen öffnet Welten" author="Bücher entdecken" size="hero" variant="mint" />
                    @endif
                </div>
            </div>
        </section>

        <section class="bc-home-actions bc-home-v1__section bc-home-v1__actions" aria-labelledby="home-actions-heading" id="home-actions">
            <div class="bc-home-v1__section-head">
                <div>
                    <span class="bc-home-v1__eyebrow">Einfach anfangen</span>
                    <h2 id="home-actions-heading">Wohin soll es gehen?</h2>
                    <p>Vier Wege in die Bibliothekswelt – ohne verschachtelte Menüs.</p>
                </div>
                <span class="bc-home-v1__section-number" aria-hidden="true">01 / 04</span>
            </div>
            <div class="bc-home-v1__actions-grid">
                <x-library.quick-action :href="route('public.catalog.index')" title="Medien finden" description="Im gesamten Bestand stöbern." icon="search" />
                @can('surface.portal.access')
                    <x-library.quick-action :href="route('portal.home')" title="Ausleihen verwalten" description="Zum persönlichen Bibliothekskonto." icon="user" />
                @else
                    <x-library.quick-action :href="route('login')" title="Ausleihen verwalten" description="Für Verlängerungen und Vormerkungen anmelden." icon="user" />
                @endcan
                <x-library.quick-action :href="route('public.series.index')" title="Buchreihen entdecken" description="Zusammenhängende Geschichten und Themen finden." icon="book" />
                <x-library.quick-action :href="route('public.wishes.create')" title="Buchwunsch abgeben" description="Neue Medien für die Bibliothek vorschlagen." icon="heart" />
            </div>
        </section>

        <section class="bc-home-discovery bc-home-v1__section bc-home-v1__discovery" aria-labelledby="home-discovery-heading" id="home-discovery">
            <div class="bc-home-v1__section-head">
                <div>
                    <span class="bc-home-v1__eyebrow">Aus dem Katalog</span>
                    <h2 id="home-discovery-heading">Jedes Buch öffnet eine andere Tür.</h2>
                    <p>Empfehlungen aus deiner Bibliothek und Medien, die neu dazugekommen sind.</p>
                </div>
                <x-ui.button :href="route('public.catalog.index')" variant="secondary">Alle Medien ansehen <span aria-hidden="true">↗</span></x-ui.button>
            </div>

            @if ($discoverTiles !== [])
                <div class="bc-home-live__discovery-labels">
                    @if ($showsRecommended) <span>Empfohlene Medien</span> @endif
                    @if ($showsNew) <span>Neue Medien</span> @endif
                </div>
                <div class="bc-home-v1__media-grid">
                    @foreach ($discoverTiles as $tile)
                        <x-library.media-card
                            :title="$tile['title']"
                            :author="$tile['authors']"
                            :cover="$tile['cover']"
                            :href="route('public.catalog.show', ['titleId' => $tile['id']])"
                            :density="$loop->last ? 'compact' : 'comfortable'">
                            <x-slot:status>
                                <x-ui.badge :variant="$tile['variant']">{{ $tile['badge'] }}</x-ui.badge>
                            </x-slot:status>
                        </x-library.media-card>
                    @endforeach
                </div>
            @else
                <div class="bc-home-live__discovery-empty">
                    <p>Hier erscheinen Empfehlungen und Neuerscheinungen, sobald Medien dafür ausgewählt sind.</p>
                    <x-ui.button :href="route('public.catalog.index')" variant="secondary">Im Katalog stöbern <span aria-hidden="true">↗</span></x-ui.button>
                </div>
            @endif

            @if (($showcase['topics'] ?? []) !== [])
                <div class="bc-home-live__topics">
                    <h3>Empfohlene Themen</h3>
                    <ul>
                        @foreach ($showcase['topics'] as $topic)
                            <li><a href="{{ route('public.topic', ['key' => $topic['key']]) }}">{{ $topic['name'] }} <span aria-hidden="true">↗</span></a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        <section class="bc-home-editorial bc-home-v1__section bc-home-v1__editorial" aria-labelledby="home-editorial-heading" id="home-editorial">
            <div class="bc-home-v1__section-head">
                <div>
                    <span class="bc-home-v1__eyebrow">Mehr als ein Regal</span>
                    <h2 id="home-editorial-heading">Raum für Neugier.</h2>
                    <p>Ein visueller und redaktioneller Gegenpol zur direkten Suche.</p>
                </div>
                <span class="bc-home-v1__section-number" aria-hidden="true">03 / 04</span>
            </div>
            <x-library.feature-overlap
                eyebrow="Bibliothek entdecken"
                title="Geschichten verbinden Menschen."
                description="Eine Schulbibliothek ist mehr als ein Regal: Hier treffen Geschichten, neue Ideen und unterschiedliche Perspektiven aufeinander."
                :href="route('public.series.index')"
                action-label="Buchreihen entdecken"
                tone="mint" />
        </section>

        <section class="bc-home-info bc-home-v1__section bc-home-v1__service" aria-labelledby="home-info-heading" id="home-service">
            <div class="bc-home-v1__section-head">
                <div>
                    <span class="bc-home-v1__eyebrow">Gut zu wissen</span>
                    <h2 id="home-info-heading">Deine Bibliothek. Deine Möglichkeiten.</h2>
                    <p>Deine tatsächlichen Öffnungszeiten und die geltenden Ausleihregeln auf einen Blick.</p>
                </div>
                <span class="bc-home-v1__section-number" aria-hidden="true">04 / 04</span>
            </div>
            <div class="bc-home-v1__service-grid">
                <article class="bc-home-v1__schedule-card" id="oeffnungszeiten" aria-labelledby="home-hours-heading">
                    <div class="bc-home-v1__schedule-header">
                        <span class="bc-home-v1__eyebrow">Deine Schulbibliothek</span>
                        <h3 id="home-hours-heading">Öffnungszeiten</h3>
                        <p>Die aktuell in der Bibliotheksverwaltung hinterlegten Zeiten.</p>
                    </div>
                    @if ($scheduleItems === [])
                        <p>Die Öffnungszeiten werden noch eingetragen. Frag bitte in der Schule nach.</p>
                    @else
                        <x-ui.week-schedule label="Öffnungszeiten der Bibliothek" :items="$scheduleItems" />
                    @endif
                    @if ($closures !== [])
                        <div class="bc-home-live__closures">
                            <h4>Geschlossen in den nächsten Wochen</h4>
                            <ul>
                                @foreach ($closures as $closure)
                                    <li>{{ $closure['date'] }}@if ($closure['reason']) · {{ $closure['reason'] }}@endif</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </article>

                <div class="bc-home-v1__service-side">
                    <article class="bc-home-v1__service-card" aria-labelledby="home-loans-heading">
                        <span class="bc-home-v1__eyebrow">Dein Bibliothekskonto</span>
                        <h3 id="home-loans-heading">So funktioniert die Ausleihe</h3>
                        <p>Mit deinem Bibliotheksausweis kannst du bis zu {{ $maxLoans }} Medien gleichzeitig für {{ $loanDays }} Tage ausleihen.</p>
                        @if ($maxRenewals > 0)
                            <p>Verlängerungen sind bis zu {{ $maxRenewals }} Mal möglich, solange niemand den Titel vorgemerkt hat.</p>
                        @endif
                        @if ($reservations)
                            <p>Ausgeliehene Titel kannst du vormerken.</p>
                        @endif
                        <div class="bc-home-v1__service-links">
                            @guest
                                <x-ui.button :href="route('identity.claim.create')" variant="secondary">Onlinekonto aktivieren <span aria-hidden="true">↗</span></x-ui.button>
                            @else
                                @can('surface.portal.access')
                                    <x-ui.button :href="route('portal.home')" variant="secondary">Zum Bibliothekskonto <span aria-hidden="true">↗</span></x-ui.button>
                                @endcan
                            @endguest
                        </div>
                    </article>
                    <div class="bc-home-live__service-extra">
                        <span class="bc-home-v1__eyebrow">Mehr entdecken</span>
                        <p>Fehlt ein Buch in der Bibliothek? Deine Wünsche sind willkommen.</p>
                        <a href="{{ route('public.wishes.create') }}">Buchwunsch abgeben <span aria-hidden="true">↗</span></a>
                    </div>
                </div>
            </div>
        </section>

        <footer class="bc-home-v1__closing" aria-label="Startseitenabschluss">
            <div>
                <span class="bc-home-v1__eyebrow">BiblioCollect · VDBS</span>
                <strong>Ein Design. Viele Geschichten.</strong>
                <p>Stöbern, entdecken und gemeinsam weiterdenken – in deiner Schulbibliothek.</p>
            </div>
            <x-ui.back-to-top href="#bc-home-top" />
        </footer>
    </div>
</x-app-shell>
