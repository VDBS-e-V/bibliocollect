<x-app-shell surface="pos" title="Notbetrieb">
    <x-ui.page-header
        kicker="Ausleihe"
        title="Notbetrieb"
        lead="Wenn das System ausfällt, geht die Ausleihe auf Papier weiter. Hier druckst du vorher die Liste aller offenen Ausleihen und Vormerkungen und trägst danach nach, was auf Papier festgehalten wurde."
    />

    @if (session('emergency_success'))
        <x-ui.alert variant="success" title="Nachgetragen">{{ session('emergency_success') }}</x-ui.alert>
    @endif

    <section class="bc-work-panel" aria-labelledby="list-heading">
        <div class="bc-section-heading"><h2 id="list-heading">1. Notfallliste</h2></div>
        <p class="bc-section-copy">Aktuell {{ $loanCount }} offene Ausleihe(n) und {{ $reservationCount }} offene Vormerkung(en), sortiert nach Name. Drucke die Liste regelmäßig, zum Beispiel einmal pro Woche, und bewahre sie am Ausleihplatz auf. Die Liste enthält Namen von Personen und gehört nicht in fremde Hände.</p>
        @can('circulation.reports')
            <div class="bc-context-actions">
                <x-ui.button href="{{ route('pos.emergency.list') }}">Liste ansehen und drucken</x-ui.button>
                <a href="{{ route('pos.emergency.export') }}">Als CSV herunterladen</a>
            </div>
        @else
            <p class="bc-section-copy">Die Liste dürfen Mitarbeiter:innen und die Verwaltung abrufen.</p>
        @endcan
    </section>

    <section class="bc-work-panel" aria-labelledby="loans-heading">
        <div class="bc-section-heading"><h2 id="loans-heading">2. Ausleihen von Papier nachtragen</h2></div>
        <p class="bc-section-copy">Alle Ausleihen einer Person an einem Tag. Es gelten die üblichen Regeln (Sperren, Höchstzahl). Fällig wird nach der normalen Leihfrist ab dem gewählten Tag. Liegt die Fälligkeit dadurch schon in der Vergangenheit, gilt das Buch gleich als überfällig. Bei Fehlern wird nichts gebucht.</p>

        @if ($errors->loans->any())
            <x-ui.alert variant="error" title="Nicht nachgetragen">
                <ul>
                    @foreach ($errors->loans->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <form method="post" action="{{ route('pos.emergency.loans') }}" class="bc-catalog-form">
            @csrf
            <x-ui.input label="Ausweisnummer oder Bibliotheksnummer" name="person" id="paper-person" :value="old('person')" autocomplete="off" required />
            <x-ui.input label="Datum der Ausleihe" name="date" id="paper-loan-date" type="date" :value="old('date', $today)" :max="$today" :min="$earliest" required />
            <div class="bc-field">
                <label class="bc-field__label" for="paper-loan-codes">Inventarnummern (eine pro Zeile)</label>
                <textarea id="paper-loan-codes" class="bc-field__control" name="barcodes" rows="6" required>{{ old('barcodes') }}</textarea>
            </div>
            <x-ui.button type="submit">Ausleihen nachtragen</x-ui.button>
        </form>
    </section>

    <section class="bc-work-panel" aria-labelledby="returns-heading">
        <div class="bc-section-heading"><h2 id="returns-heading">3. Rückgaben von Papier nachtragen</h2></div>
        <p class="bc-section-copy">Bücher, die während des Ausfalls zurückgebracht wurden. Trage zuerst die Ausleihen ein, dann die Rückgaben. Das Rückgabedatum darf nicht vor dem Ausleihdatum liegen.</p>

        @if ($errors->returns->any())
            <x-ui.alert variant="error" title="Nicht nachgetragen">
                <ul>
                    @foreach ($errors->returns->all() as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        <form method="post" action="{{ route('pos.emergency.returns') }}" class="bc-catalog-form">
            @csrf
            <x-ui.input label="Datum der Rückgabe" name="date" id="paper-return-date" type="date" :value="old('date', $today)" :max="$today" :min="$earliest" required />
            <div class="bc-field">
                <label class="bc-field__label" for="paper-return-codes">Inventarnummern (eine pro Zeile)</label>
                <textarea id="paper-return-codes" class="bc-field__control" name="barcodes" rows="6" required>{{ old('barcodes') }}</textarea>
            </div>
            <x-ui.button type="submit">Rückgaben nachtragen</x-ui.button>
        </form>
    </section>
</x-app-shell>
