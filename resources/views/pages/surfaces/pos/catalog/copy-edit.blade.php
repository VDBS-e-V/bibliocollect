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
        $hasLegacyMetadata = $copy->legacy_source
            || $copy->legacy_media_id
            || $copy->access_status
            || $copy->purchase_date
            || $copy->purchase_price
            || $copy->cataloged_on
            || $copy->legacy_cover_path
            || $copy->legacy_loan_count !== null
            || $copy->legacy_last_loan_date
            || $copy->internal_notes
            || $copy->condition_code
            || $copy->legacy_condition
            || $copy->depreciation_reason
            || $copy->depreciated_at
            || $copy->further_use
            || $copy->signature;
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
                    label="Inventarnummer / Barcode"
                    name="barcode"
                    :value="old('barcode', $copy->barcode)"
                    hint="Die Inventarnummer muss katalogweit eindeutig sein. Neue Nummern bestehen aus genau 7 Ziffern."
                    :error="$errors->first('barcode') ?: null"
                    autocomplete="off"
                    required
                />
                <x-ui.select label="Standort (Regalbrett)" name="shelf_location" :error="$errors->first('shelf_location') ?: null">
                    <option value="">Kein Standort</option>
                    @foreach ($shelfOptions as $code => $display)
                        <option value="{{ $code }}" @selected(old('shelf_location', $copy->shelf_location) === $code)>{{ $display }}</option>
                    @endforeach
                </x-ui.select>
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

    @if ($hasLegacyMetadata)
        <section class="bc-content-section" aria-labelledby="catalog-copy-legacy-heading">
            <div class="bc-section-heading"><h2 id="catalog-copy-legacy-heading">Bestands- und Legacy-Metadaten</h2></div>
            <p class="bc-section-copy">
                Diese Angaben stammen aus dem historischen Bestand oder aus strukturierten Signaturen. Alte Verfügbarkeit und Ausleihzähler sind reine Historie und steuern die aktuelle Circulation nicht.
            </p>
            <dl class="bc-detail-list">
                @if ($copy->signature)
                    <div><dt>Strukturierte Signatur</dt><dd>{{ $copy->signature->signature }}</dd></div>
                    @if ($copy->signature->topics->isNotEmpty())
                        <div><dt>Themen</dt><dd>{{ $copy->signature->topics->pluck('name')->implode(', ') }}</dd></div>
                    @endif
                @endif
                @if ($copy->access_status)<div><dt>Zugangsstatus</dt><dd>{{ $copy->access_status }}</dd></div>@endif
                @if ($copy->condition_code)<div><dt>Zustand</dt><dd>{{ $copy->condition_code }}</dd></div>@endif
                @if ($copy->purchase_date)<div><dt>Erwerbungsdatum</dt><dd>{{ $copy->purchase_date->format('d.m.Y') }}</dd></div>@endif
                @if ($copy->purchase_price !== null)<div><dt>Erwerbungspreis</dt><dd>{{ $copy->purchase_price }} €</dd></div>@endif
                @if ($copy->cataloged_on)<div><dt>Aufgenommen am</dt><dd>{{ $copy->cataloged_on->format('d.m.Y') }}</dd></div>@endif
                @if ($copy->legacy_school_id)<div><dt>Legacy-Schul-ID</dt><dd>{{ $copy->legacy_school_id }}</dd></div>@endif
                @if ($copy->legacy_source || $copy->legacy_media_id)
                    <div><dt>Legacy-Referenz</dt><dd>{{ $copy->legacy_source ?: '—' }} / {{ $copy->legacy_media_id ?: '—' }}</dd></div>
                @endif
                @if ($copy->legacy_cover_path)<div><dt>Alter Coverpfad</dt><dd>{{ $copy->legacy_cover_path }}</dd></div>@endif
                @if ($copy->legacy_loan_count !== null)<div><dt>Historische Ausleihen</dt><dd>{{ $copy->legacy_loan_count }}</dd></div>@endif
                @if ($copy->legacy_last_loan_date)<div><dt>Historisch zuletzt ausgeliehen</dt><dd>{{ $copy->legacy_last_loan_date->format('d.m.Y') }}</dd></div>@endif
                @if ($copy->legacy_is_available !== null)<div><dt>Legacy-Verfügbarkeit</dt><dd>{{ $copy->legacy_is_available ? 'ja' : 'nein' }} (nur historisch)</dd></div>@endif
                @if ($copy->legacy_in_transition !== null)<div><dt>Legacy-Übertragungsstatus</dt><dd>{{ $copy->legacy_in_transition ? 'in Übertragung' : 'nein' }}</dd></div>@endif
                @if ($copy->depreciation_reason)<div><dt>Aussonderungsgrund</dt><dd>{{ $copy->depreciation_reason }}</dd></div>@endif
                @if ($copy->depreciated_at)<div><dt>Ausgesondert am</dt><dd>{{ $copy->depreciated_at->format('d.m.Y') }}</dd></div>@endif
                @if ($copy->further_use)<div><dt>Weitere Verwendung</dt><dd>{{ $copy->further_use }}</dd></div>@endif
                @if ($copy->internal_notes)<div><dt>Interne Notizen</dt><dd>{{ $copy->internal_notes }}</dd></div>@endif
            </dl>
        </section>
    @endif
</x-app-shell>
