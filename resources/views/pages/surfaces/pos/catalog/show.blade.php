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

    @if (session('catalog_error'))
        <x-ui.alert variant="error" title="Nicht gespeichert">{{ session('catalog_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierten Eingaben.</x-ui.alert>
    @endif

    <form method="post" action="{{ route('pos.catalog.titles.feature', ['titleId' => $title->getKey()]) }}" class="bc-feature-form">
        @csrf
        @if ($title->featured_position !== null)
            <x-ui.badge variant="success">Auf der Startseite empfohlen</x-ui.badge>
            <button type="submit" class="bc-intake-linkbutton">Empfehlung zurücknehmen</button>
        @else
            <button type="submit" class="bc-intake-linkbutton">Auf der Startseite empfehlen</button>
        @endif
    </form>

    <section class="bc-content-section" id="exemplare" aria-labelledby="catalog-copies-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="catalog-copies-heading">Exemplare</h2>
            <span>{{ $title->editions->sum(fn ($edition) => $edition->copies->count()) }}</span>
        </div>
        <x-catalog.staff-copies :title="$title" :states="$copyStates" />
    </section>

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
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="catalog-contributors-heading">Verantwortliche</h2>
            <span>{{ $title->contributions->count() }}</span>
        </div>

        @if ($title->contributions->isEmpty())
            <x-ui.alert title="Noch keine Verantwortlichen">Lege unten die erste Person oder Körperschaft für diesen Titel an.</x-ui.alert>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th scope="col">Pos.</th>
                        <th scope="col">Name</th>
                        <th scope="col">Rolle</th>
                        <th scope="col"><span class="sr-only">Aktion</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($title->contributions as $contribution)
                        <tr>
                            <td class="bc-tabular">{{ $contribution->position }}</td>
                            <td>
                                <strong>{{ $contribution->contributor->display_name }}</strong>
                                @if ($contribution->contributor->sort_name)
                                    <div class="bc-catalog-muted">{{ $contribution->contributor->sort_name }}</div>
                                @endif
                            </td>
                            <td><code>{{ $contribution->role_key }}</code></td>
                            <td>
                                <a href="{{ route('pos.catalog.contributions.edit', [
                                    'titleId' => $title->getKey(),
                                    'contributionId' => $contribution->getKey(),
                                ]) }}">Bearbeiten</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif

        <form method="post" action="{{ route('pos.catalog.contributions.store', ['titleId' => $title->getKey()]) }}" class="bc-catalog-form bc-catalog-contribution-form">
            @csrf
            <div class="bc-catalog-form__grid">
                <x-ui.input
                    label="Anzeigename"
                    name="display_name"
                    :value="old('display_name')"
                    placeholder="z. B. Michael Ende"
                    :error="$errors->first('display_name') ?: null"
                    required
                />
                <x-ui.input
                    label="Sortiername"
                    name="sort_name"
                    :value="old('sort_name')"
                    placeholder="z. B. Ende, Michael"
                    :error="$errors->first('sort_name') ?: null"
                />
                <x-ui.input
                    label="Rollen-Schlüssel"
                    name="role_key"
                    :value="old('role_key', 'author')"
                    hint="Offener technischer Schlüssel, z. B. author, illustrator oder translator."
                    :error="$errors->first('role_key') ?: null"
                    required
                />
                <x-ui.input
                    label="Reihenfolge"
                    name="position"
                    type="number"
                    min="0"
                    max="9999"
                    :value="old('position', $title->contributions->count() + 1)"
                    :error="$errors->first('position') ?: null"
                    required
                />
            </div>
            <div class="bc-action-row"><x-ui.button type="submit">Verantwortliche:n hinzufügen</x-ui.button></div>
        </form>
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
