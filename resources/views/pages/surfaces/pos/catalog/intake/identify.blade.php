<x-app-shell surface="pos" title="Medium erfassen">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Medium erfassen"
        lead="Schritt für Schritt: erst die Inventarnummer, dann das Medium nachschlagen, Daten prüfen, Exemplar anlegen. Gespeichert wird erst im letzten Schritt."
    />

    <x-catalog.intake-steps :current="1" />

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierte Eingabe.</x-ui.alert>
    @endif

    <form method="post" action="{{ route('pos.catalog.intake.barcode') }}" class="bc-intake-form">
        @csrf

        <fieldset class="bc-intake-fieldset">
            <legend>Inventarnummer</legend>
            <x-ui.input
                label="Inventarnummer (Mediennummer)"
                name="barcode"
                :value="old('barcode', $draftBarcode)"
                hint="Genau 7 Ziffern, zum Beispiel 0012482. Sie steht auf dem Etikett im Buch. Ein Barcode-Scanner kann hier direkt eingelesen werden."
                :error="$errors->first('barcode')"
                required
                autofocus
                autocomplete="off"
                inputmode="numeric"
                maxlength="7"
                pattern="[0-9]{7}"
            />
        </fieldset>

        <div class="bc-intake-actions">
            <x-ui.button type="submit">Weiter zur Suche nach dem Medium</x-ui.button>
        </div>
    </form>
</x-app-shell>
