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


    {{-- Lokale V2 auf der echten Startseite: durchgehender Hero und unterschiedliche Abschnittsformen. --}}
    <div class="bc-library-home bc-home-v1 bc-home-live" id="bc-home-top">
        <section class="bc-home-hero bc-home-v1__hero" aria-labelledby="home-heading" id="home-search">
            <div class="bc-home-hero__layout">
                <div class="bc-home-hero__copy">
                    <span class="bc-home-eyebrow bc-home-eyebrow--light">Deine Schulbibliothek</span>
                    <h1 id="home-heading"><span>Hier beginnt</span><span class="bc-home-hero__accent">deine nächste</span><span>Geschichte.</span></h1>
                    <p>Finde Bücher, die dich begeistern. Entdecke neue Themen und nutze die Bibliothek ganz nach deinen Ideen.</p>
                    <x-library.catalog-search id="catalog-search" placeholder="Buchtitel, Autor:in oder Thema …" />
                    <div class="bc-home-hero__links">
                        <span>Direkt zu:</span>
                        <a href="{{ route('public.catalog.advanced') }}">Erweiterte Suche</a>
                        <a href="#oeffnungszeiten">Öffnungszeiten</a>
                    </div>
                </div>
                <div class="bc-home-hero__art" aria-hidden="true">
                    <span class="bc-home-hero__circle bc-home-hero__circle--main"></span>
                    <span class="bc-home-hero__circle bc-home-hero__circle--ring"></span>
                    <span class="bc-home-hero__caption"><small>Entdecken</small><strong>Was liest du als Nächstes?</strong></span>
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
                </div>
            </div>
        </section>

        {{-- Bildhafte Angebotskarten im dunklen Bereich, nur echte Ziele. --}}
        <section class="bc-home-actions bc-home-live__offers" aria-labelledby="home-actions-heading" id="home-actions">
            <h2 id="home-actions-heading">Angebote entdecken</h2>
            <div class="bc-home-live__offer-grid">
                <x-library.quick-action class="bc-home-live__offer bc-home-live__offer--catalog"
                    :href="route('public.catalog.index')" title="Medien finden"
                    description="Geschichten, Wissen und neue Lieblingsbücher warten im Katalog." icon="search" />
                <x-library.quick-action class="bc-home-live__offer bc-home-live__offer--series"
                    :href="route('public.series.index')" title="Buchreihen entdecken"
                    description="Fortsetzungen lesen und Welten immer wieder besuchen." icon="book" />
                <x-library.quick-action class="bc-home-live__offer bc-home-live__offer--wishes"
                    :href="route('public.wishes.create')" title="Buchwunsch abgeben"
                    description="Dir fehlt ein Titel? Bring deine Ideen in die Bibliothek." icon="heart" />
            </div>
        </section>


        <section class="bc-home-discovery bc-home-v1__section bc-home-v1__discovery" aria-labelledby="home-discovery-heading" id="home-discovery">
            <div class="bc-home-live__section-top">
                <h2 id="home-discovery-heading">Zum Lesen entdeckt</h2>
                <a class="bc-home-text-link" href="{{ route('public.catalog.index') }}">Alle Medien ansehen <span aria-hidden="true">↗</span></a>
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


        {{-- Das Feature-Overlap liefert die einzige große Überschrift dieses Abschnitts. --}}
        <section class="bc-home-editorial bc-home-v1__editorial" aria-label="Geschichten verbinden Menschen" id="home-editorial">
            <x-library.feature-overlap
                title="Geschichten verbinden Menschen."
                description="Eine Schulbibliothek ist mehr als ein Regal: Hier treffen Geschichten, neue Ideen und unterschiedliche Perspektiven aufeinander."
                :href="route('public.series.index')"
                action-label="Buchreihen entdecken"
                tone="mint" />
        </section>


        <section class="bc-home-info bc-home-v1__service" aria-labelledby="home-info-heading" id="home-service">
            <h2 id="home-info-heading">Gut zu wissen</h2>
            <div class="bc-home-v1__service-grid">
                <article class="bc-home-v1__schedule-card" id="oeffnungszeiten" aria-labelledby="home-hours-heading">
                    <div class="bc-home-v1__schedule-header">
                        <h3 id="home-hours-heading">Öffnungszeiten</h3>
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
                </div>
            </div>
        </section>


        <div class="bc-home-live__top-link">
            <x-ui.back-to-top href="#bc-home-top" />
        </div>
    </div>
</x-app-shell>
