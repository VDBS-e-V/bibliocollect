<x-app-shell surface="pos" title="Katalogpflege">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Katalogpflege"
        lead="Titel gezielt finden und neue bibliografische Datensätze anlegen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.home') }}">← Zurück zum Arbeitsplatz</a>
    </div>

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die Eingaben für den neuen Titel.</x-ui.alert>
    @endif

    <section class="bc-catalog-search" aria-labelledby="catalog-search-heading">
        <div class="bc-section-heading"><h2 id="catalog-search-heading">Titel finden</h2></div>
        <form method="get" action="{{ route('pos.catalog.index') }}" class="bc-catalog-search__form" role="search">
            <x-ui.input
                label="Titel, Verantwortliche, ISBN oder Verlag"
                name="q"
                :value="$term"
                hint="Mindestens zwei Buchstaben/Ziffern, maximal 25 Treffer."
                autocomplete="off"
            />
            <div class="bc-action-row">
                <x-ui.button type="submit">Suchen</x-ui.button>
                @if ($term !== '')
                    <x-ui.button href="{{ route('pos.catalog.index') }}" variant="secondary">Suche leeren</x-ui.button>
                @endif
            </div>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-results-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="catalog-results-heading">Treffer</h2>
            @if ($term !== '')
                <span>{{ $titles->count() }} gefunden</span>
            @endif
        </div>

        @if ($term === '')
            <x-ui.alert title="Gezielte Suche">Gib einen Suchbegriff ein, um vorhandene Titel zu öffnen.</x-ui.alert>
        @elseif ($titles->isEmpty())
            <x-ui.alert title="Keine Treffer">Für „{{ $term }}“ wurde kein Titel gefunden.</x-ui.alert>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th scope="col">Titel</th>
                        <th scope="col">Verantwortliche</th>
                        <th scope="col">Ausgaben</th>
                        <th scope="col"><span class="sr-only">Aktion</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($titles as $title)
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
                            <td>{{ $title->editions->count() }}</td>
                            <td><a href="{{ route('pos.catalog.titles.show', ['titleId' => $title->getKey()]) }}">Öffnen</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
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
