@php
    $counts = $plan['counts'];
    $statusLabels = ['new' => 'Wird angelegt', 'existing' => 'Schon vorhanden', 'duplicate' => 'Doppelt in der Datei', 'error' => 'Fehler'];
    $statusVariants = ['new' => 'success', 'existing' => 'neutral', 'duplicate' => 'neutral', 'error' => 'danger'];
    $kindLabels = ['student' => 'Schüler:in', 'teacher' => 'Lehrkraft', 'employee' => 'Mitarbeiter:in'];
@endphp

<x-app-shell surface="pos" title="Import prüfen">
    <x-ui.page-header
        kicker="Ausleihkonten"
        title="Import prüfen"
        lead="Noch ist nichts angelegt. Prüfe die Zeilen und bestätige den Import."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.patrons.import.create') }}">← Andere Datei wählen</a>
    </div>

    @if (session('workspace_error'))
        <x-ui.alert variant="error" title="Import nicht möglich">{{ session('workspace_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="import-summary-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="import-summary-heading">Ergebnis der Prüfung</h2>
            <span>{{ array_sum($counts) }} Zeilen</span>
        </div>
        <p class="bc-section-copy">
            <x-ui.badge variant="success">{{ $counts['new'] }} neu</x-ui.badge>
            <x-ui.badge>{{ $counts['existing'] }} schon vorhanden</x-ui.badge>
            <x-ui.badge>{{ $counts['duplicate'] }} doppelt</x-ui.badge>
            <x-ui.badge :variant="$counts['error'] > 0 ? 'danger' : 'neutral'">{{ $counts['error'] }} mit Fehler</x-ui.badge>
            @if ($plan['year'])
                · Klassen aus dem aktiven Schuljahr {{ $plan['year']->name }}
            @endif
        </p>

        @if ($counts['error'] > 0)
            <x-ui.alert variant="error" title="Import gesperrt">Solange es Fehler gibt, wird nichts angelegt. Korrigiere die Datei und lade sie erneut hoch.</x-ui.alert>
        @elseif ($counts['new'] === 0)
            <x-ui.alert title="Nichts zu tun">Alle Personen sind bereits vorhanden.</x-ui.alert>
        @else
            <form method="post" action="{{ route('pos.patrons.import.commit', ['token' => $token]) }}">
                @csrf
                <label class="bc-public-catalog-filter__check" for="confirm">
                    <input id="confirm" name="confirm" type="checkbox" value="1">
                    <span><strong>Ich habe die Vorschau geprüft und möchte {{ $counts['new'] }} Ausleihkonten anlegen.</strong></span>
                </label>
                <x-ui.button type="submit">Ausleihkonten anlegen</x-ui.button>
            </form>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="import-rows-heading">
        <div class="bc-section-heading"><h2 id="import-rows-heading">Zeilen</h2></div>
        <table class="bc-calendar-table">
            <thead>
                <tr>
                    <th scope="col">Zeile</th>
                    <th scope="col">Status</th>
                    <th scope="col">Name</th>
                    <th scope="col">Geburtsdatum</th>
                    <th scope="col">Art</th>
                    <th scope="col">Klasse</th>
                    <th scope="col">Hinweis</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plan['rows'] as $row)
                    <tr>
                        <th scope="row" class="bc-tabular">{{ $row['line'] }}</th>
                        <td><x-ui.badge :variant="$statusVariants[$row['status']]">{{ $statusLabels[$row['status']] }}</x-ui.badge></td>
                        <td>{{ trim($row['first_name'].' '.$row['last_name']) ?: '—' }}</td>
                        <td class="bc-tabular">{{ $row['birth_date'] ? \Carbon\Carbon::parse($row['birth_date'])->format('d.m.Y') : '—' }}</td>
                        <td>{{ $row['kind'] ? $kindLabels[$row['kind']->value] : '—' }}</td>
                        <td>{{ $row['class_name'] ?? '—' }}</td>
                        <td>
                            @foreach ($row['messages'] as $message)
                                <small class="bc-public-metadata-source">{{ $message }}</small>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</x-app-shell>
