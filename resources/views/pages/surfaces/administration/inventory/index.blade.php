<x-app-shell surface="administration" title="Inventarnummern">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Alte Inventarnummern umstellen"
        lead="Neue Inventarnummern bestehen aus genau 7 Ziffern. Alte Nummern bleiben, bis du sie hier ausdrücklich umstellst. Dabei werden die ausgewählten Exemplare fortlaufend neu nummeriert."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if ($done !== [])
        <x-ui.alert variant="success" title="Neue Nummern vergeben">
            {{ count($done) }} {{ count($done) === 1 ? 'Exemplar hat' : 'Exemplare haben' }} eine neue Inventarnummer. Die Etiketten im Buch müssen ersetzt werden.
        </x-ui.alert>

        <section class="bc-content-section" aria-labelledby="done-heading">
            <div class="bc-section-heading"><h2 id="done-heading">Ergebnis</h2></div>
            <table class="bc-calendar-table">
                <thead><tr><th scope="col">Titel</th><th scope="col">Alte Nummer</th><th scope="col">Neue Nummer</th></tr></thead>
                <tbody>
                    @foreach ($done as $row)
                        <tr>
                            <th scope="row">{{ $row['title'] }}</th>
                            <td class="bc-tabular">{{ $row['old'] }}</td>
                            <td class="bc-tabular"><strong>{{ $row['new'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <form method="post" action="{{ route('pos.labels.numbers.print') }}" target="_blank">
                @csrf
                @foreach ($done as $row)
                    <input type="hidden" name="numbers[]" value="{{ $row['new'] }}">
                @endforeach
                <x-ui.button type="submit">Neue Etiketten drucken</x-ui.button>
            </form>
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="legacy-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="legacy-heading">Exemplare mit alter Nummer</h2>
            <span>{{ $legacyTotal }}</span>
        </div>

        @if ($legacyTotal === 0)
            <p class="bc-section-copy">Alle Exemplare haben eine siebenstellige Inventarnummer.</p>
        @else
            <p class="bc-section-copy">Die nächste freie Nummer ist <strong class="bc-tabular">{{ $next }}</strong>. Die alte Nummer bleibt im Protokoll erhalten.</p>

            <form method="get" action="{{ route('administration.inventory.index') }}" class="bc-audit-filter" role="search">
                <x-ui.input label="Nummer oder Titel" name="q" id="inventory-search" :value="$term" />
                <x-ui.button type="submit" variant="secondary">Suchen</x-ui.button>
            </form>

            @if ($copies === [])
                <p class="bc-section-copy">Keine passenden Exemplare gefunden.</p>
            @else
                <form method="post" action="{{ route('administration.inventory.store') }}">
                    @csrf
                    <table class="bc-calendar-table">
                        <thead>
                            <tr>
                                <th scope="col"><label><input type="checkbox" data-select-all="copies[]"> <span class="bc-visually-hidden">Alle auswählen</span></label></th>
                                <th scope="col">Alte Nummer</th>
                                <th scope="col">Titel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($copies as $copy)
                                <tr>
                                    <td><input type="checkbox" name="copies[]" value="{{ $copy->getKey() }}" aria-label="{{ $copy->barcode }} auswählen"></td>
                                    <th scope="row" class="bc-tabular">{{ $copy->barcode }}</th>
                                    <td>{{ $copy->edition->title->preferred_title }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if ($matching > $limit)
                        <p class="bc-section-copy">Es werden die ersten {{ $limit }} von {{ $matching }} gezeigt. Nach dem Umstellen erscheinen die nächsten.</p>
                    @endif
                    <x-ui.button type="submit" data-confirm="Die ausgewählten Exemplare bekommen neue Inventarnummern. Die Etiketten im Buch müssen danach ersetzt werden. Fortfahren?">Neue Nummern vergeben</x-ui.button>
                </form>
            @endif
        @endif
    </section>
</x-app-shell>
