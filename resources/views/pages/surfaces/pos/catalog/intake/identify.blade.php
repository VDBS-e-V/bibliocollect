<x-app-shell surface="pos" title="Medium erfassen">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Medium erfassen"
        lead="Schritt für Schritt: Medium identifizieren, Daten prüfen, Exemplar anlegen. Gespeichert wird erst im letzten Schritt."
    />

    <x-catalog.intake-steps :current="1" />

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierten Eingaben.</x-ui.alert>
    @endif

    <form method="post" action="{{ route('pos.catalog.intake.lookup') }}" class="bc-intake-form">
        @csrf

        <fieldset class="bc-intake-fieldset">
            <legend>Exemplar</legend>
            <x-ui.input
                label="Barcode / Inventarnummer"
                name="barcode"
                :value="old('barcode', $draftBarcode)"
                hint="Eindeutige Bestandsnummer des Exemplars. Ein Barcode-Scanner kann hier direkt eingelesen werden."
                :error="$errors->first('barcode')"
                required
                autofocus
                autocomplete="off"
                maxlength="80"
            />
        </fieldset>

        <fieldset class="bc-intake-fieldset">
            <legend>Medium nachschlagen</legend>
            <p class="bc-intake-note">
                Die Daten werden bei der Deutschen Nationalbibliothek (DNB) abgefragt und anschließend zur Prüfung vorgeschlagen.
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
            <x-ui.button type="submit" name="action" value="lookup">Bei der DNB abfragen</x-ui.button>
            <x-ui.button type="submit" name="action" value="manual" variant="secondary">Ohne Abfrage manuell erfassen</x-ui.button>
        </div>
    </form>
</x-app-shell>
