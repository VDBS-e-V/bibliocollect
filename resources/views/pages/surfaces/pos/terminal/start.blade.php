<x-app-shell surface="pos" title="Ausleihe">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Ausleihe"
        lead="Eine Person scannen, um auszuleihen oder zu verlängern, oder Rückgaben scannen. Gebucht wird erst nach dem Bestätigen."
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

    <section class="bc-work-panel bc-terminal__start" aria-labelledby="terminal-start-heading">
        <div class="bc-section-heading"><h2 id="terminal-start-heading">Scannen</h2></div>
        <form method="post" action="{{ route('pos.terminal.start') }}" class="bc-pos-scan">
            @csrf
            <x-ui.input
                label="Ausweis oder Exemplar-Barcode scannen, oder Namen eingeben"
                name="code"
                :value="$term"
                hint="Ausweis (Bibliotheksnummer): weiter zur Person. Barcode eines Exemplars: Rückgabe. Name: Personensuche."
                autocomplete="off"
                autofocus
            />
            <x-ui.button type="submit">Weiter</x-ui.button>
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
    </section>

    @if ($items !== [])
        <section class="bc-work-panel" aria-labelledby="terminal-returns-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="terminal-returns-heading">Gescannte Rückgaben</h2>
                <span>{{ count($items) }}</span>
            </div>
            <table class="bc-calendar-table">
                <thead>
                    <tr><th scope="col">Medium</th><th scope="col"><span class="bc-visually-hidden">Entfernen</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($items as $index => $item)
                        <tr>
                            <th scope="row">{{ $item['title'] }} <small class="bc-public-metadata-source bc-tabular">{{ $item['barcode'] }}</small></th>
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

            <div class="bc-terminal__actions">
                <form method="post" action="{{ route('pos.terminal.confirm') }}">
                    @csrf
                    <x-ui.button type="submit">Rückgaben bestätigen</x-ui.button>
                </form>
                <form method="post" action="{{ route('pos.terminal.discard') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Verwerfen</x-ui.button>
                </form>
            </div>
            <p class="bc-section-copy">Wird jetzt eine Person gescannt, gehen diese Rückgaben in ihren Vorgang über (nur wenn sie ihr gehören).</p>
        </section>
    @endif
</x-app-shell>
