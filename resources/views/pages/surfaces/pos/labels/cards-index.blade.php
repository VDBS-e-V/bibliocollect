<x-app-shell surface="pos" title="Ausweise drucken">
    <x-ui.page-header
        kicker="Ausleihkonten"
        title="Bibliotheksausweise drucken"
        lead="Scheckkartenformat (85,6 × 54 mm) mit Namen, Klasse und Strichcode der Bibliotheksnummer, 10 Ausweise je A4-Bogen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.patrons.index') }}">← Zurück zur Suche</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="get" action="{{ route('pos.labels.cards') }}" class="bc-audit-filter" role="search">
        <x-ui.select label="Klasse" name="klasse" data-auto-submit>
            <option value="">Alle Klassen</option>
            @foreach ($classes as $class)
                <option value="{{ $class->getKey() }}" @selected($classId === (string) $class->getKey())>{{ $class->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.input label="Name oder Bibliotheksnummer" name="q" :value="$term" />
        <x-ui.button type="submit" variant="secondary">Suchen</x-ui.button>
    </form>

    <form method="post" action="{{ route('pos.labels.cards.print') }}" target="_blank">
        @csrf
        <section class="bc-content-section" aria-labelledby="cards-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="cards-heading">Ausleihkonten</h2>
                <span>{{ $patrons->count() }}</span>
            </div>

            @if ($patrons->isEmpty())
                <p class="bc-section-copy">Keine aktiven Ausleihkonten gefunden.</p>
            @else
                <table class="bc-calendar-table">
                    <thead>
                        <tr>
                            <th scope="col"><label><input type="checkbox" data-select-all="patrons[]"> <span class="bc-visually-hidden">Alle auswählen</span></label></th>
                            <th scope="col">Name</th>
                            <th scope="col">Nummer</th>
                            <th scope="col">Klasse</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($patrons as $patron)
                            <tr>
                                <td><input type="checkbox" name="patrons[]" value="{{ $patron->getKey() }}" aria-label="{{ $patron->displayName() }} auswählen" @checked($classId !== '')></td>
                                <th scope="row">{{ $patron->last_name }}, {{ $patron->first_name }}</th>
                                <td class="bc-tabular">{{ $patron->library_number }}</td>
                                <td>{{ $patron->schoolClass?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <x-ui.button type="submit">Ausweise ansehen und drucken</x-ui.button>
            @endif
        </section>
    </form>
</x-app-shell>
