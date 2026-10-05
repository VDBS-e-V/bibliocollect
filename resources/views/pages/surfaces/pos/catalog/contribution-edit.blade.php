<x-app-shell surface="pos" title="Verantwortlichkeit bearbeiten">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Verantwortlichkeit bearbeiten"
        :lead="$title->preferred_title"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.titles.show', ['titleId' => $title->getKey()]) }}">← Zurück zum Titel</a>
    </div>

    @if (session('catalog_error'))
        <x-ui.alert variant="error" title="Nicht gespeichert">{{ session('catalog_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierten Eingaben.</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="catalog-contribution-edit-heading">
        <div class="bc-section-heading"><h2 id="catalog-contribution-edit-heading">Verantwortliche Person oder Körperschaft</h2></div>

        @if ($usedOnTitleCount > 1)
            <p class="bc-catalog-warning">
                Dieser Contributor wird an {{ $usedOnTitleCount }} Titeln verwendet. Änderungen an Anzeigename oder Sortiername wirken deshalb auf alle diese Titel.
            </p>
        @endif

        <form method="post" action="{{ route('pos.catalog.contributions.update', [
            'titleId' => $title->getKey(),
            'contributionId' => $contribution->getKey(),
        ]) }}" class="bc-catalog-form">
            @csrf
            @method('PATCH')
            <div class="bc-catalog-form__grid">
                <x-ui.input
                    label="Anzeigename"
                    name="display_name"
                    :value="old('display_name', $contribution->contributor->display_name)"
                    :error="$errors->first('display_name') ?: null"
                    required
                />
                <x-ui.input
                    label="Sortiername"
                    name="sort_name"
                    :value="old('sort_name', $contribution->contributor->sort_name)"
                    :error="$errors->first('sort_name') ?: null"
                />
                <x-ui.input
                    label="Rollen-Schlüssel"
                    name="role_key"
                    :value="old('role_key', $contribution->role_key)"
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
                    :value="old('position', $contribution->position)"
                    :error="$errors->first('position') ?: null"
                    required
                />
            </div>
            <div class="bc-action-row">
                <x-ui.button type="submit">Verantwortlichkeit speichern</x-ui.button>
                <x-ui.button href="{{ route('pos.catalog.titles.show', ['titleId' => $title->getKey()]) }}" variant="secondary">Abbrechen</x-ui.button>
            </div>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-contribution-remove-heading">
        <div class="bc-section-heading"><h2 id="catalog-contribution-remove-heading">Vom Titel entfernen</h2></div>
        <p class="bc-section-copy">Die Verknüpfung mit diesem Titel wird entfernt. Wird der Contributor danach nirgends mehr verwendet, wird der verwaiste Datensatz automatisch bereinigt.</p>
        <form method="post" action="{{ route('pos.catalog.contributions.destroy', [
            'titleId' => $title->getKey(),
            'contributionId' => $contribution->getKey(),
        ]) }}">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger">Verantwortlichkeit entfernen</x-ui.button>
        </form>
    </section>
</x-app-shell>
