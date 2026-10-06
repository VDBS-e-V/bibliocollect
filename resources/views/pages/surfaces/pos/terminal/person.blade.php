@php
    $typeLabels = ['checkout' => 'Ausleihe', 'renew' => 'Verlängerung', 'return' => 'Rückgabe'];
    $counts = collect($items)->countBy('type');
@endphp

<x-app-shell surface="pos" :title="$patron->displayName()">
    <x-ui.page-header
        kicker="Ausleihe"
        :title="$patron->displayName()"
        :lead="'Bibliotheksnummer '.$patron->library_number.($patron->schoolClass ? ' · Klasse '.$patron->schoolClass->name : '')"
    />

    @if (session('terminal_notice'))
        <x-ui.alert title="Hinweis">{{ session('terminal_notice') }}</x-ui.alert>
    @endif

    @if (session('terminal_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('terminal_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <div class="bc-context-actions">
        <form method="post" action="{{ route('pos.terminal.discard') }}">
            @csrf
            <button type="submit" class="bc-intake-linkbutton">← Zurück zum Start (Vorgang verwerfen)</button>
        </form>
        @if ($patron->blocked_at)
            <x-ui.badge variant="danger">Ausleihkonto gesperrt</x-ui.badge>
        @endif
        <span>{{ $openLoans->count() }}{{ $maxOpenLoans > 0 ? ' von '.$maxOpenLoans : '' }} Medien ausgeliehen</span>
    </div>

    <div class="bc-terminal">
        <section class="bc-work-panel" aria-labelledby="terminal-loans-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="terminal-loans-heading">Ausgeliehene Medien</h2>
                <span>{{ $openLoans->count() }}</span>
            </div>

            @if ($openLoans->isEmpty())
                <p class="bc-section-copy">Zurzeit ist nichts ausgeliehen.</p>
            @else
                <table class="bc-calendar-table">
                    <thead>
                        <tr>
                            <th scope="col">Medium</th>
                            <th scope="col">Fällig</th>
                            <th scope="col">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($openLoans as $loan)
                            @php
                                $blocks = $renewable[(string) $loan->getKey()] ?? [];
                                $overdue = $loan->due_on->copy()->startOfDay()->lessThan(now()->startOfDay());
                                $state = $queued[(string) $loan->getKey()] ?? null;
                            @endphp
                            <tr>
                                <th scope="row">
                                    {{ $loan->copy->edition->title->preferred_title }}
                                    <small class="bc-public-metadata-source bc-tabular">{{ $loan->copy->barcode }}@if ($loan->renewal_count > 0) · {{ $loan->renewal_count }}-mal verlängert @endif</small>
                                </th>
                                <td class="bc-tabular">
                                    {{ $loan->due_on->format('d.m.Y') }}
                                    @if ($overdue)<x-ui.badge variant="danger">überfällig</x-ui.badge>@endif
                                </td>
                                <td>
                                    @if ($state === 'return')
                                        <x-ui.badge variant="success">wird zurückgegeben</x-ui.badge>
                                    @elseif ($state === 'renew')
                                        <x-ui.badge variant="success">wird verlängert</x-ui.badge>
                                    @else
                                        <div class="bc-terminal__rowactions">
                                            @if ($blocks === [])
                                                <form method="post" action="{{ route('pos.terminal.renew', ['loanId' => $loan->getKey()]) }}">
                                                    @csrf
                                                    <x-ui.button type="submit" variant="secondary">Verlängern</x-ui.button>
                                                </form>
                                            @endif
                                            <form method="post" action="{{ route('pos.terminal.return', ['loanId' => $loan->getKey()]) }}">
                                                @csrf
                                                <x-ui.button type="submit" variant="secondary">Zurückgeben</x-ui.button>
                                            </form>
                                        </div>
                                        @if ($blocks !== [])
                                            <small class="bc-public-metadata-source">Keine Verlängerung: {{ implode(' ', $blocks) }}</small>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="bc-work-panel bc-terminal__cart" aria-labelledby="terminal-cart-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="terminal-cart-heading">Vorgang</h2>
                <span>{{ count($items) }} {{ count($items) === 1 ? 'Position' : 'Positionen' }}</span>
            </div>

            <form method="post" action="{{ route('pos.terminal.scan') }}" class="bc-pos-scan">
                @csrf
                <x-ui.input
                    label="Exemplar scannen: ausleihen (oder zurückgeben, wenn es dieser Person gehört)"
                    name="code"
                    autocomplete="off"
                    autofocus
                />
                <x-ui.button type="submit">Hinzufügen</x-ui.button>
            </form>

            @if ($items === [])
                <p class="bc-section-copy">Noch keine Positionen. Barcodes scannen oder links „Verlängern“ bzw. „Zurückgeben“ wählen.</p>
            @else
                <table class="bc-calendar-table">
                    <thead>
                        <tr>
                            <th scope="col">Vorgang</th>
                            <th scope="col">Medium</th>
                            <th scope="col">Ergebnis</th>
                            <th scope="col"><span class="bc-visually-hidden">Entfernen</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $index => $item)
                            <tr>
                                <td><x-ui.badge :variant="$item['type'] === 'return' ? 'neutral' : 'success'">{{ $typeLabels[$item['type']] }}</x-ui.badge></td>
                                <th scope="row">{{ $item['title'] }} <small class="bc-public-metadata-source bc-tabular">{{ $item['barcode'] }}</small></th>
                                <td>
                                    @if ($item['type'] === 'return')
                                        zurück
                                    @else
                                        fällig {{ \Carbon\Carbon::parse($item['due_on'])->format('d.m.Y') }}
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('pos.terminal.item.remove', ['index' => $index]) }}">
                                        @csrf
                                        <button type="submit" class="bc-intake-linkbutton" aria-label="{{ $item['title'] }} aus dem Vorgang entfernen">Entfernen</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="bc-section-copy">
                    {{ $counts->get('checkout', 0) }} ausleihen · {{ $counts->get('renew', 0) }} verlängern · {{ $counts->get('return', 0) }} zurücknehmen
                </p>

                <div class="bc-terminal__actions">
                    <form method="post" action="{{ route('pos.terminal.confirm') }}">
                        @csrf
                        <x-ui.button type="submit">Vorgang bestätigen</x-ui.button>
                    </form>
                    <form method="post" action="{{ route('pos.terminal.discard') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary">Vorgang verwerfen</x-ui.button>
                    </form>
                </div>
            @endif
        </section>
    </div>
</x-app-shell>
