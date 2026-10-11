<x-app-shell surface="pos" title="Katalogpflege">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Katalogpflege"
        lead="Bibliografische Datensätze intern detailliert recherchieren, öffnen und pflegen."
    >
        @can('catalog.manage')
            <x-slot:actions>
                <x-ui.button href="{{ route('pos.catalog.intake.identify', ['neu' => 1]) }}">Medium erfassen</x-ui.button>
            </x-slot:actions>
        @endcan
    </x-ui.page-header>

    <div class="bc-context-actions">
        <a href="{{ route('pos.home') }}">← Zurück zum Arbeitsplatz</a>
        <a href="{{ route('pos.catalog.quality.index') }}">Metadaten prüfen @if ($openQualityCases > 0)({{ $openQualityCases }} offen) @endif</a>
        @can('catalog.import')
            <a href="{{ route('pos.catalog.import.create') }}">Import</a>
        @endcan
        <a href="{{ route('pos.labels.stock') }}">Etiketten drucken</a>
        <a href="{{ route('public.catalog.index') }}">Öffentlichen Katalog öffnen</a>
    </div>

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die Eingaben.</x-ui.alert>
    @endif

    @php
        $advancedActive = collect([
            $criteria->title, $criteria->contributor, $criteria->subject, $criteria->identifier,
            $criteria->publisher, $criteria->publicationPlace, $criteria->series, $criteria->topic,
            $criteria->classification, $criteria->targetAudience, $criteria->sourceRecordId,
            $criteria->yearFrom, $criteria->yearTo,
        ])->contains(fn ($value): bool => $value !== null && $value !== '');
    @endphp

    <form method="get" action="{{ route('pos.catalog.index') }}" class="bc-search-stack" role="search" aria-label="Interne Katalogsuche">
        <section class="bc-search-card" aria-labelledby="search-simple-heading">
            <h2 id="search-simple-heading" class="bc-search-card__title">Einfache Suche</h2>
            <div class="bc-search-card__body bc-search-simple">
                <x-ui.input
                    label="Suche"
                    name="q"
                    :value="$criteria->term"
                    placeholder="Titel, Person, ISBN, Verlag, Schlagwort, DNB-ID …"
                    autocomplete="off"
                />
                <x-ui.button type="submit">Suchen</x-ui.button>
            </div>
        </section>

        <details class="bc-search-card" @if ($advancedActive) open @endif>
            <summary class="bc-search-card__title">Erweiterte Suche</summary>
            <div class="bc-search-card__body">
                <p class="bc-catalog-muted">Mehrere Felder werden gemeinsam angewendet. Exemplar-Barcodes bleiben außerhalb der Titelsuche.</p>
                <div class="bc-search-advanced-grid">
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
                </div>
            </div>
            <div class="bc-search-card__footer">
                <x-ui.button type="submit">Erweitert suchen</x-ui.button>
            </div>
        </details>

        <section class="bc-search-card" aria-labelledby="search-filter-heading">
            <h2 id="search-filter-heading" class="bc-search-card__title">Filter</h2>
            <div class="bc-search-card__body bc-search-filters">
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

                <label class="bc-search-check" for="staff-active-only" title="Nur Titel mit aktiven Exemplaren">
                    <input id="staff-active-only" name="active_only" type="checkbox" value="1" data-auto-submit @checked($criteria->activeCopiesOnly)>
                    <span>Mit aktiven Exemplaren</span>
                </label>
                <label class="bc-search-check" for="staff-available-only" title="Nur jetzt verfügbare Titel: mindestens ein aktives Exemplar, das weder ausgeliehen noch zurückgelegt ist.">
                    <input id="staff-available-only" name="available_only" type="checkbox" value="1" data-auto-submit @checked($criteria->availableNowOnly)>
                    <span>Jetzt verfügbar</span>
                </label>

                <div class="bc-search-filters__actions">
                    @if ($criteria->hasSearchInput())
                        <x-ui.button href="{{ route('pos.catalog.index') }}" variant="secondary">Zurücksetzen</x-ui.button>
                    @endif
                </div>
            </div>
        </section>
    </form>

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

            <form method="post" action="{{ route('pos.catalog.bulk-location.preview') }}">
                @csrf
                <div class="bc-action-row">
                    <label for="bulk-target">Ziel-Regalbrett</label>
                    <select id="bulk-target" name="shelf" required>
                        <option value="">Bitte wählen</option>
                        @foreach (\App\Modules\Catalog\Models\CatalogShelf::query()->where('is_active', true)->orderBy('code')->get() as $shelfOption)
                            <option value="{{ $shelfOption->code }}">{{ $shelfOption->display() }}</option>
                        @endforeach
                    </select>
                    <x-ui.button type="submit" variant="secondary">Ausgewählte Exemplare: Vorschau</x-ui.button>
                </div>
                <p class="bc-section-copy">Maximal 50 Exemplare pro Stapel. Die Vorschau schreibt noch keine Daten.</p>
            <x-ui.table>
                <thead>
                    <tr>
                        <th scope="col">Auswahl</th>
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
                                @foreach ($title->editions as $edition)
                                    @foreach ($edition->copies as $copy)
                                        <label style="display:block">
                                            <input type="checkbox" name="copies[]" value="{{ $copy->getKey() }}">
                                            {{ $copy->barcode }}
                                        </label>
                                    @endforeach
                                @endforeach
                            </td>
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
            </form>

            <x-catalog.pagination-controls
                :paginator="$titles"
                :query-parameters="$queryParameters"
                route-name="pos.catalog.index"
                id-prefix="staff-catalog-bottom"
            />
        @endif
    </section>
</x-app-shell>
