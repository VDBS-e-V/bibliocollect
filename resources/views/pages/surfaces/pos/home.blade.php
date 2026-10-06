<x-app-shell surface="pos" title="Bibliotheksbetrieb">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Arbeitsplatz"
        lead="Scannen und sehen, was heute ansteht."
    />

    @if (session('workspace_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('workspace_success') }}</x-ui.alert>
    @endif

    @if (session('workspace_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('workspace_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="bc-pos-layout">
        @can('circulation.manage')
            <section class="bc-work-panel" aria-labelledby="scan-heading">
                <div class="bc-section-heading"><h2 id="scan-heading">Scannen</h2></div>
                <form method="post" action="{{ route('pos.scan') }}" class="bc-pos-scan">
                    @csrf
                    <x-ui.input
                        label="Bibliotheksnummer oder Exemplar-Barcode"
                        name="code"
                        hint="Bibliotheksnummer: öffnet die Ausleihe für diese Person. Barcode eines ausgeliehenen Exemplars: bucht die Rückgabe sofort. Für mehrere Vorgänge mit Beleg die Seite „Ausleihe“ nutzen."
                        placeholder="Code scannen oder eingeben, dann Eingabetaste"
                        autocomplete="off"
                        autofocus
                    />
                    <x-ui.button type="submit">Übernehmen</x-ui.button>
                </form>
            </section>
        @endcan

        <aside class="bc-work-panel" aria-labelledby="today-heading">
            <div class="bc-section-heading"><h2 id="today-heading">Heute, {{ $today->isoFormat('dd, DD.MM.YYYY') }}</h2></div>
            <p class="bc-section-copy">
                @if ($closure)
                    <x-ui.badge variant="danger">Geschlossen</x-ui.badge> {{ $closure->reason ?: 'Schließtag' }}
                @elseif ($hours === [])
                    <x-ui.badge>Kein Betrieb</x-ui.badge> An diesem Wochentag ist nicht geöffnet.
                @else
                    <x-ui.badge variant="success">Geöffnet</x-ui.badge> {{ implode(' und ', $hours) }} Uhr
                @endif
            </p>
        </aside>
    </div>

    @if ($tiles !== [])
        <section class="bc-content-section" aria-labelledby="overview-heading">
            <div class="bc-section-heading"><h2 id="overview-heading">Was ansteht</h2></div>
            <ul class="bc-pos-tiles">
                @foreach ($tiles as $tile)
                    <li class="bc-pos-tile {{ $tile['warn'] && $tile['value'] > 0 ? 'bc-pos-tile--warn' : '' }}">
                        @if ($tile['url'])
                            <a href="{{ $tile['url'] }}" class="bc-pos-tile__body">
                                <strong>{{ $tile['value'] }}</strong>
                                <span>{{ $tile['label'] }}</span>
                            </a>
                        @else
                            <div class="bc-pos-tile__body">
                                <strong>{{ $tile['value'] }}</strong>
                                <span>{{ $tile['label'] }}</span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <div class="bc-context-actions bc-pos-quicklinks" aria-label="Schnellzugriff">
        @can('patrons.lookup')
            <x-ui.button href="{{ route('pos.patrons.index') }}" variant="secondary">Ausleihkonto suchen</x-ui.button>
        @endcan
        @can('catalog.manage')
            <x-ui.button href="{{ route('pos.catalog.index') }}" variant="secondary">Katalog pflegen</x-ui.button>
        @endcan
        @can('circulation.reports')
            <x-ui.button href="{{ route('pos.reports.class-loans') }}" variant="secondary">Klassenlisten</x-ui.button>
        @endcan
    </div>
</x-app-shell>
