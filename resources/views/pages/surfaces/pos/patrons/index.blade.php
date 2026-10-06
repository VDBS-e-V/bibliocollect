<x-app-shell surface="pos" title="Ausleihkonten">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Ausleihkonto suchen"
        lead="Suche gezielt nach Bibliotheksnummer oder Name. Ohne Suchbegriff wird keine Personenliste angezeigt."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.home') }}">← Zurück zum Arbeitsplatz</a>
        @can('patrons.manage')
            <x-ui.button href="{{ route('pos.patrons.create') }}">Neues Ausleihkonto</x-ui.button>
            <a href="{{ route('pos.patrons.import.create') }}">Aus CSV importieren</a>
        @endcan
    </div>

    <section class="bc-patron-search" aria-labelledby="patron-search-heading">
        <div class="bc-section-heading">
            <h2 id="patron-search-heading">Person finden</h2>
        </div>

        <form method="get" action="{{ route('pos.patrons.index') }}" class="bc-patron-search__form" role="search">
            <x-ui.input
                label="Bibliotheksnummer oder Name"
                name="q"
                :value="$term"
                hint="Mindestens zwei Buchstaben/Ziffern, maximal 25 Treffer. E-Mail und Geburtsdatum werden nicht als Suchfelder verwendet."
                autocomplete="off"
                autofocus
            />
            <div class="bc-action-row">
                <x-ui.button type="submit">Suchen</x-ui.button>
                @if ($term !== '')
                    <x-ui.button href="{{ route('pos.patrons.index') }}" variant="secondary">Suche leeren</x-ui.button>
                @endif
            </div>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="results-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="results-heading">Treffer</h2>
            @if ($term !== '')
                <span>{{ $patrons->count() }} gefunden</span>
            @endif
        </div>

        @if ($term === '')
            <x-ui.alert title="Gezielte Suche">Gib eine Bibliotheksnummer oder einen Namen ein. Aus Datenschutzgründen gibt es hier keine vollständige Personenliste.</x-ui.alert>
        @elseif ($patrons->isEmpty())
            <x-ui.alert title="Keine Treffer">Für „{{ $term }}“ wurde kein Ausleihkonto gefunden.</x-ui.alert>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th scope="col">Bibliotheksnr.</th>
                        <th scope="col">Name</th>
                        <th scope="col">Klasse</th>
                        <th scope="col">Typ</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="sr-only">Aktion</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($patrons as $patron)
                        @php
                            $kindLabel = match ($patron->kind->value) {
                                'student' => 'Schüler:in',
                                'teacher' => 'Lehrkraft',
                                'employee' => 'Mitarbeiter:in',
                                default => $patron->kind->value,
                            };
                            $statusLabel = match ($patron->status->value) {
                                'active' => 'Aktiv',
                                'departed' => 'Ausgeschieden',
                                'archived' => 'Archiviert',
                                default => $patron->status->value,
                            };
                        @endphp
                        <tr>
                            <td class="bc-tabular">{{ $patron->library_number }}</td>
                            <td><strong>{{ $patron->displayName() }}</strong></td>
                            <td>{{ $patron->schoolClass?->name ?? '—' }}</td>
                            <td>{{ $kindLabel }}</td>
                            <td>
                                <div class="bc-status-stack">
                                    <x-ui.badge :variant="$patron->status->value === 'active' ? 'success' : 'neutral'">
                                        {{ $statusLabel }}
                                    </x-ui.badge>
                                    @if ($patron->blocked_at !== null)
                                        <x-ui.badge variant="danger">Gesperrt</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td><a href="{{ route('pos.patrons.show', ['patronId' => $patron->getKey()]) }}">Öffnen</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </section>
</x-app-shell>
