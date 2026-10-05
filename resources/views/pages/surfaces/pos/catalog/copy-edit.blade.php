<x-app-shell surface="pos" title="Exemplar bearbeiten">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Exemplar bearbeiten"
        :lead="$edition->title->preferred_title"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.editions.edit', ['editionId' => $edition->getKey()]) }}">← Zurück zur Ausgabe</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierten Eingaben.</x-ui.alert>
    @endif

    @php
        $copyStatusLabels = [
            'active' => 'Aktiv',
            'damaged' => 'Beschädigt',
            'lost' => 'Verloren',
            'withdrawn' => 'Ausgesondert',
        ];
    @endphp

    <section class="bc-content-section" aria-labelledby="catalog-copy-context-heading">
        <div class="bc-section-heading"><h2 id="catalog-copy-context-heading">Ausgabe</h2></div>
        <div class="bc-catalog-copy-context">
            <strong>{{ $edition->edition_statement ?: 'Ausgabe ohne Auflagenangabe' }}</strong>
            <span>
                {{ $edition->publisher_name ?: 'Verlag unbekannt' }}
                @if ($edition->publication_year) · {{ $edition->publication_year }} @endif
                @if ($edition->isbn) · ISBN {{ $edition->isbn }} @endif
            </span>
        </div>
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-copy-edit-heading">
        <div class="bc-section-heading"><h2 id="catalog-copy-edit-heading">Exemplardaten</h2></div>

        <p class="bc-section-copy">
            Die Ausgabezuordnung wird hier bewusst nicht geändert. Ein Exemplar behält seine Identität;
            statt einer Löschung steht für dauerhaft entfernten Bestand der Status „Ausgesondert“ bereit.
        </p>

        <form method="post" action="{{ route('pos.catalog.copies.update', [
            'editionId' => $edition->getKey(),
            'copyId' => $copy->getKey(),
        ]) }}" class="bc-catalog-form">
            @csrf
            @method('PATCH')
            <div class="bc-catalog-form__grid">
                <x-ui.input
                    label="Barcode"
                    name="barcode"
                    :value="old('barcode', $copy->barcode)"
                    hint="Der Barcode muss katalogweit eindeutig sein."
                    :error="$errors->first('barcode') ?: null"
                    autocomplete="off"
                    required
                />
                <x-ui.input
                    label="Regalstandort"
                    name="shelf_location"
                    :value="old('shelf_location', $copy->shelf_location)"
                    placeholder="z. B. J 5 ENDE"
                    :error="$errors->first('shelf_location') ?: null"
                />
                <x-ui.select
                    label="Exemplarstatus"
                    name="status"
                    :error="$errors->first('status') ?: null"
                    required
                >
                    @foreach ($copyStatuses as $copyStatus)
                        <option value="{{ $copyStatus->value }}" @selected(old('status', $copy->status->value) === $copyStatus->value)>
                            {{ $copyStatusLabels[$copyStatus->value] ?? $copyStatus->value }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="bc-action-row">
                <x-ui.button type="submit">Exemplar speichern</x-ui.button>
                <x-ui.button href="{{ route('pos.catalog.editions.edit', ['editionId' => $edition->getKey()]) }}" variant="secondary">Abbrechen</x-ui.button>
            </div>
        </form>
    </section>
</x-app-shell>
