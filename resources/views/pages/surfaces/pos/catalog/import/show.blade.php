<x-app-shell surface="pos" title="Katalogimport">
    <x-ui.page-header
        kicker="Katalogimport"
        :title="$batch->original_filename ?: 'Import-Batch'"
        lead="Mapping, persistente Vorschau und kontrollierte Übernahme."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.import.create') }}">← Neuer Import</a>
        <a href="{{ route('pos.catalog.index') }}">Katalogpflege</a>
    </div>

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Import">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Nicht übernommen">Bitte prüfe die Hinweise und Konflikte des Imports.</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="catalog-import-state-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="catalog-import-state-heading">Importstatus</h2>
            <span>{{ $presenter->batchStatus($batch->status) }}</span>
        </div>
        <p class="bc-section-copy">
            Quelle: {{ strtoupper($batch->source_format) }} · {{ $batch->rows->count() }} gespeicherte Datenzeilen
            @if ($batch->committed_at)
                · Übernommen {{ $batch->committed_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
            @endif
        </p>
    </section>

    @if ($batch->status->value !== 'committed')
        <section class="bc-content-section" aria-labelledby="catalog-import-mapping-heading">
            <div class="bc-section-heading"><h2 id="catalog-import-mapping-heading">2. CSV-Spalten zuordnen</h2></div>
            <p class="bc-section-copy">
                Haupttitel und Barcode sind Pflichtzuordnungen. Nicht benötigte optionale Felder können leer bleiben. Eine neue Vorschau schreibt weiterhin keine Katalogdatensätze.
            </p>

            <form method="post" action="{{ route('pos.catalog.import.preview', ['batchId' => $batch->getKey()]) }}" class="bc-catalog-form">
                @csrf
                <div class="bc-catalog-form__grid">
                    @foreach ($fields as $field)
                        @php($selected = old('mapping.'.$field->value, $batch->mapping[$field->value] ?? ''))
                        <x-ui.select
                            :label="$presenter->fieldLabel($field)"
                            :name="'mapping['.$field->value.']'"
                            :error="$errors->first('mapping.'.$field->value) ?: null"
                            :required="in_array($field->value, ['preferred_title', 'barcode'], true)"
                        >
                            <option value="">— nicht zuordnen —</option>
                            @foreach ($batch->headers as $header)
                                <option value="{{ $header }}" @selected($selected === $header)>{{ $header }}</option>
                            @endforeach
                        </x-ui.select>
                    @endforeach
                </div>
                <div class="bc-action-row">
                    <x-ui.button type="submit">Vorschau neu berechnen</x-ui.button>
                </div>
            </form>
        </section>
    @endif

    @if (is_array($batch->summary))
        <section class="bc-content-section" aria-labelledby="catalog-import-summary-heading">
            <div class="bc-section-heading"><h2 id="catalog-import-summary-heading">{{ $batch->status->value === 'committed' ? 'Importbericht' : 'Vorschau-Zählwerte' }}</h2></div>
            <x-ui.table>
                <thead>
                    <tr>
                        <th scope="col">Zeilen</th>
                        <th scope="col">Neue Titel</th>
                        <th scope="col">Titel wiederverwendet</th>
                        <th scope="col">Neue Ausgaben</th>
                        <th scope="col">Ausgaben wiederverwendet</th>
                        <th scope="col">Neue Contributors</th>
                        <th scope="col">Neue Verantwortlichkeiten</th>
                        <th scope="col">Neue Exemplare</th>
                        <th scope="col">Übernommene Zeilen</th>
                        <th scope="col">Warnungen</th>
                        <th scope="col">Konflikte</th>
                        <th scope="col">Konfliktzeilen</th>
                        <th scope="col">Ungültig</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $batch->summary['rows_total'] ?? 0 }}</td>
                        <td>{{ $batch->summary['new_titles'] ?? 0 }}</td>
                        <td>{{ $batch->summary['reused_titles'] ?? 0 }}</td>
                        <td>{{ $batch->summary['new_editions'] ?? 0 }}</td>
                        <td>{{ $batch->summary['reused_editions'] ?? 0 }}</td>
                        <td>{{ $batch->summary['new_contributors'] ?? 0 }}</td>
                        <td>{{ $batch->summary['new_contributions'] ?? 0 }}</td>
                        <td>{{ $batch->summary['new_copies'] ?? 0 }}</td>
                        <td>{{ $batch->summary['committed_rows'] ?? '—' }}</td>
                        <td>{{ $batch->summary['warnings'] ?? 0 }}</td>
                        <td>{{ $batch->summary['conflicts'] ?? 0 }}</td>
                        <td>{{ $batch->summary['conflict_rows'] ?? 0 }}</td>
                        <td>{{ $batch->summary['invalid_rows'] ?? 0 }}</td>
                    </tr>
                </tbody>
            </x-ui.table>
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="catalog-import-preview-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="catalog-import-preview-heading">3. Persistente Vorschau</h2>
            <span>{{ $batch->rows->count() }} Zeilen</span>
        </div>
        <p class="bc-section-copy">Die Tabelle ist horizontal scrollbar. Normalisierte Werte und der geplante Schreibvorgang bleiben auch nach einem Seitenneuladen erhalten.</p>

        <x-ui.table>
            <thead>
                <tr>
                    <th scope="col">CSV-Zeile</th>
                    <th scope="col">Status</th>
                    <th scope="col">Haupttitel</th>
                    <th scope="col">ISBN</th>
                    <th scope="col">Ausgabe</th>
                    <th scope="col">Contributor</th>
                    <th scope="col">Barcode</th>
                    <th scope="col">Medientyp / Sprache</th>
                    <th scope="col">Plan</th>
                    <th scope="col">Hinweise</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($batch->rows as $row)
                    @php($data = is_array($row->normalized_data) ? $row->normalized_data : [])
                    <tr>
                        <td>{{ $row->row_number }}</td>
                        <td><strong>{{ $presenter->rowStatus($row->status) }}</strong></td>
                        <td>
                            {{ $data['preferred_title'] ?? '—' }}
                            @if (! empty($data['subtitle']))
                                <div class="bc-catalog-muted">{{ $data['subtitle'] }}</div>
                            @endif
                        </td>
                        <td>{{ $data['isbn'] ?? '—' }}</td>
                        <td>
                            {{ $data['edition_statement'] ?? '—' }}
                            @if (! empty($data['publisher_name']) || ! empty($data['publication_year']))
                                <div class="bc-catalog-muted">{{ $data['publisher_name'] ?? '—' }} · {{ $data['publication_year'] ?? '—' }}</div>
                            @endif
                            @if (($data['minimum_age'] ?? null) !== null || ! empty($data['age_rating_label']))
                                <div class="bc-catalog-muted">Alter: {{ $data['minimum_age'] ?? '—' }} · {{ $data['age_rating_label'] ?? '—' }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $data['contributor_name'] ?? '—' }}
                            @if (! empty($data['contributor_role']))
                                <div class="bc-catalog-muted">{{ $data['contributor_role'] }}</div>
                            @endif
                        </td>
                        <td>
                            {{ $data['barcode'] ?? '—' }}
                            @if (! empty($data['shelf_location']))
                                <div class="bc-catalog-muted">{{ $data['shelf_location'] }}</div>
                            @endif
                        </td>
                        <td>{{ $data['media_type'] ?? '—' }} / {{ $data['language_code'] ?? '—' }}</td>
                        <td>{{ $presenter->planSummary($row) }}</td>
                        <td>
                            @foreach ($row->warnings as $warning)
                                <div>{{ $warning }}</div>
                            @endforeach
                            @foreach ($row->conflicts as $conflict)
                                <div><strong>{{ $row->status->value === 'invalid' ? 'Ungültig' : 'Konflikt' }}:</strong> {{ $conflict }}</div>
                            @endforeach
                            @if ($row->warnings === [] && $row->conflicts === [])
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </x-ui.table>
    </section>

    @if ($batch->status->value === 'ready')
        <section class="bc-content-section" aria-labelledby="catalog-import-commit-heading">
            <div class="bc-section-heading"><h2 id="catalog-import-commit-heading">4. Übernahme ausdrücklich bestätigen</h2></div>
            <x-ui.alert title="Schreibvorgang">
                Erst diese Bestätigung legt die in der Vorschau angekündigten Katalogdatensätze an. Die Übernahme läuft vollständig in einer Datenbanktransaktion und prüft Konflikte unmittelbar davor erneut.
            </x-ui.alert>
            <form method="post" action="{{ route('pos.catalog.import.commit', ['batchId' => $batch->getKey()]) }}" class="bc-catalog-form">
                @csrf
                <div class="bc-field">
                    <label>
                        <input type="checkbox" name="confirm_import" value="1" @checked(old('confirm_import')) required>
                        Ich habe Mapping, Warnungen und Vorschau geprüft und möchte diesen Import jetzt übernehmen.
                    </label>
                    @if ($errors->first('confirm_import'))
                        <p class="bc-field__error"><strong>Fehler:</strong> {{ $errors->first('confirm_import') }}</p>
                    @endif
                </div>
                <div class="bc-action-row">
                    <x-ui.button type="submit">Import verbindlich übernehmen</x-ui.button>
                </div>
            </form>
        </section>
    @elseif ($batch->status->value === 'blocked')
        <x-ui.alert variant="error" title="Übernahme blockiert">
            Mindestens eine Zeile ist ungültig oder enthält einen kritischen Konflikt. Korrigiere die Quelldatei beziehungsweise das Mapping und starte danach einen neuen Import oder berechne das Mapping neu. Es wurden keine Katalogdaten übernommen.
        </x-ui.alert>
    @elseif ($batch->status->value === 'uploaded')
        <x-ui.alert title="Vorschau fehlt">Prüfe das vorgeschlagene Mapping und berechne anschließend die Vorschau.</x-ui.alert>
    @elseif ($batch->status->value === 'committed')
        <x-ui.alert variant="success" title="Import abgeschlossen">Der Batch ist abgeschlossen und kann nicht ein zweites Mal übernommen werden.</x-ui.alert>
    @endif
</x-app-shell>
