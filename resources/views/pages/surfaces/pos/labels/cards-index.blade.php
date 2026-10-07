<x-app-shell surface="pos" title="Ausweise">
    <x-ui.page-header
        kicker="Ausleihkonten"
        title="Bibliotheksausweise"
        lead="Nicht personalisierte Ausweise mit zufälliger Nummer. Erzeugen, auf Avery Zweckform C32016 drucken oder als Liste für einen Kartendruck exportieren. Zugeordnet wird am Ausleihplatz durch Scannen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.patrons.index') }}">← Zurück zur Suche</a>
        <a href="{{ route('pos.labels.cards.designs') }}">Motive der Ausweise</a>
        <a href="{{ route('pos.help.show', ['topic' => 'ausleihe']) }}">Anleitung</a>
    </div>

    @if (session('status'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="generate-heading">
        <div class="bc-section-heading"><h2 id="generate-heading">Neue Ausweise erzeugen</h2></div>
        <p class="bc-section-copy">
            Das System vergibt zufällige, nicht aufeinanderfolgende Nummern mit Prüfziffer. Eine Nummer wird nie ein zweites Mal vergeben,
            auch nicht nach Sperrung oder Löschung des Kontos.
        </p>
        <form method="post" action="{{ route('pos.labels.cards.generate') }}" class="bc-audit-filter">
            @csrf
            <x-ui.input label="Anzahl" name="count" type="number" min="1" max="1000" value="50" required />
            <x-ui.button type="submit">Charge erzeugen</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="batches-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="batches-heading">Chargen</h2>
            <span>{{ count($batches) }}</span>
        </div>

        @if ($batches === [])
            <p class="bc-section-copy">Noch keine Ausweise erzeugt.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Charge</th>
                        <th scope="col">Stand</th>
                        <th scope="col">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($batches as $number => $batch)
                        @php
                            $free = ($batch['counts']['generated'] ?? 0) + ($batch['counts']['in_print'] ?? 0) + ($batch['counts']['available'] ?? 0);
                        @endphp
                        <tr>
                            <th scope="row">Charge {{ $number }}</th>
                            <td>
                                @foreach ($statuses as $case)
                                    @if (($batch['counts'][$case->value] ?? 0) > 0)
                                        <span class="bc-tabular">{{ $batch['counts'][$case->value] }} {{ $case->label() }}</span>@unless ($loop->last) · @endunless
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                @if ($free > 0)
                                    <a href="{{ route('pos.labels.cards.batch', ['batch' => $number]) }}">Bögen drucken ({{ $free }} frei)</a>
                                @endif
                                <form method="post" action="{{ route('pos.labels.cards.export', ['batch' => $number]) }}">
                                    @csrf
                                    <button type="submit" class="bc-intake-linkbutton">Als CSV exportieren (markiert „Im Druck“)</button>
                                </form>
                                @if (($batch['counts']['generated'] ?? 0) + ($batch['counts']['in_print'] ?? 0) > 0)
                                    <form method="post" action="{{ route('pos.labels.cards.available', ['batch' => $number]) }}">
                                        @csrf
                                        <button type="submit" class="bc-intake-linkbutton">Karten sind da: als „Verfügbar“ markieren</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="cards-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="cards-heading">Ausweise suchen</h2>
            <span>{{ $cards->count() }}{{ $cards->count() === 100 ? '+' : '' }}</span>
        </div>

        <form method="get" action="{{ route('pos.labels.cards') }}" class="bc-audit-filter" role="search">
            <x-ui.select label="Status" name="status" data-auto-submit>
                <option value="">Alle</option>
                @foreach ($statuses as $case)
                    <option value="{{ $case->value }}" @selected($status === $case->value)>{{ $case->label() }}@isset($totals[$case->value]) ({{ $totals[$case->value] }})@endisset</option>
                @endforeach
            </x-ui.select>
            <x-ui.input label="Ausweisnummer oder Person" name="q" :value="$term" />
            <x-ui.button type="submit" variant="secondary">Suchen</x-ui.button>
        </form>

        @if ($cards->isEmpty())
            <p class="bc-section-copy">Keine Ausweise gefunden.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Nummer</th>
                        <th scope="col">Charge</th>
                        <th scope="col">Status</th>
                        <th scope="col">Person</th>
                        <th scope="col"><span class="bc-visually-hidden">Sperren</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($cards as $card)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $card->number }}</th>
                            <td>{{ $card->batch ?? '—' }}</td>
                            <td>
                                {{ $card->status->label() }}@if ($card->block_reason) ({{ $card->block_reason->label() }})@endif
                            </td>
                            <td>{{ $card->patron ? $card->patron->last_name.', '.$card->patron->first_name : '—' }}</td>
                            <td>
                                @if ($card->status !== \App\Modules\Patrons\Enums\CardStatus::Blocked)
                                    <form method="post" action="{{ route('pos.labels.cards.block', ['cardId' => $card->getKey()]) }}">
                                        @csrf
                                        <input type="hidden" name="reason" value="{{ $card->status === \App\Modules\Patrons\Enums\CardStatus::Assigned ? 'lost' : 'defective' }}">
                                        <button type="submit" class="bc-intake-linkbutton" aria-label="Ausweis {{ $card->number }} sperren">Sperren</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</x-app-shell>
