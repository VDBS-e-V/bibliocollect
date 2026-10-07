<x-app-shell surface="pos" title="Notfallliste">
    <div class="bc-class-report-screen">
        <x-ui.page-header
            kicker="Notbetrieb"
            title="Notfallliste"
            lead="Alle offenen Ausleihen und Vormerkungen, sortiert nach Name. Zum Ausdrucken und Aufbewahren am Ausleihplatz."
        />

        <div class="bc-context-actions">
            <a href="{{ route('pos.emergency') }}">← Zum Notbetrieb</a>
            <a href="{{ route('pos.emergency.export') }}">Als CSV herunterladen</a>
            <button type="button" class="bc-button bc-button--primary" data-print>Drucken</button>
        </div>
    </div>

    <section class="bc-class-report" aria-labelledby="emergency-loans-heading">
        <h2 id="emergency-loans-heading">Offene Ausleihen</h2>
        <p class="bc-class-report__meta">Stand {{ $now->format('d.m.Y H:i') }} Uhr · {{ count($loans) }} {{ count($loans) === 1 ? 'Ausleihe' : 'Ausleihen' }} · Vertraulich, enthält Namen</p>

        @if ($loans === [])
            <p>Es gibt keine offenen Ausleihen.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Klasse</th>
                        <th scope="col">Medium</th>
                        <th scope="col">Inventarnr.</th>
                        <th scope="col">Ausgeliehen</th>
                        <th scope="col">Fällig</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($loans as $row)
                        <tr>
                            <th scope="row">{{ $row['patron'] }}</th>
                            <td>{{ $row['class'] !== '' ? $row['class'] : '—' }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td class="bc-tabular">{{ $row['barcode'] }}</td>
                            <td class="bc-tabular">{{ $row['checked_out_on'] }}</td>
                            <td class="bc-tabular">{{ $row['due_on'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="bc-class-report" aria-labelledby="emergency-reservations-heading">
        <h2 id="emergency-reservations-heading">Offene Vormerkungen</h2>
        <p class="bc-class-report__meta">Stand {{ $now->format('d.m.Y H:i') }} Uhr · {{ count($reservations) }} {{ count($reservations) === 1 ? 'Vormerkung' : 'Vormerkungen' }}</p>

        @if ($reservations === [])
            <p>Es gibt keine offenen Vormerkungen.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Klasse</th>
                        <th scope="col">Titel</th>
                        <th scope="col">Stand</th>
                        <th scope="col">Inventarnr.</th>
                        <th scope="col">Abholen bis</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservations as $row)
                        <tr>
                            <th scope="row">{{ $row['patron'] }}</th>
                            <td>{{ $row['class'] !== '' ? $row['class'] : '—' }}</td>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['status'] }}</td>
                            <td class="bc-tabular">{{ $row['ready_barcode'] !== '' ? $row['ready_barcode'] : '—' }}</td>
                            <td class="bc-tabular">{{ $row['pickup_until'] !== '' ? $row['pickup_until'] : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</x-app-shell>
