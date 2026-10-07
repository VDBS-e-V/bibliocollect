@php
    $sections = [
        'missing' => ['Fehlt', 'Laut System stehen sie an einem geprüften Regalbrett, wurden aber nicht gescannt (ausgeliehene sind nicht dabei).'],
        'misplaced' => ['Falsch einsortiert', 'Gescannt, aber laut System an einem anderen Regalbrett.'],
        'unplaced' => ['Ohne Standort', 'Gescannt, im System steht noch kein Standort.'],
        'inactive' => ['Verloren oder ausgesondert, aber im Regal', 'Diese Bücher stehen im Regal, gelten im System aber als verloren oder ausgesondert.'],
        'unknown' => ['Unbekannt', 'Diese Inventarnummern gibt es im System nicht.'],
    ];
    $lead = ($count->isOpen() ? 'Zwischenstand. ' : 'Abgeschlossen am '.$count->closed_at?->timezone(config('app.timezone'))->format('d.m.Y H:i').'. ')
        .$report['scanned'].' Bücher gescannt an '.count($report['shelves']).' Regalbrett(ern).';
@endphp

<x-app-shell surface="pos" :title="'Bericht: '.$count->name">
    <x-ui.page-header kicker="Inventur" :title="$count->name" :lead="$lead" />

    <div class="bc-context-actions">
        <a href="{{ route('pos.inventory') }}">← Zur Übersicht</a>
        @if ($count->isOpen())
            <a href="{{ route('pos.inventory.show', ['countId' => $count->getKey()]) }}">Weiter zählen</a>
        @endif
        <a href="{{ route('pos.inventory.export', ['countId' => $count->getKey()]) }}">Als CSV herunterladen</a>
        <button type="button" class="bc-intake-linkbutton" data-print>Drucken</button>
    </div>

    @if (session('inventory_notice'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('inventory_notice') }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="summary-heading">
        <div class="bc-section-heading"><h2 id="summary-heading">Zusammenfassung</h2></div>
        <ul class="bc-pos-tiles">
            <li class="bc-pos-tile"><div class="bc-pos-tile__body"><strong>{{ $report['ok']->count() }}</strong><span>richtig einsortiert</span></div></li>
            @foreach ($sections as $key => $section)
                @php
                    $total = $report[$key]->count();
                @endphp
                <li class="bc-pos-tile {{ $total > 0 && in_array($key, ['missing', 'misplaced'], true) ? 'bc-pos-tile--warn' : '' }}"><div class="bc-pos-tile__body"><strong>{{ $total }}</strong><span>{{ $section[0] }}</span></div></li>
            @endforeach
        </ul>
        <p class="bc-section-copy">{{ $report['onLoan'] }} Exemplar(e) an den geprüften Regalbrettern sind ausgeliehen und zählen nicht als fehlend.</p>

        @if ($report['misplaced']->isNotEmpty() || $report['unplaced']->isNotEmpty())
            <form method="post" action="{{ route('pos.inventory.apply', ['countId' => $count->getKey()]) }}">
                @csrf
                <x-ui.button type="submit" variant="secondary" data-confirm="Die Standorte im System auf die gefundenen Regalbretter setzen?">Standorte korrigieren ({{ $report['misplaced']->count() + $report['unplaced']->count() }})</x-ui.button>
            </form>
        @endif
    </section>

    @foreach ($sections as $key => $section)
        @if ($report[$key]->isNotEmpty())
            <section class="bc-content-section" aria-labelledby="section-{{ $key }}-heading">
                <div class="bc-section-heading bc-section-heading--with-meta"><h2 id="section-{{ $key }}-heading">{{ $section[0] }}</h2><span>{{ $report[$key]->count() }}</span></div>
                <p class="bc-section-copy">{{ $section[1] }}</p>
                <table class="bc-calendar-table">
                    <thead><tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col">Gefunden auf</th><th scope="col">Laut System</th></tr></thead>
                    <tbody>
                        @foreach ($report[$key] as $row)
                            @php
                                $copy = $key === 'missing' ? $row : $row->copy;
                            @endphp
                            <tr>
                                <th scope="row" class="bc-tabular">{{ $copy?->barcode ?? $row->barcode }}</th>
                                <td>{{ $copy?->edition->title->preferred_title ?? '—' }}</td>
                                <td>{{ $key === 'missing' ? '—' : $row->shelf_code }}</td>
                                <td>{{ $copy?->shelf_location ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    @endforeach
</x-app-shell>
