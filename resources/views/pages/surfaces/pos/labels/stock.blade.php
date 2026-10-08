@php
    $format = static fn (int $number): string => str_pad((string) $number, 7, '0', STR_PAD_LEFT);
@endphp

<x-app-shell surface="pos" title="Etiketten auf Vorrat">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Etiketten auf Vorrat drucken"
        :lead="'Inventarnummern im Voraus auf Etikettenbögen drucken (70 × 36 mm, '.$perSheet.' je Bogen). Das System prüft jede Nummer: Was schon einem Exemplar gehört, wird nie gedruckt.'"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.labels.copies') }}">← Zurück zu den Exemplar-Etiketten</a>
    </div>

    @if (session('stock_notice'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('stock_notice') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="stock-overview-heading">
        <div class="bc-section-heading"><h2 id="stock-overview-heading">Stand der Nummern</h2></div>
        @if ($overview['usedCount'] === 0)
            <p class="bc-section-copy">Es gibt noch kein Exemplar mit siebenstelliger Inventarnummer.</p>
        @else
            <p class="bc-section-copy">{{ $overview['usedCount'] }} siebenstellige Nummern sind vergeben, von <strong class="bc-tabular">{{ $format($overview['lowest']) }}</strong> bis <strong class="bc-tabular">{{ $format($overview['highest']) }}</strong>.@if ($overview['lastPrinted']) Zuletzt auf Vorrat gedruckt bis <strong class="bc-tabular">{{ $format($overview['lastPrinted']) }}</strong>.@endif</p>
        @endif
    </section>

    <form method="get" action="{{ route('pos.labels.stock') }}" class="bc-catalog-form">
        <input type="hidden" name="plan" value="1">

        <section class="bc-content-section" aria-labelledby="stock-mode-heading">
            <div class="bc-section-heading"><h2 id="stock-mode-heading">Was soll gedruckt werden?</h2></div>

            <fieldset class="bc-intake-fieldset">
                <legend>Art</legend>
                <label class="bc-public-catalog-filter__check" for="mode-reihe">
                    <input id="mode-reihe" type="radio" name="modus" value="reihe" @checked($mode === 'reihe')>
                    <span><strong>Reihe fortsetzen</strong><small>Ab einer Nummer die nächsten freien Nummern, vergebene werden übersprungen.</small></span>
                </label>
                <label class="bc-public-catalog-filter__check" for="mode-luecken">
                    <input id="mode-luecken" type="radio" name="modus" value="luecken" @checked($mode === 'luecken')>
                    <span><strong>Lücken der laufenden Reihe füllen (einmalig)</strong><small>Alle Nummern zwischen zwei Grenzen, die noch keinem Exemplar gehören, damit die Reihe lückenlos wird.</small></span>
                </label>
            </fieldset>

            <div class="bc-intake-fieldset__grid">
                <x-ui.input label="Erste Nummer (Reihe fortsetzen)" name="start" type="number" min="1" max="9999999" :value="$values['start']" hint="Vorgeschlagen: die nächste Nummer nach der höchsten vergebenen oder gedruckten." />
                <x-ui.input label="Anzahl Etiketten (Reihe fortsetzen)" name="anzahl" type="number" min="1" :max="$max" :value="$values['count']" :hint="'Höchstens '.$max.' (20 Bögen). Ein Bogen hat '.$perSheet.'.'" />
                <x-ui.input label="Lücken von Nummer" name="von" type="number" min="1" max="9999999" :value="$values['from']" />
                <x-ui.input label="Lücken bis Nummer" name="bis" type="number" min="1" max="9999999" :value="$values['to']" hint="Stell die Grenzen auf die echte laufende Reihe ein, Ausreißer wie 1234567 verfälschen sie." />
                <x-ui.input label="Erstes freies Etikett auf dem Bogen (1–{{ $perSheet }})" name="startplatz" type="number" min="1" :max="$perSheet" :value="$values['position']" />
            </div>

            <label class="bc-public-catalog-filter__check" for="stock-reprint">
                <input id="stock-reprint" type="checkbox" name="erneut" value="1" @checked($reprint)>
                <span><strong>Auch schon gedruckte Nummern noch einmal drucken</strong><small>Zum Beispiel, wenn ein Bogen unbrauchbar ist.</small></span>
            </label>

            <div class="bc-action-row"><x-ui.button type="submit" variant="secondary">Prüfen</x-ui.button></div>
        </section>
    </form>

    <section class="bc-content-section" aria-labelledby="stock-runs-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="stock-runs-heading">Letzte Drucke</h2>
            <span>{{ $printedCount }} Nummern als gedruckt gespeichert</span>
        </div>

        @if ($runs === [])
            <p class="bc-section-copy">Es wurde noch nichts auf Vorrat gedruckt.@if ($printedCount > 0) {{ $printedCount }} ältere Nummern sind ohne Druckauftrag als gedruckt gespeichert.@endif</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Gedruckt</th>
                        <th scope="col">Von</th>
                        <th scope="col">Art</th>
                        <th scope="col">Nummern</th>
                        <th scope="col">Etiketten</th>
                        <th scope="col"><span class="sr-only">Aktion</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($runs as $run)
                        <tr>
                            <td class="bc-tabular">{{ \Illuminate\Support\Carbon::parse($run['printed_at'])->timezone(config('app.timezone'))->format('d.m.Y H:i') }}</td>
                            <td>{{ $run['by'] ?? '—' }}</td>
                            <td>{{ $run['mode'] === 'luecken' ? 'Lücken gefüllt' : 'Reihe fortgesetzt' }}</td>
                            <td class="bc-tabular">{{ $run['first'] }} bis {{ $run['last'] }}</td>
                            <td class="bc-tabular">{{ $run['count'] }}@if ($run['remaining'] !== $run['count']) <small>({{ $run['remaining'] }} noch gespeichert)</small>@endif</td>
                            <td>
                                <form method="post" action="{{ route('pos.labels.stock.run.destroy', ['runId' => $run['id']]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="secondary" data-confirm-label="Löschen" data-confirm="Diesen Druckauftrag löschen? Seine Nummern gelten dann nicht mehr als gedruckt und werden wieder vergeben." aria-label="Druckauftrag {{ $run['first'] }} bis {{ $run['last'] }} löschen">Auftrag löschen</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($printedCount > 0)
            <form method="post" action="{{ route('pos.labels.stock.clear') }}">
                @csrf
                @method('DELETE')
                <x-ui.button type="submit" variant="secondary" data-confirm-label="Alle löschen" data-confirm="Alle {{ $printedCount }} als gedruckt gespeicherten Nummern löschen? Sie werden danach wieder vergeben und können noch einmal gedruckt werden.">Alle gedruckten Nummern löschen ({{ $printedCount }})</x-ui.button>
            </form>
            <p class="bc-section-copy">Das Löschen ändert nichts an Büchern oder Exemplaren. Es betrifft nur die Merkliste, welche Nummern schon auf Etiketten gedruckt wurden.</p>
        @endif
    </section>

    @if ($plan !== null)
        <section class="bc-content-section" aria-labelledby="stock-plan-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="stock-plan-heading">Ergebnis der Prüfung</h2>
                <span>{{ count($plan['numbers']) }} Etiketten, {{ $plan['sheets'] }} {{ $plan['sheets'] === 1 ? 'Bogen' : 'Bögen' }}</span>
            </div>

            @if ($plan['numbers'] === [])
                <x-ui.alert title="Nichts zu drucken">Alle Nummern in diesem Bereich sind schon vergeben@if ($plan['printed'] !== []) oder gedruckt@endif.</x-ui.alert>
            @else
                <p class="bc-section-copy">Gedruckt werden <strong class="bc-tabular">{{ $plan['numbers'][0] }}</strong> bis <strong class="bc-tabular">{{ $plan['numbers'][count($plan['numbers']) - 1] }}</strong>@if ($mode === 'luecken') ({{ $plan['total'] }} Lücken)@endif.</p>
            @endif

            @if ($plan['truncated'])
                <x-ui.alert variant="warning" title="Nur ein Teil">Es gibt {{ $plan['total'] }} freie Nummern in diesem Bereich. Dieser Druck nimmt die ersten {{ $max }}; danach prüfst du erneut (die gedruckten werden übersprungen).</x-ui.alert>
            @endif

            @if ($plan['used'] !== [])
                <x-ui.alert title="Übersprungen: schon vergeben">{{ count($plan['used']) }} Nummer(n) gehören schon einem Exemplar und bekommen kein Etikett: <span class="bc-tabular">{{ implode(', ', array_slice($plan['used'], 0, 30)) }}@if (count($plan['used']) > 30) …@endif</span></x-ui.alert>
            @endif

            @if ($plan['printed'] !== [])
                <x-ui.alert title="Übersprungen: schon gedruckt">{{ count($plan['printed']) }} Nummer(n) wurden schon einmal auf Vorrat gedruckt: <span class="bc-tabular">{{ implode(', ', array_slice($plan['printed'], 0, 30)) }}@if (count($plan['printed']) > 30) …@endif</span></x-ui.alert>
            @endif

            @if ($plan['numbers'] !== [])
                <form method="post" action="{{ route('pos.labels.stock.print') }}" target="_blank">
                    @csrf
                    <input type="hidden" name="modus" value="{{ $mode }}">
                    <input type="hidden" name="start" value="{{ $values['start'] }}">
                    <input type="hidden" name="anzahl" value="{{ $values['count'] }}">
                    <input type="hidden" name="von" value="{{ $values['from'] }}">
                    <input type="hidden" name="bis" value="{{ $values['to'] }}">
                    <input type="hidden" name="startplatz" value="{{ $values['position'] }}">
                    @if ($reprint)<input type="hidden" name="erneut" value="1">@endif
                    <x-ui.button type="submit">Etiketten ansehen und drucken</x-ui.button>
                </form>
                <p class="bc-section-copy">Mit dem Druck merkt sich das System die Nummern als „gedruckt“. Der nächste Vorratsdruck beginnt danach mit der folgenden freien Nummer.</p>
            @endif
        </section>
    @endif
</x-app-shell>
