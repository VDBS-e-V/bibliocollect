<x-app-shell surface="pos" title="Überfällige Ausleihen">
    <x-ui.page-header
        kicker="Ausleihe"
        title="Überfällige Ausleihen"
        :lead="'Stand '.$today->format('d.m.Y').'. Die am längsten überfälligen stehen oben. Rückgabe: Inventarnummer im Arbeitsplatz scannen.'"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.home') }}">← Zurück zum Arbeitsplatz</a>
        @can('circulation.reports')
            <a href="{{ route('pos.reports.class-loans') }}">Klassenlisten zum Ausdrucken</a>
        @endcan
    </div>

    @if ($rows === [])
        <x-ui.alert title="Nichts überfällig">Es gibt keine überfälligen Ausleihen.</x-ui.alert>
    @else
        <table class="bc-calendar-table">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Klasse</th>
                    <th scope="col">Medium</th>
                    <th scope="col">Inventarnr.</th>
                    <th scope="col">Fällig</th>
                    <th scope="col">Überfällig</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <th scope="row">{{ $row['patron'] }}</th>
                        <td>{{ $row['class'] !== '' ? $row['class'] : '—' }}</td>
                        <td>{{ $row['title'] }}</td>
                        <td class="bc-tabular">{{ $row['barcode'] }}</td>
                        <td class="bc-tabular">{{ $row['due_on'] }}</td>
                        <td class="bc-tabular">{{ $row['days_overdue'] }} {{ $row['days_overdue'] === 1 ? 'Tag' : 'Tage' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-app-shell>
