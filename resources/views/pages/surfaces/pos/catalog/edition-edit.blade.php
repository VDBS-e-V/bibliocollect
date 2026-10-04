<x-app-shell surface="pos" title="Ausgabe bearbeiten">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Ausgabe bearbeiten"
        :lead="$edition->title->preferred_title"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.titles.show', ['titleId' => $edition->title_id]) }}">← Zurück zum Titel</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierten Eingaben.</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="catalog-edition-edit-heading">
        <div class="bc-section-heading"><h2 id="catalog-edition-edit-heading">Editionsdaten</h2></div>

        <form method="post" action="{{ route('pos.catalog.editions.update', ['editionId' => $edition->getKey()]) }}" class="bc-catalog-form">
            @csrf
            @method('PATCH')
            <div class="bc-catalog-form__grid">
                <x-ui.input label="Auflagen-/Ausgabebezeichnung" name="edition_statement" :value="old('edition_statement', $edition->edition_statement)" :error="$errors->first('edition_statement') ?: null" />
                <x-ui.input label="ISBN" name="isbn" :value="old('isbn', $edition->isbn)" :error="$errors->first('isbn') ?: null" />
                <x-ui.input label="Verlag" name="publisher_name" :value="old('publisher_name', $edition->publisher_name)" :error="$errors->first('publisher_name') ?: null" />
                <x-ui.input label="Erscheinungsjahr" name="publication_year" type="number" min="1000" max="2100" :value="old('publication_year', $edition->publication_year)" :error="$errors->first('publication_year') ?: null" />
                <x-ui.input label="Medientyp" name="media_type" :value="old('media_type', $edition->media_type)" :error="$errors->first('media_type') ?: null" />
                <x-ui.input label="Sprachcode" name="language_code" :value="old('language_code', $edition->language_code)" :error="$errors->first('language_code') ?: null" />
                <x-ui.input label="Mindestalter" name="minimum_age" type="number" min="0" max="18" :value="old('minimum_age', $edition->minimum_age)" :error="$errors->first('minimum_age') ?: null" />
                <x-ui.input label="Altersfreigabe (Anzeige)" name="age_rating_label" :value="old('age_rating_label', $edition->age_rating_label)" :error="$errors->first('age_rating_label') ?: null" />
            </div>
            <div class="bc-action-row">
                <x-ui.button type="submit">Ausgabe speichern</x-ui.button>
                <x-ui.button href="{{ route('pos.catalog.titles.show', ['titleId' => $edition->title_id]) }}" variant="secondary">Abbrechen</x-ui.button>
            </div>
        </form>
    </section>
</x-app-shell>
