<x-app-shell surface="pos" title="Ausleihkonten importieren">
    <x-ui.page-header
        kicker="Ausleihkonten"
        title="Ausleihkonten importieren"
        lead="Schüler:innen, Lehrkräfte und Mitarbeiter:innen aus einer CSV-Datei anlegen. Vor dem Anlegen gibt es eine Vorschau."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.patrons.index') }}">← Zurück zur Suche</a>
        <a href="{{ route('pos.patrons.import.template') }}">Vorlage herunterladen</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Datei nicht nutzbar">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="import-upload-heading">
        <div class="bc-section-heading"><h2 id="import-upload-heading">Datei hochladen</h2></div>
        <form method="post" action="{{ route('pos.patrons.import.store') }}" enctype="multipart/form-data" class="bc-calendar-form">
            @csrf
            <x-ui.input label="CSV-Datei (höchstens 2 MB)" name="file" type="file" accept=".csv,.txt" />
            <x-ui.button type="submit">Vorschau erstellen</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="import-format-heading">
        <div class="bc-section-heading"><h2 id="import-format-heading">Dateiformat</h2></div>
        <p class="bc-section-copy">
            CSV mit Kopfzeile, Trennzeichen Semikolon, Komma oder Tabulator, Zeichensatz UTF-8 (Excel-Export „CSV UTF-8“) oder Windows-1252. Höchstens {{ \App\Modules\Patrons\Import\PatronCsvParser::MAX_ROWS }} Zeilen je Datei. Die Reihenfolge der Spalten ist egal.
        </p>
        <table class="bc-calendar-table">
            <thead><tr><th scope="col">Spalte</th><th scope="col">Pflicht</th><th scope="col">Inhalt</th></tr></thead>
            <tbody>
                <tr><th scope="row"><code>vorname</code></th><td>ja</td><td>Vorname</td></tr>
                <tr><th scope="row"><code>nachname</code></th><td>ja</td><td>Nachname</td></tr>
                <tr><th scope="row"><code>geburtsdatum</code></th><td>ja</td><td><code>TT.MM.JJJJ</code> oder <code>JJJJ-MM-TT</code>, nicht in der Zukunft</td></tr>
                <tr><th scope="row"><code>klasse</code></th><td>für Schüler:innen</td><td>Name einer aktiven Klasse im aktiven Schuljahr, z. B. <code>5a</code></td></tr>
                <tr><th scope="row"><code>art</code></th><td>nein</td><td><code>Schüler:in</code> (Standard), <code>Lehrkraft</code> oder <code>Mitarbeiter:in</code></td></tr>
                <tr><th scope="row"><code>email</code></th><td>nein</td><td>E-Mail-Adresse am Ausleihkonto</td></tr>
                <tr><th scope="row"><code>bibliotheksnummer</code></th><td>nein</td><td>Wird sonst vergeben: <code>S-…</code> für Schüler:innen, <code>L-…</code> für Lehrkräfte, <code>M-…</code> für Mitarbeiter:innen</td></tr>
            </tbody>
        </table>
        <p class="bc-section-copy">
            Gleiche Personen (Vorname, Nachname und Geburtsdatum) werden nie überschrieben, sondern übersprungen. Enthält die Datei Fehler, wird nichts angelegt. Bereits ausgeschiedene Personen lassen sich nicht über den Import wieder aktivieren.
        </p>
    </section>
</x-app-shell>
