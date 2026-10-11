<x-app-shell surface="public" title="Katalog">
    <x-content.block key="home.notice" />

    {{-- Wiederverwendbare öffentliche Markenbühne: Suche bleibt die echte Katalogroute. --}}
    <section class="bc-public-home-stage" aria-labelledby="home-heading">
        <div class="bc-public-home-stage__copy">
            <p class="bc-public-home-stage__eyebrow">BiblioCollect · VDBS</p>
            <h1 id="home-heading">Die Schulbibliothek.</h1>
            <p class="bc-public-home-stage__lead">Medien finden, ausleihen und gemeinsam entdecken. Deine Schulbibliothek ist ein Ort für Geschichten, Wissen und neue Ideen.</p>

            <div class="bc-public-home-stage__search">
                <h2 id="catalog-heading">Katalog durchsuchen</h2>
                <x-library.catalog-search id="catalog-search" />
                <div class="bc-public-home-stage__links">
                    <a href="{{ route('public.catalog.advanced') }}">Erweiterte Suche</a>
                    <a href="{{ route('public.series.index') }}">Buchreihen entdecken</a>
                </div>
            </div>
        </div>

        <div class="bc-public-home-stage__art" aria-hidden="true">
            <img src="/brand/vdbs/figures-chain.svg" alt="">
        </div>
    </section>

    <section class="bc-public-shortcuts" aria-labelledby="shortcuts-heading">
        <header class="bc-public-shortcuts__heading">
            <p>Schnell zum Ziel</p>
            <h2 id="shortcuts-heading">Was möchtest du tun?</h2>
        </header>
        <div class="bc-public-shortcuts__grid">
            <x-library.quick-action
                :href="route('public.catalog.index')"
                icon="search"
                title="Medien finden"
                description="Im gesamten Katalog suchen und stöbern."
            />
            @can('surface.portal.access')
                <x-library.quick-action
                    :href="route('portal.home')"
                    icon="user"
                    title="Meine Ausleihen"
                    description="Ausleihen, Verlängerungen und Vormerkungen ansehen."
                />
            @else
                <x-library.quick-action
                    :href="route('login')"
                    icon="user"
                    title="Mein Konto"
                    description="Anmelden und eigene Ausleihen verwalten."
                />
            @endcan
            <x-library.quick-action
                :href="route('public.series.index')"
                icon="book"
                title="Buchreihen"
                description="Zusammengehörige Geschichten entdecken."
            />
            <x-library.quick-action
                :href="route('public.wishes.create')"
                icon="heart"
                title="Buchwunsch abgeben"
                description="Ein Medium fehlt? Mach einen Vorschlag."
            />
        </div>
    </section>

    @foreach ([
        'recommended' => ['title' => 'Empfohlene Medien', 'id' => 'recommended-heading', 'lead' => 'Von der Bibliothek ausgesucht und beliebt bei euch.'],
        'new' => ['title' => 'Neue Medien', 'id' => 'new-heading', 'lead' => 'Frisch ins Regal gekommen.'],
    ] as $key => $block)
        @if (($showcase[$key] ?? []) !== [])
            <section class="bc-showcase" aria-labelledby="{{ $block['id'] }}">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="{{ $block['id'] }}">{{ $block['title'] }}</h2>
                    <span>{{ $block['lead'] }}</span>
                </div>
                <ul class="bc-showcase__grid">
                    @foreach ($showcase[$key] as $tile)
                        <li class="bc-showcase__tile">
                            <a href="{{ route('public.catalog.show', ['titleId' => $tile['id']]) }}" class="bc-showcase__link">
                                <span class="bc-showcase__cover"><img src="{{ $tile['cover'] }}" alt="" loading="lazy"></span>
                                <strong class="bc-showcase__title">{{ $tile['title'] }}</strong>
                                @if ($tile['authors'] !== '')<span class="bc-showcase__authors">{{ $tile['authors'] }}</span>@endif
                            </a>
                            <x-ui.badge :variant="$tile['variant']">{{ $tile['badge'] }}</x-ui.badge>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach

    @if (($showcase['topics'] ?? []) !== [])
        <section class="bc-showcase" aria-labelledby="topics-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="topics-heading">Empfohlene Themen</h2>
                <span>Stöbern nach Thema.</span>
            </div>
            <ul class="bc-showcase__topics">
                @foreach ($showcase['topics'] as $topic)
                    <li>
                        <a href="{{ route('public.topic', ['key' => $topic['key']]) }}" class="bc-showcase__topic">
                            <strong>{{ $topic['name'] }}</strong>
                            @if (! empty($topic['description']))<span>{{ \Illuminate\Support\Str::limit($topic['description'], 90) }}</span>@endif
                            <small>{{ $topic['count'] }} Titel</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="bc-home-layout">
        <div class="bc-home-layout__main">
            <section class="bc-content-section" aria-labelledby="quick-heading">
                <div class="bc-section-heading">
                    <h2 id="quick-heading">Schnell finden</h2>
                </div>
                <div class="bc-link-list">
                    <div class="bc-link-list__row">
                        <div><a href="{{ route('public.catalog.index') }}"><strong>Im Katalog stöbern</strong></a><span>Alle Titel und Ausgaben, mit Filter „nur jetzt verfügbare Titel“</span></div>
                    </div>
                    <div class="bc-link-list__row">
                        <div><a href="{{ route('public.wishes.create') }}"><strong>Buchwunsch abgeben</strong></a><span>Dir fehlt ein Buch? Sag es uns, auch ohne Anmeldung</span></div>
                    </div>
                    @guest
                        <div class="bc-link-list__row">
                            <div><a href="{{ route('identity.claim.create') }}"><strong>Onlinekonto aktivieren</strong></a><span>Mit dem Code aus der Bibliothek: verlängern, vormerken, eigene Ausleihen sehen</span></div>
                        </div>
                    @endguest
                </div>
            </section>

            <section class="bc-content-section" aria-labelledby="hours-heading">
                <div class="bc-section-heading">
                    <h2 id="hours-heading">Öffnungszeiten</h2>
                </div>
                @if ($hours === [])
                    <p class="bc-section-copy">Die Öffnungszeiten werden noch eingetragen. Frag bitte in der Schule nach.</p>
                @else
                    <table class="bc-calendar-table">
                        <caption class="sr-only">Öffnungszeiten der Bibliothek</caption>
                        <thead><tr><th scope="col">Tag</th><th scope="col">Zeit</th></tr></thead>
                        <tbody>
                            @foreach ($hours as $hour)
                                <tr><th scope="row">{{ $hour['day'] }}</th><td class="bc-tabular">{{ $hour['time'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                @if ($closures !== [])
                    <h3>Geschlossen in den nächsten Wochen</h3>
                    <ul class="bc-plain-links">
                        @foreach ($closures as $closure)
                            <li><span class="bc-tabular">{{ $closure['date'] }}</span>@if ($closure['reason']) · {{ $closure['reason'] }}@endif</li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="bc-content-section" aria-labelledby="info-heading">
                <div class="bc-section-heading">
                    <h2 id="info-heading">So funktioniert die Ausleihe</h2>
                </div>
                <div class="bc-notice-row">
                    <strong>Ausleihe vor Ort</strong>
                    <p>Du bekommst einen Bibliotheksausweis. Damit kannst du bis zu {{ $maxLoans }} Medien gleichzeitig für {{ $loanDays }} Tage ausleihen. Ein Onlinekonto brauchst du dafür nicht.</p>
                </div>
                <div class="bc-notice-row">
                    <strong>Verlängern und vormerken</strong>
                    <p>{{ $maxRenewals > 0 ? "Du kannst eine Ausleihe bis zu {$maxRenewals} Mal verlängern, wenn niemand den Titel vorgemerkt hat." : 'Verlängern ist zurzeit nicht vorgesehen.' }} {{ $reservations ? 'Ist ein Titel ausgeliehen, kannst du ihn vormerken. Wir legen ihn für dich zurück, sobald er wieder da ist.' : '' }}</p>
                </div>
            </section>
        </div>

        <aside class="bc-home-layout__aside" aria-label="Service">
            <section class="bc-side-panel" aria-labelledby="account-heading">
                <h2 id="account-heading">Mein Konto</h2>
                <p>Eigene Ausleihen, Vormerkungen und Buchwünsche verwalten.</p>
                @auth
                    @can('surface.portal.access')
                        <x-ui.button class="mt-4" variant="secondary" :href="route('portal.home')">Zum Konto</x-ui.button>
                    @endcan
                @else
                    <x-ui.button class="mt-4" variant="secondary" :href="route('login')">Anmelden</x-ui.button>
                @endauth
            </section>

            <section class="bc-side-panel bc-side-panel--quiet" aria-labelledby="help-heading">
                <h2 id="help-heading">Fragen?</h2>
                <p>Sprich uns in der Bibliothek an. Hinweise zum Datenschutz und zur Barrierefreiheit stehen unten auf jeder Seite.</p>
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
