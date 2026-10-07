<x-app-shell surface="pos" title="Katalogpflege">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Katalogpflege"
        lead="Bibliografische Datensätze intern detailliert recherchieren, öffnen und pflegen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.home') }}">← Zurück zum Arbeitsplatz</a>
        <a href="{{ route('pos.catalog.intake.identify', ['neu' => 1]) }}"><strong>Medium erfassen</strong></a>
        <a href="{{ route('pos.catalog.quality.index') }}">Metadaten prüfen @if ($openQualityCases > 0)({{ $openQualityCases }} offen) @endif</a>
        @can('catalog.import')
            <a href="{{ route('pos.catalog.import.create') }}">Import</a>
        @endcan
        <a href="{{ route('pos.labels.copies') }}">Etiketten drucken</a>
        <a href="{{ route('public.catalog.index') }}">Öffentlichen Katalog öffnen</a>
    </div>

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die Eingaben.</x-ui.alert>
    @endif

    <section class="bc-catalog-search bc-catalog-search--advanced" aria-labelledby="catalog-search-heading">
        <div class="bc-section-heading"><h2 id="catalog-search-heading">Interne erweiterte Recherche</h2></div>
        <p class="bc-catalog-warning">
            Diese Suche ist bewusst ausführlicher als der öffentliche Schulkatalog. Mehrere Felder werden gemeinsam angewendet; Exemplar-Barcodes bleiben weiterhin außerhalb der Titelsuche.
        </p>

        <form method="get" action="{{ route('pos.catalog.index') }}" class="bc-catalog-search__advanced-form" role="search">
            <div class="bc-catalog-search__wide">
                <x-ui.input
                    label="Freie Suche"
                    name="q"
                    :value="$criteria->term"
                    placeholder="Titel, Person, ISBN, Verlag, Schlagwort, DNB-ID …"
                    autocomplete="off"
                />
            </div>

            <x-ui.input label="Titel / Untertitel" name="title" :value="$criteria->title" />
            <x-ui.input label="Verantwortliche / GND" name="contributor" :value="$criteria->contributor" />
            <x-ui.input label="Schlagwort / Inhalt" name="subject" :value="$criteria->subject" />
            <x-ui.input label="ISBN / ISSN / DOI" name="identifier" :value="$criteria->identifier" />
            <x-ui.input label="Verlag" name="publisher" :value="$criteria->publisher" />
            <x-ui.input label="Erscheinungsort" name="publication_place" :value="$criteria->publicationPlace" />
            <x-ui.input label="Reihe" name="series" :value="$criteria->series" />
            <x-ui.input label="Thema" name="topic" :value="$criteria->topic" />
            <x-ui.input label="Themenbereich" name="classification" :value="$criteria->classification" />
            <x-ui.input label="Zielgruppe" name="target_audience" :value="$criteria->targetAudience" />
            <x-ui.input label="DNB-/Quell-ID" name="source_record_id" :value="$criteria->sourceRecordId" />
            <x-ui.input label="Jahr von" name="year_from" type="number" :value="$criteria->yearFrom" min="1000" max="2100" />
            <x-ui.input label="Jahr bis" name="year_to" type="number" :value="$criteria->yearTo" min="1000" max="2100" />

            <x-ui.select label="Medientyp" name="media_type" data-auto-submit>
                <option value="">Alle Medientypen</option>
                @foreach ($filterOptions['mediaTypes'] as $mediaType)
                    <option value="{{ $mediaType }}" @selected($criteria->mediaType === $mediaType)>{{ $mediaType }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Sprache" name="language_code" data-auto-submit>
                <option value="">Alle Sprachen</option>
                @foreach ($filterOptions['languageCodes'] as $languageCode)
                    <option value="{{ $languageCode }}" @selected($criteria->languageCode === $languageCode)>{{ strtoupper($languageCode) }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Sortierung" name="sort" data-auto-submit>
                <option value="title" @selected($criteria->sort === 'title')>Titel A–Z</option>
                <option value="title_desc" @selected($criteria->sort === 'title_desc')>Titel Z–A</option>
                <option value="year_desc" @selected($criteria->sort === 'year_desc')>Erscheinungsjahr neu → alt</option>
                <option value="year_asc" @selected($criteria->sort === 'year_asc')>Erscheinungsjahr alt → neu</option>
                <option value="recent" @selected($criteria->sort === 'recent')>Zuletzt erfasst</option>
            </x-ui.select>

            <label class="bc-public-catalog-filter__check bc-catalog-search__wide" for="staff-active-only">
                <input id="staff-active-only" name="active_only" type="checkbox" value="1" @checked($criteria->activeCopiesOnly)>
                <span>
                    <strong>Nur Titel mit aktiven Exemplaren</strong>
                    <small>Filtert katalogseitig auf CopyStatus „active“.</small>
                </span>
            </label>
                <label class="bc-public-catalog-filter__check bc-catalog-search__wide" for="staff-available-only">
                <input id="staff-available-only" name="available_only" type="checkbox" value="1" @checked($criteria->availableNowOnly)>
                <span>
                    <strong>Nur jetzt verfügbare Titel</strong>
                    <small>Mindestens ein aktives Exemplar, das weder ausgeliehen noch zurückgelegt ist.</small>
                </span>
            </label>

            <div class="bc-action-row bc-catalog-search__wide">
                <x-ui.button type="submit">Intern suchen</x-ui.button>
                @if ($criteria->hasSearchInput())
                    <x-ui.button href="{{ route('pos.catalog.index') }}" variant="secondary">Suche leeren</x-ui.button>
                @endif
            </div>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-results-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="catalog-results-heading">Treffer</h2>
            <span>{{ $titles->total() }} {{ $criteria->hasSearchInput() ? 'gefunden' : 'Titel im Katalog' }}</span>
        </div>

        @if ($titles->count() === 0)
            <x-ui.alert title="Keine Treffer">Für diese Kombination wurde kein Titel gefunden.</x-ui.alert>
        @else
            <x-catalog.pagination-controls
                :paginator="$titles"
                :query-parameters="$queryParameters"
                route-name="pos.catalog.index"
                id-prefix="staff-catalog-top"
            />

            <x-ui.table>
                <thead>
                    <tr>
                        <th scope="col">Titel</th>
                        <th scope="col">Verantwortliche</th>
                        <th scope="col">Ausgaben / Metadaten</th>
                        <th scope="col"><span class="sr-only">Aktion</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($titles as $title)
                        @php
                            $latestEdition = $title->editions->sortByDesc(fn ($edition) => $edition->publication_year ?? 0)->first();
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $title->preferred_title }}</strong>
                                @if ($title->subtitle)
                                    <div class="bc-catalog-muted">{{ $title->subtitle }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $title->contributions->pluck('contributor.display_name')->filter()->join(', ') ?: '—' }}
                            </td>
                            <td>
                                @php
                                    $copyList = $title->editions->flatMap(fn ($edition) => $edition->copies);
                                    $available = $copyList->filter(fn ($copy): bool => $copy->status->value === 'active' && ! ($copyStates[(string) $copy->getKey()]->loaned ?? false) && ! ($copyStates[(string) $copy->getKey()]->held ?? false))->count();
                                @endphp
                                <strong>{{ $title->editions->count() }} Ausgabe(n)</strong>
                                @if ($latestEdition)
                                    <div class="bc-catalog-muted">
                                        {{ $latestEdition->publication_year ?: 'Jahr unbekannt' }}
                                        @if ($latestEdition->publisher_name) · {{ $latestEdition->publisher_name }} @endif
                                        @if ($latestEdition->isbn) · ISBN {{ $latestEdition->isbn }} @endif
                                    </div>
                                @endif
                                <details class="bc-catalog-copies-inline">
                                    <summary>{{ $copyList->count() }} {{ $copyList->count() === 1 ? 'Exemplar' : 'Exemplare' }}, {{ $available }} da</summary>
                                    <x-catalog.staff-copies :title="$title" :states="$copyStates" />
                                </details>
                            </td>
                            <td><a href="{{ route('pos.catalog.titles.show', ['titleId' => $title->getKey()]) }}">Öffnen</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-catalog.pagination-controls
                :paginator="$titles"
                :query-parameters="$queryParameters"
                route-name="pos.catalog.index"
                id-prefix="staff-catalog-bottom"
            />
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-create-heading">
        <div class="bc-section-heading"><h2 id="catalog-create-heading">Neuen Titel anlegen</h2></div>
        <p class="bc-section-copy">Hier wird zunächst nur der titelbezogene Kern angelegt. Ausgaben werden anschließend am Titel ergänzt.</p>

        <form method="post" action="{{ route('pos.catalog.titles.store') }}" class="bc-catalog-form">
            @csrf
            <div class="bc-catalog-form__grid">
                <x-ui.input
                    label="Haupttitel"
                    name="preferred_title"
                    :value="old('preferred_title')"
                    :error="$errors->first('preferred_title') ?: null"
                    required
                />
                <x-ui.input
                    label="Untertitel"
                    name="subtitle"
                    :value="old('subtitle')"
                    :error="$errors->first('subtitle') ?: null"
                />
                <x-ui.input
                    label="Sortiertitel"
                    name="sort_title"
                    :value="old('sort_title')"
                    hint="Optional. Kann später für sortierrelevante Artikel oder abweichende Ansetzungen genutzt werden."
                    :error="$errors->first('sort_title') ?: null"
                />
            </div>
            <div class="bc-action-row">
                <x-ui.button type="submit">Titel anlegen</x-ui.button>
            </div>
        </form>
    </section>
</x-app-shell>
