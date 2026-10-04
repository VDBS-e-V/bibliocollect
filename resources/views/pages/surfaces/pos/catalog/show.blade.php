<x-app-shell surface="pos" :title="$title->preferred_title">
    <x-ui.page-header
        kicker="Katalogpflege"
        :title="$title->preferred_title"
        lead="Titelstammdaten und zugehörige Ausgaben bearbeiten."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.index') }}">← Zurück zur Katalogpflege</a>
    </div>

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierten Eingaben.</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="catalog-title-data-heading">
        <div class="bc-section-heading"><h2 id="catalog-title-data-heading">Titeldaten</h2></div>

        <form method="post" action="{{ route('pos.catalog.titles.update', ['titleId' => $title->getKey()]) }}" class="bc-catalog-form">
            @csrf
            @method('PATCH')
            <div class="bc-catalog-form__grid">
                <x-ui.input
                    label="Haupttitel"
                    name="preferred_title"
                    :value="old('preferred_title', $title->preferred_title)"
                    :error="$errors->first('preferred_title') ?: null"
                    required
                />
                <x-ui.input
                    label="Untertitel"
                    name="subtitle"
                    :value="old('subtitle', $title->subtitle)"
                    :error="$errors->first('subtitle') ?: null"
                />
                <x-ui.input
                    label="Sortiertitel"
                    name="sort_title"
                    :value="old('sort_title', $title->sort_title)"
                    :error="$errors->first('sort_title') ?: null"
                />
            </div>
            <div class="bc-action-row"><x-ui.button type="submit">Titeldaten speichern</x-ui.button></div>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-contributors-heading">
        <div class="bc-section-heading"><h2 id="catalog-contributors-heading">Verantwortliche</h2></div>
        @if ($title->contributions->isEmpty())
            <p class="bc-section-copy">Noch keine Verantwortlichen hinterlegt. Die Bearbeitung folgt im nächsten Catalog-Schritt.</p>
        @else
            <x-ui.table>
                <thead><tr><th scope="col">Name</th><th scope="col">Rolle</th></tr></thead>
                <tbody>
                    @foreach ($title->contributions as $contribution)
                        <tr>
                            <td>{{ $contribution->contributor->display_name }}</td>
                            <td>{{ $contribution->role_key }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-editions-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="catalog-editions-heading">Ausgaben</h2>
            <span>{{ $title->editions->count() }}</span>
        </div>

        @if ($title->editions->isEmpty())
            <x-ui.alert title="Noch keine Ausgabe">Lege unten die erste konkrete Ausgabe dieses Titels an.</x-ui.alert>
        @else
            <div class="bc-catalog-editions">
                @foreach ($title->editions as $edition)
                    <article class="bc-catalog-edition">
                        <div>
                            <strong>{{ $edition->edition_statement ?: 'Ausgabe ohne Auflagenangabe' }}</strong>
                            <p>
                                {{ $edition->publisher_name ?: 'Verlag unbekannt' }}
                                @if ($edition->publication_year) · {{ $edition->publication_year }} @endif
                                @if ($edition->isbn) · ISBN {{ $edition->isbn }} @endif
                            </p>
                            <p>
                                {{ $edition->media_type ?: 'Medientyp offen' }}
                                @if ($edition->language_code) · {{ $edition->language_code }} @endif
                                @if ($edition->minimum_age !== null) · Mindestalter {{ $edition->minimum_age }} @endif
                                · {{ $edition->copies->count() }} Exemplare
                            </p>
                        </div>
                        <a href="{{ route('pos.catalog.editions.edit', ['editionId' => $edition->getKey()]) }}">Ausgabe bearbeiten</a>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-edition-create-heading">
        <div class="bc-section-heading"><h2 id="catalog-edition-create-heading">Ausgabe hinzufügen</h2></div>

        <form method="post" action="{{ route('pos.catalog.editions.store', ['titleId' => $title->getKey()]) }}" class="bc-catalog-form">
            @csrf
            <div class="bc-catalog-form__grid">
                <x-ui.input label="Auflagen-/Ausgabebezeichnung" name="edition_statement" :value="old('edition_statement')" :error="$errors->first('edition_statement') ?: null" />
                <x-ui.input label="ISBN" name="isbn" :value="old('isbn')" :error="$errors->first('isbn') ?: null" />
                <x-ui.input label="Verlag" name="publisher_name" :value="old('publisher_name')" :error="$errors->first('publisher_name') ?: null" />
                <x-ui.input label="Erscheinungsjahr" name="publication_year" type="number" min="1000" max="2100" :value="old('publication_year')" :error="$errors->first('publication_year') ?: null" />
                <x-ui.input label="Medientyp" name="media_type" :value="old('media_type')" hint="Noch freier Wert; kontrolliertes Vokabular folgt später." :error="$errors->first('media_type') ?: null" />
                <x-ui.input label="Sprachcode" name="language_code" :value="old('language_code')" hint="z. B. de, en oder ein später definiertes Importformat." :error="$errors->first('language_code') ?: null" />
                <x-ui.input label="Mindestalter" name="minimum_age" type="number" min="0" max="18" :value="old('minimum_age')" :error="$errors->first('minimum_age') ?: null" />
                <x-ui.input label="Altersfreigabe (Anzeige)" name="age_rating_label" :value="old('age_rating_label')" placeholder="z. B. ab 12" :error="$errors->first('age_rating_label') ?: null" />
            </div>
            <div class="bc-action-row"><x-ui.button type="submit">Ausgabe anlegen</x-ui.button></div>
        </form>
    </section>
</x-app-shell>
