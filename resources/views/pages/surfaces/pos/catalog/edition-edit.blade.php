<x-app-shell surface="pos" title="Ausgabe bearbeiten">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Ausgabe bearbeiten"
        :lead="$edition->title->preferred_title"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.titles.show', ['titleId' => $edition->title_id]) }}">← Zurück zum Titel</a>
    </div>

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('catalog_success') }}</x-ui.alert>
    @endif

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
        $copyStatusVariants = [
            'active' => 'success',
            'damaged' => 'danger',
            'lost' => 'danger',
            'withdrawn' => 'neutral',
        ];
        $hasExtendedMetadata = $edition->responsibility_statement
            || $edition->series_statement
            || $edition->publication_place
            || $edition->edition_number
            || (is_array($edition->alternate_identifiers) && $edition->alternate_identifiers !== [])
            || $edition->issn
            || $edition->doi_handle
            || $edition->local_classification
            || $edition->original_language_code
            || $edition->page_count
            || $edition->physical_extent
            || $edition->file_size_bytes
            || $edition->format_type
            || $edition->summary
            || $edition->subject_keywords
            || $edition->subject_keywords_system
            || $edition->target_audience
            || $edition->age_recommendation
            || $edition->metadata_source
            || $edition->source_record_id;
    @endphp

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

    @if ($hasExtendedMetadata)
        <section class="bc-content-section" aria-labelledby="catalog-edition-metadata-heading">
            <div class="bc-section-heading"><h2 id="catalog-edition-metadata-heading">Erweiterte bibliografische Metadaten</h2></div>
            <p class="bc-section-copy">
                Diese Angaben können aus DNB-/Normdaten oder dem Legacy-Import stammen. Die bestehende Kurzpflege oben überschreibt sie nicht; spätere DNB-/MARC21-Anreicherung kann sie gezielt aktualisieren.
            </p>
            <dl class="bc-detail-list">
                @if ($edition->responsibility_statement)<div><dt>Verantwortlichkeitsangabe</dt><dd>{{ $edition->responsibility_statement }}</dd></div>@endif
                @if ($edition->series_statement)<div><dt>Reihe</dt><dd>{{ $edition->series_statement }}</dd></div>@endif
                @if ($edition->edition_number)<div><dt>Auflagenangabe</dt><dd>{{ $edition->edition_number }}</dd></div>@endif
                @if ($edition->publication_place)<div><dt>Erscheinungsort</dt><dd>{{ $edition->publication_place }}</dd></div>@endif
                @if (is_array($edition->alternate_identifiers) && $edition->alternate_identifiers !== [])<div><dt>Weitere Identifikatoren</dt><dd>{{ implode(', ', $edition->alternate_identifiers) }}</dd></div>@endif
                @if ($edition->issn)<div><dt>ISSN</dt><dd>{{ $edition->issn }}</dd></div>@endif
                @if ($edition->doi_handle)<div><dt>DOI / Handle</dt><dd>{{ $edition->doi_handle }}</dd></div>@endif
                @if ($edition->local_classification)<div><dt>Lokale Klassifikation</dt><dd>{{ $edition->local_classification }}</dd></div>@endif
                @if ($edition->original_language_code)<div><dt>Originalsprache</dt><dd>{{ $edition->original_language_code }}</dd></div>@endif
                @if ($edition->page_count)<div><dt>Seiten</dt><dd>{{ $edition->page_count }}</dd></div>@endif
                @if ($edition->physical_extent)<div><dt>Umfang</dt><dd>{{ $edition->physical_extent }}</dd></div>@endif
                @if ($edition->file_size_bytes)<div><dt>Dateigröße</dt><dd>{{ number_format($edition->file_size_bytes, 0, ',', '.') }} Bytes</dd></div>@endif
                @if ($edition->format_type)<div><dt>Format</dt><dd>{{ $edition->format_type }}</dd></div>@endif
                @if ($edition->target_audience)<div><dt>Zielgruppe</dt><dd>{{ $edition->target_audience }}</dd></div>@endif
                @if ($edition->summary)<div><dt>Inhaltsangabe</dt><dd>{{ $edition->summary }}</dd></div>@endif
                @if ($edition->subject_keywords)<div><dt>Lokale Schlagwörter</dt><dd>{{ $edition->subject_keywords }}</dd></div>@endif
                @if ($edition->subject_keywords_system)<div><dt>System-Schlagwörter</dt><dd>{{ $edition->subject_keywords_system }}</dd></div>@endif
                @if ($edition->metadata_source || $edition->source_record_id)
                    <div>
                        <dt>Metadatenquelle</dt>
                        <dd>
                            {{ $edition->metadata_source ?: '—' }}
                            @if ($edition->source_record_id) · {{ $edition->source_record_id }} @endif
                            @if ($edition->source_permalink) · <a href="{{ $edition->source_permalink }}" rel="nofollow noopener">Quelldatensatz</a> @endif
                        </dd>
                    </div>
                @endif
            </dl>
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="catalog-copies-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="catalog-copies-heading">Exemplare</h2>
            <a href="{{ route('pos.labels.copies', ['ausgabe' => $edition->getKey()]) }}">Etiketten drucken</a>
            <span>{{ $edition->copies->count() }}</span>
        </div>

        <p class="bc-section-copy">
            Jedes physische Exemplar besitzt einen eindeutigen Barcode. Exemplare werden nicht gelöscht:
            Nicht mehr genutzte Bestände werden auf „Ausgesondert“ gesetzt, damit ihre Identität für spätere
            Ausleih- und Inventurhistorien erhalten bleibt.
        </p>

        @if ($edition->copies->isEmpty())
            <x-ui.alert title="Noch keine Exemplare">Lege unten das erste physische Exemplar dieser Ausgabe an.</x-ui.alert>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th scope="col">Barcode</th>
                        <th scope="col">Standort</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Aktion</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($edition->copies->sortBy('barcode') as $copy)
                        <tr>
                            <td><code>{{ $copy->barcode }}</code></td>
                            <td>{{ $copy->shelf_location ?: '—' }}</td>
                            <td>
                                <x-ui.badge :variant="$copyStatusVariants[$copy->status->value] ?? 'neutral'">
                                    {{ $copyStatusLabels[$copy->status->value] ?? $copy->status->value }}
                                </x-ui.badge>
                            </td>
                            <td>
                                <a href="{{ route('pos.catalog.copies.edit', [
                                    'editionId' => $edition->getKey(),
                                    'copyId' => $copy->getKey(),
                                ]) }}">Bearbeiten</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif

        <form method="post" action="{{ route('pos.catalog.copies.store', ['editionId' => $edition->getKey()]) }}" class="bc-catalog-form bc-catalog-copy-form">
            @csrf
            <div class="bc-catalog-form__grid">
                <x-ui.input
                    label="Barcode"
                    name="barcode"
                    :value="old('barcode')"
                    hint="Eindeutige sichtbare Exemplar-ID; der interne Primärschlüssel bleibt eine ULID."
                    :error="$errors->first('barcode') ?: null"
                    autocomplete="off"
                    required
                />
<p class="bc-intake-note">Den Standort bekommt das Exemplar beim Einsortieren ins Regal (Vorgang „Medien einsortieren“).</p>
                <x-ui.select label="Themenbereich / Signatur" name="signature_id" :error="$errors->first('signature_id') ?: null">
                    <option value="">Noch nicht festgelegt</option>
                    @foreach ($signatureOptions as $id => $display)
                        <option value="{{ $id }}" @selected((string) old('signature_id') === $id)>{{ $display }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select
                    label="Exemplarstatus"
                    name="status"
                    :error="$errors->first('status') ?: null"
                    required
                >
                    @foreach ($copyStatuses as $copyStatus)
                        <option value="{{ $copyStatus->value }}" @selected(old('status', 'active') === $copyStatus->value)>
                            {{ $copyStatusLabels[$copyStatus->value] ?? $copyStatus->value }}
                        </option>
                    @endforeach
                </x-ui.select>
            </div>
            <div class="bc-action-row"><x-ui.button type="submit">Exemplar anlegen</x-ui.button></div>
        </form>
    </section>
</x-app-shell>
