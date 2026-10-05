<x-app-shell surface="pos" title="Katalog importieren">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Katalog importieren"
        lead="CSV einlesen, Felder zuordnen und die geplanten Änderungen prüfen, bevor Bibliotheksdaten geschrieben werden."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.index') }}">← Zurück zur Katalogpflege</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Import nicht gestartet">Bitte prüfe die ausgewählte CSV-Datei.</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="catalog-import-upload-heading">
        <div class="bc-section-heading"><h2 id="catalog-import-upload-heading">1. CSV-Datei einlesen</h2></div>
        <p class="bc-section-copy">
            Die Datei wird nur eingelesen. In diesem Schritt werden noch keine Titel, Ausgaben, Verantwortlichen oder Exemplare angelegt oder verändert.
            Die Importzeilen werden für Mapping und Vorschau persistent gespeichert.
        </p>

        <form method="post" action="{{ route('pos.catalog.import.store') }}" enctype="multipart/form-data" class="bc-catalog-form">
            @csrf
            <x-ui.input
                label="CSV-Datei"
                name="catalog_file"
                type="file"
                accept=".csv,text/csv,text/plain"
                hint="Maximal 10 MB. Die erste Zeile muss eindeutige Spaltenüberschriften enthalten. Komma, Semikolon und Tabulator werden erkannt."
                :error="$errors->first('catalog_file') ?: null"
                required
            />
            <div class="bc-action-row">
                <x-ui.button type="submit">CSV einlesen</x-ui.button>
            </div>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-import-history-heading">
        <div class="bc-section-heading"><h2 id="catalog-import-history-heading">Gespeicherte Import-Batches</h2></div>
        <p class="bc-section-copy">Preview, Konflikte und Importbericht bleiben persistent und können hier erneut geöffnet werden.</p>

        @if ($batches->isEmpty())
            <x-ui.alert title="Noch keine Importe">Es wurde noch kein Import-Batch gespeichert.</x-ui.alert>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th scope="col">Datei</th>
                        <th scope="col">Quelle</th>
                        <th scope="col">Zeilen</th>
                        <th scope="col">Status</th>
                        <th scope="col">Erstellt</th>
                        <th scope="col"><span class="sr-only">Aktion</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($batches as $savedBatch)
                        <tr>
                            <td>{{ $savedBatch->original_filename ?: 'Import-Batch' }}</td>
                            <td>{{ strtoupper($savedBatch->source_format) }}</td>
                            <td>{{ $savedBatch->rows_count }}</td>
                            <td>{{ $presenter->batchStatus($savedBatch->status) }}</td>
                            <td>{{ $savedBatch->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') ?: '—' }}</td>
                            <td><a href="{{ route('pos.catalog.import.show', ['batchId' => $savedBatch->getKey()]) }}">Öffnen</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="catalog-import-flow-heading">
        <div class="bc-section-heading"><h2 id="catalog-import-flow-heading">Ablauf</h2></div>
        <p class="bc-section-copy">
            Nach dem Upload ordnest du die CSV-Spalten den Katalogfeldern zu. Erst die Vorschau bewertet Normalisierung, Wiederverwendung, Warnungen und Konflikte.
            Eine Übernahme ist nur bei konfliktfreier Vorschau möglich und verlangt anschließend eine zusätzliche ausdrückliche Bestätigung.
        </p>
    </section>
</x-app-shell>
