<x-app-shell surface="pos" title="Ausweis registrieren">
    <x-ui.page-header
        kicker="Ausleihe"
        title="Ausweis registrieren"
        lead="Der gescannte Ausweis ist neu und gehört noch niemandem. Suche die Person und ordne ihr den Ausweis zu."
    />

    @if (session('terminal_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('terminal_error') }}</x-ui.alert>
    @endif

    <div class="bc-context-actions">
        <form method="post" action="{{ route('pos.terminal.card.cancel') }}">
            @csrf
            <button type="submit" class="bc-intake-linkbutton">← Abbrechen, zurück zum Start</button>
        </form>
    </div>

    <section class="bc-work-panel" aria-labelledby="register-card-heading">
        <div class="bc-section-heading"><h2 id="register-card-heading">Neuer Ausweis</h2></div>
        <p class="bc-card-number">{{ $card->number }}</p>
    </section>

    <section class="bc-work-panel bc-terminal__start" aria-labelledby="register-search-heading">
        <div class="bc-section-heading"><h2 id="register-search-heading">Person suchen</h2></div>
        <form method="get" action="{{ route('pos.terminal.card.register') }}" class="bc-pos-scan" role="search">
            <x-ui.input
                label="Name oder Bibliotheksnummer"
                name="q"
                :value="$term"
                hint="Nachname, Vorname oder Bibliotheksnummer. Die Bibliotheksnummer kannst du auch scannen."
                autocomplete="off"
                autofocus
            />
            <x-ui.button type="submit">Suchen</x-ui.button>
        </form>

        @if ($term !== '')
            @if ($results->isEmpty())
                <p class="bc-section-copy">Niemand gefunden für „{{ $term }}“.</p>
            @else
                <ul class="bc-terminal__results" aria-label="Gefundene Personen">
                    @foreach ($results as $result)
                        <li>
                            <form method="post" action="{{ route('pos.terminal.card.claim') }}">
                                @csrf
                                <input type="hidden" name="patron_id" value="{{ $result->getKey() }}">
                                <button type="submit" class="bc-terminal__result">
                                    <strong>{{ $result->last_name }}, {{ $result->first_name }}</strong>
                                    <span>
                                        {{ $result->library_number }}@if ($result->schoolClass) · {{ $result->schoolClass->name }}@endif
                                        ·
                                        @if (isset($current[(string) $result->getKey()]))
                                            hat schon den Ausweis {{ $current[(string) $result->getKey()] }}: dieser wird gesperrt
                                        @else
                                            noch kein Ausweis
                                        @endif
                                    </span>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </section>
</x-app-shell>
