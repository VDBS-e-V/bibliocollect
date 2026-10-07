@php
    $preview = $preview ?? false;
@endphp

<x-app-shell surface="administration" title="Verwaltung" :preview="$preview">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Übersicht"
        lead="Was ist eingerichtet, was fehlt noch, und wo findest du die Verwaltungsaufgaben?"
    />

    @if (($snapshot ?? null) !== null)
        @php
            $labels = ['ok' => 'In Ordnung', 'warn' => 'Achtung', 'fail' => 'Fehler'];
        @endphp
        <x-ui.alert :variant="$snapshot['status'] === 'ok' ? 'success' : ($snapshot['status'] === 'warn' ? 'warning' : 'error')" title="Systemzustand: {{ $labels[$snapshot['status']] }}">
            <a href="{{ route('administration.system.index') }}">Einzelheiten ansehen</a>
        </x-ui.alert>
    @endif

    @if (($checklist ?? []) !== [])
        <section class="bc-content-section" aria-labelledby="launch-heading">
            <div class="bc-section-heading"><h2 id="launch-heading">Startklar?</h2></div>
            <p class="bc-section-copy">Alles, was für den Betrieb eingerichtet sein sollte. Ein Haken heißt: erledigt.</p>
            <table class="bc-calendar-table">
                <thead><tr><th scope="col">Punkt</th><th scope="col">Stand</th><th scope="col">Einzelheiten</th></tr></thead>
                <tbody>
                    @foreach ($checklist as $item)
                        <tr>
                            <th scope="row">@if ($item['route'])<a href="{{ $item['route'] }}">{{ $item['label'] }}</a>@else{{ $item['label'] }}@endif</th>
                            <td><x-ui.badge :variant="$item['ok'] ? 'success' : 'warning'">{{ $item['ok'] ? 'Erledigt' : 'Offen' }}</x-ui.badge></td>
                            <td>{{ $item['detail'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    @foreach (($areas ?? []) as $group)
        <section class="bc-content-section" aria-labelledby="admin-group-{{ $loop->index }}">
            <div class="bc-section-heading"><h2 id="admin-group-{{ $loop->index }}">{{ $group['title'] }}</h2></div>
            <div class="bc-admin-list">
                @foreach ($group['items'] as $item)
                    <div><strong><a href="{{ route($item['route']) }}">{{ $item['label'] }}</a></strong><span>{{ $item['text'] }}</span></div>
                @endforeach
            </div>
        </section>
    @endforeach

    @if ($preview)
        <x-ui.alert title="Entwicklungsansicht">Dies ist die Vorschau ohne Daten.</x-ui.alert>
    @endif
</x-app-shell>
