<x-app-shell surface="pos" title="Medium suchen">
    <x-ui.page-header
        kicker="Medium erfassen"
        title="Medium suchen"
        :lead="'Inventarnummer '.$barcode.'. Jetzt die ISBN eingeben oder scannen; nach Titel und Autor:in suchen geht auch.'"
    />

    <x-catalog.intake-steps :current="2" />

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierten Eingaben.</x-ui.alert>
    @endif

    <form method="post" action="{{ route('pos.catalog.intake.lookup') }}" class="bc-intake-form">
        @csrf

        <fieldset class="bc-intake-fieldset">
            <legend>Medium nachschlagen</legend>
            <p class="bc-intake-note">
                Die Daten werden bei der Deutschen Nationalbibliothek (DNB) abgefragt und anschließend zur Prüfung vorgeschlagen.
                Fehlt dort eine Zusammenfassung, wird sie zur ISBN bei Google Books oder Open Library gesucht.
                Nichts wird ungeprüft übernommen.
            </p>

            <x-ui.input
                label="ISBN / EAN"
                name="isbn"
                :value="old('isbn', $draftQuery['isbn'])"
                hint="Bei mehreren ISBNs bitte die 13-stellige verwenden (beginnt mit 978 oder 979)."
                :error="$errors->first('isbn')"
                inputmode="numeric"
                autocomplete="off"
                autofocus
                maxlength="32"
            />

            <p class="bc-intake-or">oder ohne ISBN suchen</p>

            <div class="bc-intake-fieldset__grid">
                <x-ui.input
                    label="Titel"
                    name="title"
                    :value="old('title', $draftQuery['title'])"
                    :error="$errors->first('title')"
                    maxlength="200"
                />
                <x-ui.input
                    label="Autor:in"
                    name="person"
                    :value="old('person', $draftQuery['person'])"
                    :error="$errors->first('person')"
                    maxlength="200"
                />
            </div>
        </fieldset>

        <div class="bc-intake-actions">
            <a href="{{ route('pos.catalog.intake.identify') }}">← Inventarnummer ändern</a>
            <x-ui.button type="submit" name="action" value="lookup">Bei der DNB abfragen</x-ui.button>
            <x-ui.button type="submit" name="action" value="manual" variant="secondary">Ohne Abfrage manuell erfassen</x-ui.button>
        </div>
    </form>
</x-app-shell>
