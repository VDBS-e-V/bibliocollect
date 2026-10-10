<x-app-shell surface="pos" title="Stapelbearbeitung – Vorschau">
    <x-ui.page-header kicker="Katalogpflege" title="Exemplarstandorte gemeinsam ändern"
        lead="Diese Vorschau ist 15 Minuten gültig. Erst nach der Bestätigung werden Standorte geändert." />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.index') }}">← Zurück zur Katalogsuche</a>
    </div>

    <x-ui.alert title="Nur der Standort wird geändert">
        Barcodes, Ausleihen, Vormerkungen, Status und bibliografische Angaben bleiben unverändert.
        Wurde zwischenzeitlich eines der ausgewählten Exemplare verändert, bricht die gesamte Änderung ab.
    </x-ui.alert>

    <section class="bc-content-section">
        <h2>Zielstandort: {{ $shelf->display() }}</h2>
        <p>{{ $copies->count() }} Exemplare ausgewählt.</p>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th scope="col">Exemplar</th><th scope="col">Titel</th><th scope="col">Bisheriger Standort</th><th scope="col">Neuer Standort</th></tr></thead>
                <tbody>
                    @foreach ($copies as $copy)
                        <tr>
                            <th scope="row">{{ $copy->barcode }}</th>
                            <td>{{ $copy->edition?->title?->preferred_title ?? 'Titel unbekannt' }}</td>
                            <td>{{ $copy->shelf_location ?: 'Nicht zugeordnet' }}</td>
                            <td>{{ $shelf->code }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <form method="post" action="{{ route('pos.catalog.bulk-location.commit') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="bc-checkbox-line">
            <input type="checkbox" name="confirm" value="1" required>
            Ich habe alle Exemplare und den Zielstandort geprüft und bestätige die Änderung.
        </label>
        <div class="bc-action-row">
            <x-ui.button type="submit">Standorte verbindlich ändern</x-ui.button>
            <a href="{{ route('pos.catalog.index') }}">Abbrechen</a>
        </div>
    </form>
</x-app-shell>
