<x-app-shell surface="administration" title="Regal-Dashboard">
    <x-ui.page-header kicker="Verwaltung" title="Regal-Dashboard"
        lead="Belegung und Pflegebedarf der Regalbretter auf einen Blick. Hinweise ändern keine Daten." />

    <div class="bc-context-actions">
        <a href="{{ route('administration.shelves.index') }}">← Regalbretter verwalten</a>
        <a href="{{ route('administration.topics.index') }}">Themenbereiche verwalten</a>
        <a href="{{ route('administration.shelves.dashboard.export', request()->query()) }}">CSV exportieren</a>
    </div>

    <section class="bc-content-section">
        <h2>Überblick</h2>
        <p>{{ $total }} Regalbretter · {{ $used }} Exemplare · {{ $withoutTopicCount }} Bretter ohne Themen · {{ $missingCapacity }} Bretter ohne Kapazitätsangabe</p>
        <p>{{ $withoutLocation }} Exemplare ohne Standort · {{ $unknownLocations }} Exemplare mit unbekanntem Standort · {{ $topicWithoutShelves }} Themen ohne Regalbrett</p>
        <p class="bc-section-copy">Die Bestandszahlen berücksichtigen alle Exemplare mit angegebenem Standort. Für Bretter ohne Kapazitätsangabe kann keine Auslastung berechnet werden. Alle Hinweise sind schreibfrei.</p>
    </section>

    <section class="bc-content-section">
        <h2>Filter</h2>
        <form method="get" action="{{ route('administration.shelves.dashboard') }}" class="bc-audit-filter">
            <label>Bereichsgruppe
                <select name="gruppe">
                    <option value="">Alle</option>
                    @foreach ($groupOptions as $option)
                        <option value="{{ $option }}" @selected($group === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <x-ui.input label="Bereich (z. B. A)" name="bereich" id="dashboard-area" :value="$area" />
            <x-ui.input label="Regal (z. B. 1)" name="regal" id="dashboard-rack" :value="$rack" />
            <label class="bc-checkbox-line"><input type="checkbox" name="ohne_thema" value="1" @checked($withoutTopic)> Nur ohne Thema</label>
            <x-ui.button type="submit">Filtern</x-ui.button>
            <a href="{{ route('administration.shelves.dashboard') }}">Zurücksetzen</a>
        </form>
    </section>

    <section class="bc-content-section">
        <h2>Regalbretter ({{ $total }})</h2>
        <div style="overflow-x:auto">
            <table class="bc-calendar-table">
                <thead>
                    <tr><th scope="col">Regalbrett</th><th scope="col">Gruppe / Bereich / Regal</th><th scope="col">Themen</th><th scope="col">Exemplare</th><th scope="col">Kapazität</th><th scope="col">Auslastung</th><th scope="col">Hinweise</th></tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($shelf = $row['shelf'])
                        <tr>
                            <th scope="row">
                                <a href="{{ route('administration.shelves.index', ['q' => $shelf->code]) }}">{{ $shelf->code }}</a>
                                @if ($shelf->label)<small>{{ $shelf->label }}</small>@endif
                            </th>
                            <td>{{ $row['group'] }} / {{ $row['area'] }} / {{ $row['rack'] }}</td>
                            <td>{{ $row['topics'] ?: '–' }}</td>
                            <td class="bc-tabular">{{ $row['copies'] }}</td>
                            <td class="bc-tabular">{{ $shelf->capacity ?? 'Nicht erfasst' }}</td>
                            <td class="bc-tabular">
                                {{ $shelf->capacity !== null ? round(100 * $row['copies'] / $shelf->capacity).' %' : 'Nicht erfasst' }}
                            </td>
                            <td>{{ $row['warning'] ?: '–' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Keine passenden Regalbretter vorhanden.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-shell>
