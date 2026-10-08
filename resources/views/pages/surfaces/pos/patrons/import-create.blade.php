<x-app-shell surface="pos" title="Klassendaten importieren">
    <x-ui.page-header
        kicker="Ausleihkonten"
        title="Klassendaten importieren"
        lead="Klassenleitungen füllen die Vorlage mit den Schülerdaten aus, ihr importiert sie auf einen Schlag. Vor dem Anlegen gibt es eine Vorschau."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.patrons.index') }}">← Zurück zur Suche</a>
        <a href="{{ route('pos.patrons.import.template') }}">Excel-Vorlage für die Klassenleitungen herunterladen</a>
        <a href="{{ route('pos.patrons.import.template-csv') }}">CSV-Vorlage</a>
        <a href="{{ route('pos.labels.cards.issue', ['klasse' => 'alle']) }}">Danach: Ausweise klassenweise ausgeben</a>
    </div>

    <x-ui.alert title="Ausweise gibt es erst später">Der Import legt nur die Ausleihkonten an. Die Ausweise werden erst ausgegeben, wenn die Schüler:innen vor euch stehen: unter „Ausweise klassenweise ausgeben“ die Klasse wählen und jeden Ausweis neben dem Namen scannen. Einzelne neue Konten legt ihr dagegen mit Ausweis an, weil die Person dann ohnehin da ist.</x-ui.alert>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Datei nicht nutzbar">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="import-upload-heading">
        <div class="bc-section-heading"><h2 id="import-upload-heading">Datei hochladen</h2></div>
        <form method="post" action="{{ route('pos.patrons.import.store') }}" enctype="multipart/form-data" class="bc-calendar-form">
            @csrf
            <x-ui.select label="Klasse" name="school_class_id" id="import-class" hint="Alle Personen der Datei werden dieser Klasse zugeordnet.">
                <option value="">Bitte wählen …</option>
                @foreach ($schoolClasses as $schoolClass)
                    <option value="{{ $schoolClass->getKey() }}" @selected(old('school_class_id') === (string) $schoolClass->getKey())>{{ $schoolClass->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input label="Excel- oder CSV-Datei (höchstens 2 MB)" name="file" type="file" accept=".xlsx,.csv,.txt" />
            <x-ui.button type="submit">Vorschau erstellen</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="import-format-heading">
        <div class="bc-section-heading"><h2 id="import-format-heading">Dateiformat</h2></div>
        <p class="bc-section-copy">
            Excel-Datei (.xlsx, am einfachsten mit der Vorlage oben) oder CSV mit Kopfzeile, Trennzeichen Semikolon, Komma oder Tabulator, Zeichensatz UTF-8 oder Windows-1252. Es zählt das erste Tabellenblatt. Höchstens {{ \App\Modules\Patrons\Import\PatronCsvParser::MAX_ROWS }} Zeilen je Datei. Die Reihenfolge der Spalten ist egal.
        </p>
        <table class="bc-calendar-table">
            <thead><tr><th scope="col">Spalte</th><th scope="col">Pflicht</th><th scope="col">Inhalt</th></tr></thead>
            <tbody>
                <tr><th scope="row"><code>vorname</code></th><td>ja</td><td>Vorname</td></tr>
                <tr><th scope="row"><code>nachname</code></th><td>ja</td><td>Nachname</td></tr>
                <tr><th scope="row"><code>geburtsdatum</code></th><td>ja</td><td><code>TT.MM.JJJJ</code> oder <code>JJJJ-MM-TT</code>, nicht in der Zukunft</td></tr>
                <tr><th scope="row"><code>email</code></th><td>nein</td><td>E-Mail-Adresse am Ausleihkonto</td></tr>
            </tbody>
        </table>
        <p class="bc-section-copy">
            Die Klasse wählst du oben beim Hochladen, sie steht nicht in der Datei. Die Bibliotheksnummern werden zufällig vergeben. Weitere Spalten in der Datei werden ignoriert. Lehrkräfte und Mitarbeiter:innen legst du einzeln unter „Ausleihkonto anlegen“ an.
        </p>
        <p class="bc-section-copy">
            Gleiche Personen (Vorname, Nachname und Geburtsdatum) werden nie überschrieben, sondern übersprungen. Enthält die Datei Fehler, wird nichts angelegt. Bereits ausgeschiedene Personen lassen sich nicht über den Import wieder aktivieren.
        </p>
    </section>
</x-app-shell>
