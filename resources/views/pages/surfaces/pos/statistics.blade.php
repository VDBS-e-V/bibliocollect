@php
    $monthNames = ['01' => 'Jan', '02' => 'Feb', '03' => 'Mär', '04' => 'Apr', '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug', '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Dez'];
    $maxMonth = max(1, collect($stats['by_month'])->max('loans'));
    $statusLabels = ['active' => 'Aktiv', 'damaged' => 'Beschädigt', 'lost' => 'Verloren', 'withdrawn' => 'Ausgesondert'];
    $mediaLabels = ['book' => 'Buch', 'audiobook' => 'Hörbuch', 'ebook' => 'E-Book', 'comic' => 'Comic', 'manga' => 'Manga', 'game' => 'Spiel'];
    $kpis = [
        ['Ausleihen', $stats['loans']],
        ['Rückgaben', $stats['returns']],
        ['Verlängerungen', $stats['renewals']],
        ['Aktive Leser:innen', $stats['active_patrons']],
        ['Aktuell ausgeliehen', $stats['open']],
        ['Davon überfällig', $stats['overdue']],
    ];
@endphp

<x-app-shell surface="pos" title="Statistik">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Statistik"
        lead="Kennzahlen zur Ausleihe. Die Auswertung enthält nur Zahlen, keine Personen."
    />

    <form method="get" action="{{ route('pos.statistics') }}" class="bc-audit-filter">
        <x-ui.select label="Zeitraum" name="zeitraum" data-auto-submit>
            @foreach ($schoolYears as $year)
                <option value="{{ $year->getKey() }}" @selected($selected === (string) $year->getKey())>Schuljahr {{ $year->name }}</option>
            @endforeach
            <option value="letzte12" @selected($selected === 'letzte12')>Letzte 12 Monate</option>
            <option value="alle" @selected($selected === 'alle')>Gesamter Zeitraum</option>
        </x-ui.select>
        <p class="bc-section-copy">{{ $from->format('d.m.Y') }} bis {{ $to->format('d.m.Y') }}</p>
        <noscript><x-ui.button type="submit" variant="secondary">Anzeigen</x-ui.button></noscript>
    </form>

    <div class="bc-context-actions">
        <a href="{{ route('pos.statistics.download', ['zeitraum' => $selected]) }}">Als CSV herunterladen</a>
        <button type="button" class="bc-intake-linkbutton" data-print>Drucken</button>
    </div>

    <section class="bc-content-section" aria-labelledby="kpi-heading">
        <div class="bc-section-heading"><h2 id="kpi-heading">Kennzahlen</h2></div>
        <ul class="bc-pos-tiles">
            @foreach ($kpis as [$label, $value])
                <li class="bc-pos-tile {{ $label === 'Davon überfällig' && $value > 0 ? 'bc-pos-tile--warn' : '' }}">
                    <div class="bc-pos-tile__body"><strong>{{ number_format($value, 0, ',', '.') }}</strong><span>{{ $label }}</span></div>
                </li>
            @endforeach
        </ul>
        <p class="bc-section-copy">Vormerkungen jetzt: {{ $stats['waiting_reservations'] }} wartend, {{ $stats['ready_reservations'] }} abholbereit.</p>
    </section>

    <section class="bc-content-section" aria-labelledby="month-heading">
        <div class="bc-section-heading"><h2 id="month-heading">Ausleihen je Monat</h2></div>
        <table class="bc-calendar-table">
            <caption class="bc-visually-hidden">Ausleihen je Monat im gewählten Zeitraum</caption>
            <thead><tr><th scope="col">Monat</th><th scope="col">Ausleihen</th><th scope="col"><span class="bc-visually-hidden">Balken</span></th></tr></thead>
            <tbody>
                @foreach ($stats['by_month'] as $row)
                    @php [$y, $m] = explode('-', $row['month']); @endphp
                    <tr>
                        <th scope="row">{{ $monthNames[$m] }} {{ $y }}</th>
                        <td class="bc-tabular">{{ $row['loans'] }}</td>
                        <td class="bc-stat-bar-cell"><span class="bc-stat-bar" style="width: {{ round($row['loans'] / $maxMonth * 100) }}%"></span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <div class="bc-stat-grid">
        <section class="bc-content-section" aria-labelledby="popular-heading">
            <div class="bc-section-heading"><h2 id="popular-heading">Beliebteste Titel</h2></div>
            @if ($stats['popular'] === [])
                <p class="bc-section-copy">Keine Ausleihen im Zeitraum.</p>
            @else
                <table class="bc-calendar-table">
                    <thead><tr><th scope="col">Titel</th><th scope="col">Ausleihen</th></tr></thead>
                    <tbody>
                        @foreach ($stats['popular'] as $row)
                            <tr><th scope="row">{{ $row['title'] }}</th><td class="bc-tabular">{{ $row['loans'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="bc-content-section" aria-labelledby="class-heading">
            <div class="bc-section-heading"><h2 id="class-heading">Ausleihen nach Klasse</h2></div>
            <p class="bc-section-copy">Nach der heutigen Klasse der Leser:innen; anonymisierte Ausleihen fehlen hier.</p>
            @if ($stats['by_class'] === [])
                <p class="bc-section-copy">Keine Daten.</p>
            @else
                <table class="bc-calendar-table">
                    <thead><tr><th scope="col">Klasse</th><th scope="col">Ausleihen</th></tr></thead>
                    <tbody>
                        @foreach ($stats['by_class'] as $row)
                            <tr><th scope="row">{{ $row['class'] }}</th><td class="bc-tabular">{{ $row['loans'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="bc-content-section" aria-labelledby="media-heading">
            <div class="bc-section-heading"><h2 id="media-heading">Ausleihen nach Medientyp</h2></div>
            @if ($stats['by_media_type'] === [])
                <p class="bc-section-copy">Keine Daten.</p>
            @else
                <table class="bc-calendar-table">
                    <thead><tr><th scope="col">Medientyp</th><th scope="col">Ausleihen</th></tr></thead>
                    <tbody>
                        @foreach ($stats['by_media_type'] as $row)
                            <tr><th scope="row">{{ $row['media_type'] ? ($mediaLabels[mb_strtolower($row['media_type'])] ?? $row['media_type']) : 'Nicht angegeben' }}</th><td class="bc-tabular">{{ $row['loans'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="bc-content-section" aria-labelledby="stock-heading">
            <div class="bc-section-heading"><h2 id="stock-heading">Bestand</h2></div>
            <table class="bc-calendar-table">
                <tbody>
                    <tr><th scope="row">Titel</th><td class="bc-tabular">{{ number_format($stats['stock']['titles'], 0, ',', '.') }}</td></tr>
                    <tr><th scope="row">Ausgaben</th><td class="bc-tabular">{{ number_format($stats['stock']['editions'], 0, ',', '.') }}</td></tr>
                    @foreach ($stats['stock']['copies'] as $status => $count)
                        <tr><th scope="row">Exemplare: {{ $statusLabels[$status] ?? $status }}</th><td class="bc-tabular">{{ number_format($count, 0, ',', '.') }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </div>
</x-app-shell>
