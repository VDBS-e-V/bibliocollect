<x-app-shell surface="public" title="Katalog">
    <header class="bc-public-catalog-hero">
        <div>
            <p class="bc-eyebrow">Öffentlicher Katalog</p>
            <h1>Medien finden, die zu dir passen</h1>
            <p>Finde Bücher und andere Medien unserer Schulbibliothek. Für eine genauere Recherche gibt es zusätzlich die erweiterte Suche.</p>
        </div>

        <form method="get" action="{{ route('public.catalog.index') }}" class="bc-public-hero-search" role="search">
            <label for="public-catalog-query">Im Katalog suchen</label>
            <div class="bc-public-hero-search__row">
                <input
                    id="public-catalog-query"
                    name="q"
                    type="search"
                    value="{{ $criteria->term }}"
                    placeholder="Titel, Autor:in, Thema oder ISBN"
                    autocomplete="off"
                >
                <x-ui.button type="submit">Suchen</x-ui.button>
            </div>
            <div class="bc-public-hero-search__links">
                <span>Mindestens zwei Buchstaben oder Ziffern.</span>
                <a href="{{ route('public.catalog.advanced', $queryParameters) }}">Erweiterte Suche</a>
                <a href="{{ route('public.series.index') }}">Reihen</a>
            </div>
        </form>
    </header>

    @if (session('bookmark_notice'))
        <x-ui.alert variant="success" title="Merkliste">{{ session('bookmark_notice') }} @auth<a href="{{ route('portal.bookmarks') }}">Zur Merkliste</a>@endauth</x-ui.alert>
    @endif
    @if (session('bookmark_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('bookmark_error') }}</x-ui.alert>
    @endif

    @if ($criteria->shelf)
        <section class="bc-public-advanced-active" role="status" aria-label="Regalbrett">
            <strong>Regalbrett {{ $shelf?->code ?? $criteria->shelf }}@if ($shelf && trim((string) $shelf->label) !== ''): {{ $shelf->label }}@endif</strong>
            @if ($shelf)
                @php
                    $where = array_values(array_filter([$shelf->rack?->display(), $shelf->rack?->parent?->display(), $shelf->rack?->parent?->parent?->display()]));
                @endphp
                @if ($where !== [])<span>{{ implode(' › ', $where) }}</span>@endif
            @endif
            <span>Das sind die Medien, die auf diesem Brett stehen.</span>
            <a href="{{ route('public.catalog.index') }}">Ganzen Katalog zeigen</a>
        </section>
    @endif

    @if ($criteria->theme)
        <section class="bc-public-advanced-active" role="status" aria-label="Thema">
            <strong>Thema {{ $theme?->name ?? $criteria->theme }}</strong>
            @if ($theme && trim((string) $theme->description) !== '')
                <span>{{ $theme->description }}</span>
            @endif
            @if ($theme && $theme->shelves->isNotEmpty())
                <span>Zu finden auf:
                    @foreach ($theme->shelves->sortBy('code', SORT_NATURAL) as $themeShelf)
                        <a href="{{ route('public.shelf', ['code' => $themeShelf->publicSlug()]) }}">{{ $themeShelf->code }}</a>@if (trim((string) $themeShelf->label) !== '') <small>({{ $themeShelf->label }})</small>@endif@if (! $loop->last), @endif
                    @endforeach
                </span>
            @else
                <span>Für dieses Thema ist noch kein Regalbrett eingetragen.</span>
            @endif
            <a href="{{ route('public.catalog.index') }}">Ganzen Katalog zeigen</a>
        </section>
    @endif

    <section class="bc-public-catalog-search" aria-labelledby="public-catalog-filter-heading">
        <div class="bc-public-catalog-search__heading">
            <div>
                <p class="bc-eyebrow">Schnell filtern</p>
                <h2 id="public-catalog-filter-heading">Treffer eingrenzen</h2>
            </div>
            <a href="{{ route('public.catalog.index') }}">Alles zurücksetzen</a>
        </div>

        <form method="get" action="{{ route('public.catalog.index') }}" class="bc-public-catalog-filter">
            @if ($criteria->term)
                <input type="hidden" name="q" value="{{ $criteria->term }}">
            @endif
            @if ($criteria->shelf)
                <input type="hidden" name="regalbrett" value="{{ $criteria->shelf }}">
            @endif
            @if ($criteria->theme)
                <input type="hidden" name="thema" value="{{ $criteria->theme }}">
            @endif
            @foreach (['title', 'contributor', 'subject', 'identifier', 'publisher', 'topic'] as $advancedField)
                @php
                    $advancedValue = $criteria->{$advancedField};
                @endphp
                @if ($advancedValue)
                    <input type="hidden" name="{{ $advancedField }}" value="{{ $advancedValue }}">
                @endif
            @endforeach
            @if ($criteria->yearFrom)
                <input type="hidden" name="year_from" value="{{ $criteria->yearFrom }}">
            @endif
            @if ($criteria->yearTo)
                <input type="hidden" name="year_to" value="{{ $criteria->yearTo }}">
            @endif

            <div class="bc-public-catalog-filter__options">
                <x-ui.select label="Medientyp" name="media_type" data-auto-submit>
                    <option value="">Alle Medientypen</option>
                    @foreach ($filterOptions['mediaTypes'] as $mediaType)
                        <option value="{{ $mediaType }}" @selected($criteria->mediaType === $mediaType)>
                            {{ $presenter->mediaTypeLabel($mediaType) }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.select label="Sprache" name="language_code" data-auto-submit>
                    <option value="">Alle Sprachen</option>
                    @foreach ($filterOptions['languageCodes'] as $languageCode)
                        <option value="{{ $languageCode }}" @selected($criteria->languageCode === $languageCode)>
                            {{ $presenter->languageLabel($languageCode) }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.select label="Sortierung" name="sort" data-auto-submit>
                    <option value="title" @selected($criteria->sort === 'title')>Titel A–Z</option>
                    <option value="title_desc" @selected($criteria->sort === 'title_desc')>Titel Z–A</option>
                    <option value="year_desc" @selected($criteria->sort === 'year_desc')>Neuere Erscheinungsjahre zuerst</option>
                    <option value="year_asc" @selected($criteria->sort === 'year_asc')>Ältere Erscheinungsjahre zuerst</option>
                    <option value="recent" @selected($criteria->sort === 'recent')>Zuletzt im Katalog erfasst</option>
                </x-ui.select>
            </div>

            <div class="bc-public-catalog-filter__footer">
                <label class="bc-public-catalog-filter__check" for="available-only">
                    <input
                        id="available-only"
                        name="available_only"
                        data-auto-submit
                        type="checkbox"
                        value="1"
                        @checked($criteria->availableNowOnly)
                    >
                    <span>
                        <strong>Nur jetzt verfügbare Titel</strong>
                        <small>Mindestens ein Exemplar steht im Regal und ist weder ausgeliehen noch zurückgelegt.</small>
                    </span>
                </label>

                <label class="bc-public-catalog-filter__check" for="active-only">
                    <input
                        id="active-only"
                        name="active_only"
                        data-auto-submit
                        type="checkbox"
                        value="1"
                        @checked($criteria->activeCopiesOnly)
                    >
                    <span>
                        <strong>Nur Titel mit aktiven Exemplaren</strong>
                        <small>Aktiv beschreibt den Katalogstatus; ob ein Exemplar gerade ausleihbar ist, steht bei den Treffern.</small>
                    </span>
                </label>

                <noscript><x-ui.button type="submit" variant="secondary">Filter anwenden</x-ui.button></noscript>
            </div>
        </form>

        @if ($criteria->hasAdvancedFilters())
            <div class="bc-public-advanced-active" role="status">
                <strong>Erweiterte Suchkriterien sind aktiv.</strong>
                <a href="{{ route('public.catalog.advanced', $queryParameters) }}">Kriterien bearbeiten</a>
            </div>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="public-catalog-results-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="public-catalog-results-heading">Treffer</h2>
            <span>{{ $titles->total() }}</span>
        </div>

        @if ($titles->count() === 0)
            <div class="bc-public-catalog-empty">
                <strong>Keine passenden Titel gefunden.</strong>
                <p>Versuche einen allgemeineren Suchbegriff oder entferne einzelne Filter.</p>
                <div class="bc-action-row">
                    <x-ui.button href="{{ route('public.catalog.index') }}" variant="secondary">Gesamten Katalog anzeigen</x-ui.button>
                    <x-ui.button href="{{ route('public.catalog.advanced') }}" variant="secondary">Erweiterte Suche</x-ui.button>
                </div>
                <p>Fehlt dir ein Buch? <a href="{{ route('public.wishes.create', array_filter(['titel' => request('q')])) }}">Wünsch es dir.</a></p>
            </div>
        @else
            <x-catalog.pagination-controls
                :paginator="$titles"
                :query-parameters="$queryParameters"
                route-name="public.catalog.index"
                id-prefix="public-catalog-top"
            />

            <div class="bc-public-catalog-results">
                @foreach ($titles as $title)
                    @php
                        $titleId = (string) $title->getKey();
                        $holding = $holdingSummaries[$titleId];
                        $topics = $topicNames[$titleId] ?? [];
                        $representativeEdition = $title->editions
                            ->sortByDesc(fn ($edition) => $edition->publication_year ?? 0)
                            ->first();
                    @endphp
                    <article class="bc-public-result">
                        <a
                            class="bc-public-result__cover-link"
                            href="{{ route('public.catalog.show', ['titleId' => $title->getKey()]) }}"
                            aria-label="{{ $title->preferred_title }} öffnen"
                        >
                            <span class="bc-public-result__cover-frame">
                                <img class="bc-public-result__cover" src="{{ $coverUrls[$titleId] }}" alt="" loading="lazy">
                            </span>
                        </a>

                        <div class="bc-public-result__main">
                            <p class="bc-public-result__type">
                                {{ $representativeEdition ? $presenter->mediaTypeLabel($representativeEdition->media_type) : 'Medium' }}
                            </p>
                            <h3>
                                <a href="{{ route('public.catalog.show', ['titleId' => $title->getKey()]) }}">
                                    {{ $title->preferred_title }}
                                </a>
                            </h3>

                            @if ($title->subtitle)
                                <p class="bc-public-result__subtitle">{{ $title->subtitle }}</p>
                            @endif

                            @if ($title->contributions->isNotEmpty())
                                <p class="bc-public-result__contributors">
                                    {{ $title->contributions->pluck('contributor.display_name')->filter()->join(', ') }}
                                </p>
                            @elseif ($representativeEdition?->responsibility_statement)
                                <p class="bc-public-result__contributors">{{ $representativeEdition->responsibility_statement }}</p>
                            @endif

                            <div class="bc-public-result__metadata" aria-label="Medienmerkmale">
                                @foreach ($title->editions->pluck('language_code')->filter()->unique()->take(2) as $languageCode)
                                    <span>{{ $presenter->languageLabel($languageCode) }}</span>
                                @endforeach
                                @if ($representativeEdition?->publication_year)
                                    <span>{{ $representativeEdition->publication_year }}</span>
                                @endif
                                @if ($representativeEdition?->publisher_name)
                                    <span>{{ $representativeEdition->publisher_name }}</span>
                                @endif
                                @if ($representativeEdition?->local_classification)
                                    <span>{{ $representativeEdition->local_classification }}</span>
                                @endif
                            </div>

                            @if ($topics !== [])
                                <p class="bc-public-result__topics">
                                    <strong>Themen:</strong> {{ implode(' · ', array_slice($topics, 0, 3)) }}
                                </p>
                            @endif
                        </div>

                        <div class="bc-public-result__aside">
                            <x-ui.badge :variant="$presenter->availabilityVariant($holding, $availabilities[$titleId])">
                                {{ $presenter->availabilityLabel($holding, $availabilities[$titleId]) }}
                            </x-ui.badge>
                            @if ($presenter->availabilityHint($availabilities[$titleId]))
                                <span class="bc-public-result__hint">{{ $presenter->availabilityHint($availabilities[$titleId]) }}</span>
                            @endif

                            <x-catalog.bookmark-button :title-id="$titleId" :marked="in_array($titleId, $bookmarked, true)" />

                            @if ($holding->shelfLocations !== [])
                                <div>
                                    <strong>Standort</strong>
                                    <span>{{ implode(', ', $holding->shelfLocations) }}</span>
                                </div>
                            @endif

                            <div>
                                <strong>Ausgaben</strong>
                                <span>{{ $title->editions->count() }}</span>
                            </div>

                            <a class="bc-public-result__details-link" href="{{ route('public.catalog.show', ['titleId' => $title->getKey()]) }}">
                                Mehr zum Titel →
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <x-catalog.pagination-controls
                :paginator="$titles"
                :query-parameters="$queryParameters"
                route-name="public.catalog.index"
                id-prefix="public-catalog-bottom"
            />
        @endif
    </section>
</x-app-shell>
