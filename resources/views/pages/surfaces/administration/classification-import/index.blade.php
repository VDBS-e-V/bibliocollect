<x-app-shell surface="administration" title="Themen und Regalbretter importieren">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Themen & Regalbretter importieren"
        lead="Themenliste und Regalsignaturen aus dem Altsystem (JSON-Export) prüfen und übernehmen. Zuerst gibt es eine Vorschau; geschrieben wird erst nach deiner Bestätigung."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.shelves.index') }}">← Zu den Regalbrettern</a>
        <a href="{{ route('administration.topics.index') }}">Themenbereiche</a>
    </div>

    @if (session('import_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('import_success') }}</x-ui.alert>
    @endif

    @if (session('import_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('import_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if (session('import_result'))
        @php($result = session('import_result'))
        <section class="bc-content-section" aria-labelledby="result-heading">
            <div class="bc-section-heading"><h2 id="result-heading">Ergebnis des Imports</h2></div>
            <table class="bc-calendar-table">
                <thead><tr><th scope="col">Kennzahl</th><th scope="col">Anzahl</th></tr></thead>
                <tbody>
                    <tr><th scope="row">Neue Themen</th><td>{{ $result['new_topics'] }}</td></tr>
                    <tr><th scope="row">Geänderte Themen (bewusst übernommen)</th><td>{{ $result['updated_topics'] }}</td></tr>
                    <tr><th scope="row">Themenkonflikte (nicht übernommen)</th><td>{{ $result['skipped_conflicts'] }}</td></tr>
                    <tr><th scope="row">Neue Signaturen</th><td>{{ $result['new_signatures'] }}</td></tr>
                    <tr><th scope="row">Bestehende Signaturen</th><td>{{ $result['existing_signatures'] }}</td></tr>
                    <tr><th scope="row">Neue Regalbretter</th><td>{{ $result['new_shelves'] }}</td></tr>
                    <tr><th scope="row">Bestehende Regalbretter</th><td>{{ $result['existing_shelves'] }}</td></tr>
                    <tr><th scope="row">Neue Themenzuordnungen (Regalbretter)</th><td>{{ $result['new_assignments'] }}</td></tr>
                    <tr><th scope="row">Neue Themenzuordnungen (Signaturen)</th><td>{{ $result['new_signature_assignments'] }}</td></tr>
                    <tr><th scope="row">Warnungen</th><td>{{ $result['warnings'] }}</td></tr>
                </tbody>
            </table>
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="upload-heading">
        <div class="bc-section-heading"><h2 id="upload-heading">Dateien prüfen</h2></div>
        <p class="bc-section-copy">
            Wähle die Themenliste (<code>mediaTopicList.json</code>), die Regalsignaturen (<code>mediaSignatures.json</code>) oder beide.
            Erlaubt ist der phpMyAdmin-Export als JSON oder eine einfache Liste von Zeilen. Dateien bis 2 MB.
            Die Regalsignaturen brauchen Themen, die entweder in der Themenliste stehen oder schon im Bestand sind.
        </p>
        <p class="bc-section-copy"><strong>Vorher:</strong> Erstelle eine Datensicherung unter <a href="{{ route('administration.system.index') }}">Systemzustand</a> („Jetzt sichern“).</p>

        <form method="post" action="{{ route('administration.classification-import.preview') }}" enctype="multipart/form-data" class="bc-calendar-form">
            @csrf
            <div class="bc-field">
                <label class="bc-field__label" for="topics">Themenliste (mediaTopicList.json)</label>
                <input id="topics" name="topics" type="file" accept=".json,application/json,text/plain" class="bc-field__control">
            </div>
            <div class="bc-field">
                <label class="bc-field__label" for="signatures">Regalsignaturen (mediaSignatures.json)</label>
                <input id="signatures" name="signatures" type="file" accept=".json,application/json,text/plain" class="bc-field__control">
            </div>
            <x-ui.button type="submit">Dateien prüfen (Vorschau)</x-ui.button>
        </form>
    </section>
</x-app-shell>
