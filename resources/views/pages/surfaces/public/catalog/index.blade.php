<x-app-shell surface="public" title="Katalog">
    <x-ui.page-header
        kicker="Öffentlicher Katalog"
        title="Medien finden"
        lead="Durchsuche Titel, Verantwortliche und Ausgaben. Filter helfen dir, den Bestand gezielt einzugrenzen."
    />

    <section class="bc-public-catalog-search" aria-labelledby="public-catalog-search-heading">
        <div class="bc-public-catalog-search__heading">
            <div>
                <p class="bc-eyebrow">Recherche</p>
                <h2 id="public-catalog-search-heading">Katalog durchsuchen</h2>
            </div>
            <a href="{{ route('public.catalog.index') }}">Filter zurücksetzen</a>
        </div>

        <form method="get" action="{{ route('public.catalog.index') }}" class="bc-public-catalog-filter" role="search">
            <div class="bc-public-catalog-filter__query">
                <x-ui.input
                    label="Suchbegriff"
                    name="q"
                    type="search"
                    :value="$criteria->term"
                    placeholder="Titel, Autor:in, ISBN, Verlag …"
                    hint="Mindestens zwei Buchstaben oder Ziffern. Exemplar-Barcodes gehören bewusst nicht zur öffentlichen Titelsuche."
                    autocomplete="off"
                />
            </div>

            <div class="bc-public-catalog-filter__options">
                <x-ui.select label="Medientyp" name="media_type">
                    <option value="">Alle Medientypen</option>
                    @foreach ($filterOptions['mediaTypes'] as $mediaType)
                        <option value="{{ $mediaType }}" @selected($criteria->mediaType === $mediaType)>
                            {{ $presenter->mediaTypeLabel($mediaType) }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.select label="Sprache" name="language_code">
                    <option value="">Alle Sprachen</option>
                    @foreach ($filterOptions['languageCodes'] as $languageCode)
                        <option value="{{ $languageCode }}" @selected($criteria->languageCode === $languageCode)>
                            {{ $presenter->languageLabel($languageCode) }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.select label="Sortierung" name="sort">
                    <option value="title" @selected($criteria->sort === 'title')>Titel A–Z</option>
                    <option value="recent" @selected($criteria->sort === 'recent')>Zuletzt erfasst</option>
                </x-ui.select>
            </div>

            <label class="bc-public-catalog-filter__check" for="active-only">
                <input
                    id="active-only"
                    name="active_only"
                    type="checkbox"
                    value="1"
                    @checked($criteria->activeCopiesOnly)
                >
                <span>
                    <strong>Nur Titel mit aktiven Exemplaren</strong>
                    <small>„Aktiv“ beschreibt den Katalogstatus des Exemplars, noch nicht den späteren Ausleihstatus.</small>
                </span>
            </label>

            <div class="bc-action-row">
                <x-ui.button type="submit">Suchen und filtern</x-ui.button>
                <x-ui.button href="{{ route('public.catalog.index') }}" variant="secondary">Alle Titel</x-ui.button>
            </div>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="public-catalog-results-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="public-catalog-results-heading">Treffer</h2>
            <span>{{ $titles->total() }}</span>
        </div>

        <p class="bc-public-catalog-note">
            Die Bestandsanzeige zeigt derzeit katalogseitig aktive Exemplare. Ob ein aktives Exemplar gerade ausgeliehen ist,
            kann erst das spätere Circulation-Modul beantworten.
        </p>

        @if ($titles->count() === 0)
            <div class="bc-public-catalog-empty">
                <strong>Keine passenden Titel gefunden.</strong>
                <p>Versuche einen anderen Suchbegriff oder entferne einzelne Filter.</p>
                <x-ui.button href="{{ route('public.catalog.index') }}" variant="secondary">Gesamten Katalog anzeigen</x-ui.button>
            </div>
        @else
            <div class="bc-public-catalog-results">
                @foreach ($titles as $title)
                    @php
                        $holding = $holdingSummaries[(string) $title->getKey()];
                    @endphp
                    <article class="bc-public-result">
                        <div class="bc-public-result__main">
                            <div class="bc-public-result__heading">
                                <div>
                                    <h3>
                                        <a href="{{ route('public.catalog.show', ['titleId' => $title->getKey()]) }}">
                                            {{ $title->preferred_title }}
                                        </a>
                                    </h3>
                                    @if ($title->subtitle)
                                        <p class="bc-public-result__subtitle">{{ $title->subtitle }}</p>
                                    @endif
                                </div>
                                <x-ui.badge :variant="$presenter->holdingVariant($holding)">
                                    {{ $presenter->holdingLabel($holding) }}
                                </x-ui.badge>
                            </div>

                            @if ($title->contributions->isNotEmpty())
                                <p class="bc-public-result__contributors">
                                    @foreach ($title->contributions as $contribution)
                                        <span>
                                            {{ $contribution->contributor->display_name }}
                                            <small>{{ $presenter->roleLabel($contribution->role_key) }}</small>
                                        </span>@if (! $loop->last)<span aria-hidden="true"> · </span>@endif
                                    @endforeach
                                </p>
                            @endif

                            <div class="bc-public-result__metadata" aria-label="Ausgabemerkmale">
                                @foreach ($title->editions->pluck('media_type')->filter()->unique() as $mediaType)
                                    <span>{{ $presenter->mediaTypeLabel($mediaType) }}</span>
                                @endforeach
                                @foreach ($title->editions->pluck('language_code')->filter()->unique() as $languageCode)
                                    <span>{{ $presenter->languageLabel($languageCode) }}</span>
                                @endforeach
                                @if ($title->editions->max('publication_year'))
                                    <span>bis {{ $title->editions->max('publication_year') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="bc-public-result__aside">
                            @if ($holding->shelfLocations !== [])
                                <div>
                                    <strong>Standorte</strong>
                                    <span>{{ implode(', ', $holding->shelfLocations) }}</span>
                                </div>
                            @endif
                            <div>
                                <strong>Ausgaben</strong>
                                <span>{{ $title->editions->count() }}</span>
                            </div>
                            <a href="{{ route('public.catalog.show', ['titleId' => $title->getKey()]) }}">Titeldetails ansehen →</a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($titles->lastPage() > 1)
                <nav class="bc-public-pagination" aria-label="Seitennavigation der Katalogtreffer">
                    @if ($titles->onFirstPage())
                        <span aria-disabled="true">← Zurück</span>
                    @else
                        <a href="{{ $titles->previousPageUrl() }}" rel="prev">← Zurück</a>
                    @endif

                    <span>Seite {{ $titles->currentPage() }} von {{ $titles->lastPage() }}</span>

                    @if ($titles->hasMorePages())
                        <a href="{{ $titles->nextPageUrl() }}" rel="next">Weiter →</a>
                    @else
                        <span aria-disabled="true">Weiter →</span>
                    @endif
                </nav>
            @endif
        @endif
    </section>
</x-app-shell>
