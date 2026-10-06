@php
    $modes = ['checkout' => 'Ausleihe', 'renew' => 'Verlängern', 'return' => 'Rückgabe'];
    $typeLabels = ['checkout' => 'Ausleihe', 'renew' => 'Verlängerung', 'return' => 'Rückgabe'];
    $counts = collect($items)->countBy('type');
@endphp

<x-app-shell surface="pos" title="Ausleihe">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Ausleihe"
        lead="Person wählen, Ausleihen, Verlängerungen und Rückgaben sammeln und zum Schluss gemeinsam bestätigen. Bis dahin wird nichts gebucht."
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

    <div class="bc-terminal">
        <section class="bc-work-panel bc-terminal__person" aria-labelledby="terminal-person-heading">
            <div class="bc-section-heading"><h2 id="terminal-person-heading">1. Person</h2></div>

            @if ($patron)
                <div class="bc-terminal__patron">
                    <strong>{{ $patron->displayName() }}</strong>
                    <span>{{ $patron->library_number }}@if ($patron->schoolClass) · Klasse {{ $patron->schoolClass->name }}@endif</span>
                    <span>{{ $openLoans->count() }}{{ $maxOpenLoans > 0 ? ' von '.$maxOpenLoans : '' }} Medien ausgeliehen</span>
                    @if ($patron->blocked_at)
                        <x-ui.badge variant="danger">Ausleihkonto gesperrt</x-ui.badge>
                    @endif
                </div>
                <form method="post" action="{{ route('pos.terminal.patron.clear') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Andere Person wählen</x-ui.button>
                </form>
            @else
                <form method="post" action="{{ route('pos.terminal.patron') }}" class="bc-pos-scan">
                    @csrf
                    <x-ui.input label="Bibliotheksnummer scannen oder Namen suchen" name="code" :value="$term" autocomplete="off" autofocus />
                    <x-ui.button type="submit">Person wählen</x-ui.button>
                </form>

                @if ($term !== '')
                    @if ($results->isEmpty())
                        <p class="bc-section-copy">Niemand gefunden für „{{ $term }}“.</p>
                    @else
                        <ul class="bc-terminal__results" aria-label="Gefundene Personen">
                            @foreach ($results as $result)
                                <li>
                                    <form method="post" action="{{ route('pos.terminal.patron') }}">
                                        @csrf
                                        <input type="hidden" name="patron_id" value="{{ $result->getKey() }}">
                                        <button type="submit" class="bc-terminal__result">
                                            <strong>{{ $result->last_name }}, {{ $result->first_name }}</strong>
                                            <span>{{ $result->library_number }}@if ($result->schoolClass) · {{ $result->schoolClass->name }}@endif</span>
                                        </button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
                <p class="bc-section-copy">Rückgaben lassen sich auch ohne Person buchen (Modus „Rückgabe“).</p>
            @endif

            @if ($patron && $openLoans->isNotEmpty())
                <h3 class="bc-terminal__sub">Offene Ausleihen</h3>
                <ul class="bc-terminal__loans">
                    @foreach ($openLoans as $loan)
                        @php
                            $blocks = $renewable[(string) $loan->getKey()] ?? [];
                            $overdue = $loan->due_on->copy()->startOfDay()->lessThan(now()->startOfDay());
                            $queued = in_array((string) $loan->getKey(), $inCart, true);
                        @endphp
                        <li>
                            <div>
                                <strong>{{ $loan->copy->edition->title->preferred_title }}</strong>
                                <span class="bc-tabular">{{ $loan->copy->barcode }} · fällig {{ $loan->due_on->format('d.m.Y') }}</span>
                                @if ($overdue)<x-ui.badge variant="danger">überfällig</x-ui.badge>@endif
                                @if ($blocks !== [] && ! $queued)<small class="bc-public-metadata-source">Keine Verlängerung: {{ implode(' ', $blocks) }}</small>@endif
                            </div>
                            @if ($queued)
                                <x-ui.badge variant="success">im Vorgang</x-ui.badge>
                            @elseif ($blocks === [])
                                <form method="post" action="{{ route('pos.terminal.renew', ['loanId' => $loan->getKey()]) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="secondary">Verlängern</x-ui.button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="bc-work-panel bc-terminal__cart" aria-labelledby="terminal-cart-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="terminal-cart-heading">2. Vorgang</h2>
                <span>{{ count($items) }} {{ count($items) === 1 ? 'Position' : 'Positionen' }}</span>
            </div>

            <div class="bc-pos-toolbar" role="group" aria-label="Vorgangsart">
                @foreach ($modes as $key => $label)
                    <form method="post" action="{{ route('pos.terminal.mode') }}">
                        @csrf
                        <input type="hidden" name="mode" value="{{ $key }}">
                        <button type="submit" class="bc-pos-toolbar__item {{ $mode === $key ? 'bc-pos-toolbar__item--active' : '' }}" @if ($mode === $key) aria-pressed="true" @else aria-pressed="false" @endif>{{ $label }}</button>
                    </form>
                @endforeach
            </div>

            <form method="post" action="{{ route('pos.terminal.scan') }}" class="bc-pos-scan">
                @csrf
                <x-ui.input
                    :label="'Exemplar-Barcode für '.$modes[$mode]"
                    name="code"
                    autocomplete="off"
                    :autofocus="(bool) $patron || $mode === 'return'"
                />
                <x-ui.button type="submit">Hinzufügen</x-ui.button>
            </form>

            @if ($items === [])
                <p class="bc-section-copy">Noch keine Positionen. Barcodes scannen oder „Verlängern“ bei den offenen Ausleihen wählen.</p>
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
