@php
    $counts = $plan['counts'];
    $statusLabels = ['new' => 'Wird angelegt', 'update' => 'Wird aktualisiert', 'existing' => 'Schon vorhanden', 'duplicate' => 'Doppelt in der Datei', 'error' => 'Fehler'];
    $statusVariants = ['new' => 'success', 'update' => 'warning', 'existing' => 'neutral', 'duplicate' => 'neutral', 'error' => 'danger'];
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
            @if ($update)<x-ui.badge variant="warning">{{ $counts['update'] }} werden aktualisiert</x-ui.badge>@endif
            <x-ui.badge>{{ $counts['existing'] }} schon vorhanden</x-ui.badge>
            <x-ui.badge>{{ $counts['duplicate'] }} doppelt</x-ui.badge>
            <x-ui.badge :variant="$counts['error'] > 0 ? 'danger' : 'neutral'">{{ $counts['error'] }} mit Fehler</x-ui.badge>
            @if ($plan['class'])
                · Klasse {{ $plan['class']->name }}@if ($plan['year']), Schuljahr {{ $plan['year']->name }}@endif
            @endif
        </p>

        @if ($counts['error'] > 0)
            <x-ui.alert variant="error" title="Import gesperrt">Solange es Fehler gibt, wird nichts angelegt. Korrigiere die Datei und lade sie erneut hoch.</x-ui.alert>
        @elseif ($counts['new'] === 0 && $counts['update'] === 0)
            <x-ui.alert title="Nichts zu tun">Alle Personen sind bereits vorhanden{{ $update ? ' und aktuell' : '' }}.</x-ui.alert>
        @else
            <form method="post" action="{{ route('pos.patrons.import.commit', ['token' => $token]) }}">
                @csrf
                <label class="bc-public-catalog-filter__check" for="confirm">
                    <input id="confirm" name="confirm" type="checkbox" value="1">
                    <span><strong>Ich habe die Vorschau geprüft und möchte {{ $counts['new'] }} Ausleihkonten anlegen@if ($counts['update'] > 0) und {{ $counts['update'] }} aktualisieren@endif.</strong></span>
                </label>
                <x-ui.button type="submit">{{ $counts['update'] > 0 ? 'Anlegen und aktualisieren' : 'Ausleihkonten anlegen' }}</x-ui.button>
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
                        <td>{{ $row['class_name'] ?? '—' }}</td>
                        <td>
                            @foreach ($row['messages'] as $message)
                                <small class="bc-public-metadata-source">{{ $message }}</small>
                            @endforeach
                            @foreach ($row['changes'] as $change)
                                <small class="bc-public-metadata-source">{{ $change }}</small>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
</x-app-shell>
