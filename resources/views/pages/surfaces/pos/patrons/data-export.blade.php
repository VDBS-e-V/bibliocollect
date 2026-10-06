@php
    $account = $data['ausleihkonto'];
    $sections = [
        'ausleihen' => ['Ausleihen', ['titel' => 'Titel', 'barcode' => 'Barcode', 'ausgeliehen_am' => 'Ausgeliehen', 'faellig_am' => 'Fällig', 'zurueckgegeben_am' => 'Zurückgegeben']],
        'vormerkungen' => ['Vormerkungen', ['titel' => 'Titel', 'status' => 'Status', 'vorgemerkt_am' => 'Vorgemerkt', 'abgeschlossen_am' => 'Abgeschlossen']],
        'sperren' => ['Sperren', ['aktion' => 'Aktion', 'grund' => 'Grund', 'am' => 'Am']],
        'statuswechsel' => ['Statuswechsel', ['von' => 'Von', 'nach' => 'Nach', 'wirksam_am' => 'Wirksam']],
        'erinnerungen' => ['Erinnerungs-E-Mails', ['art' => 'Art', 'gesendet_am' => 'Gesendet']],
    ];
@endphp

<x-app-shell surface="pos" title="Auskunft">
    <div class="bc-class-report-screen">
        <x-ui.page-header
            kicker="Ausleihkonto"
            title="Auskunft über gespeicherte Daten"
            :lead="'Bibliotheksnummer '.$account['bibliotheksnummer']"
        />

        <div class="bc-context-actions">
            <a href="{{ route('pos.patrons.show', ['patronId' => $patron->getKey()]) }}">← Zurück zum Ausleihkonto</a>
            <a href="{{ route('pos.patrons.data-export.download', ['patronId' => $patron->getKey()]) }}">Als JSON herunterladen</a>
            <button type="button" class="bc-button bc-button--secondary" data-print>Drucken</button>
        </div>

        <x-ui.alert title="Hinweis">{{ $data['hinweis'] }}</x-ui.alert>
    </div>

    <section class="bc-content-section" aria-labelledby="export-account-heading">
        <div class="bc-section-heading"><h2 id="export-account-heading">Ausleihkonto</h2></div>
        <dl class="bc-detail-list">
            @foreach ($account as $key => $value)
                <div><dt>{{ str_replace('_', ' ', ucfirst($key)) }}</dt><dd>{{ $value ?? '—' }}</dd></div>
            @endforeach
        </dl>
    </section>

    @if ($data['onlinekonto'])
        <section class="bc-content-section" aria-labelledby="export-user-heading">
            <div class="bc-section-heading"><h2 id="export-user-heading">Onlinekonto</h2></div>
            <dl class="bc-detail-list">
                @foreach ($data['onlinekonto'] as $key => $value)
                    <div><dt>{{ str_replace('_', ' ', ucfirst($key)) }}</dt><dd>{{ is_array($value) ? implode(', ', $value) : (is_bool($value) ? ($value ? 'ja' : 'nein') : ($value ?? '—')) }}</dd></div>
                @endforeach
            </dl>
        </section>
    @endif

    @foreach ($sections as $key => [$heading, $columns])
        <section class="bc-content-section" aria-labelledby="export-{{ $key }}-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="export-{{ $key }}-heading">{{ $heading }}</h2>
                <span>{{ count($data[$key]) }}</span>
            </div>
            @if ($data[$key] === [])
                <p class="bc-section-copy">Keine Einträge.</p>
            @else
                <table class="bc-calendar-table">
                    <thead><tr>@foreach ($columns as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach ($data[$key] as $row)
                            <tr>@foreach (array_keys($columns) as $column)<td>{{ $row[$column] ?? '—' }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endforeach
</x-app-shell>
