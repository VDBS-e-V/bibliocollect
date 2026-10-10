<x-app-shell surface="pos" title="Reihen im Katalog">
    <x-ui.page-header
        kicker="Katalog"
        title="Reihen"
        lead="Aus der Reihenangabe der Ausgaben werden Reihen und Bandnummern abgeleitet. Hier siehst du, was dabei herauskommt, blendest Verlagsreihen aus und korrigierst Namen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.index') }}">← Zur Katalogpflege</a>
        <a href="{{ route('public.series.index') }}">Öffentliche Reihenübersicht</a>
    </div>

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="eval-heading">
        <h2 id="eval-heading">Auswertung</h2>
        <ul class="bc-section-copy">
            <li>{{ $totals['withStatement'] }} Ausgaben haben eine Reihenangabe, {{ $totals['series'] }} Reihen wurden daraus erkannt ({{ $totals['hidden'] }} davon ausgeblendet).</li>
            <li>{{ $totals['withVolume'] }} Ausgaben haben eine erkannte Bandnummer.</li>
            <li>{{ $totals['unassigned'] }} Ausgaben mit Reihenangabe sind keiner Reihe zugeordnet (reine Zahlen oder leer).</li>
        </ul>
        <p class="bc-section-copy">
            <strong>Hinweis:</strong> Im Altbestand stehen unter „Reihe“ oft Verlags- und Taschenbuchreihen („dtv“, „Fischer“) und keine Lesereihen. Reihen, deren Name dem Verlag entspricht, sind deshalb von Anfang an ausgeblendet; blende weitere hier aus, damit sie nicht als „Teil der Reihe“ erscheinen.
        </p>
        <form method="post" action="{{ route('pos.catalog.series.sync') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary">Zuordnung neu berechnen</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="list-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="list-heading">Reihen</h2>
            <span>{{ $series->total() }}</span>
        </div>

        <form method="get" action="{{ route('pos.catalog.series') }}" class="bc-calendar-form" role="search">
            <x-ui.input label="Name suchen" name="q" id="series-search" :value="$term" />
            <x-ui.button type="submit" variant="secondary">Suchen</x-ui.button>
        </form>

        @if ($series->isEmpty())
            <p class="bc-section-copy">Keine Reihen gefunden.</p>
        @else
            <div class="bc-table-wrap">
                <table class="bc-table">
                    <caption class="bc-visually-hidden">Erkannte Reihen mit Ausgaben und der Möglichkeit, sie öffentlich auszublenden</caption>
                    <thead><tr><th scope="col">Name</th><th scope="col">Ausgaben</th><th scope="col">Öffentlich</th><th scope="col"><span class="bc-visually-hidden">Speichern</span></th></tr></thead>
                    <tbody>
                        @foreach ($series as $item)
                            <tr>
                                <td>
                                    <form method="post" action="{{ route('pos.catalog.series.update', ['seriesId' => $item->getKey()]) }}" id="series-{{ $item->getKey() }}">
                                        @csrf
                                        <label class="bc-visually-hidden" for="name-{{ $item->getKey() }}">Name der Reihe</label>
                                        <input id="name-{{ $item->getKey() }}" name="name" value="{{ $item->name }}" maxlength="190" class="bc-field__control" required>
                                    </form>
                                </td>
                                <td>{{ $item->editions_count }}</td>
                                <td>
                                    <label class="bc-public-catalog-filter__check">
                                        <input type="checkbox" form="series-{{ $item->getKey() }}" name="is_hidden" value="1" @checked($item->is_hidden)>
                                        <span>Ausblenden</span>
                                    </label>
                                </td>
                                <td><button type="submit" form="series-{{ $item->getKey() }}" class="bc-intake-linkbutton" aria-label="Reihe {{ $item->name }} speichern">Speichern</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($series->hasPages())
                <nav class="bc-context-actions" aria-label="Seiten der Reihenliste">
                    @if ($series->onFirstPage())
                        <span>Seite {{ $series->currentPage() }} von {{ $series->lastPage() }}</span>
                    @else
                        <a href="{{ $series->previousPageUrl() }}">← Vorherige Seite</a>
                        <span>Seite {{ $series->currentPage() }} von {{ $series->lastPage() }}</span>
                    @endif
                    @if ($series->hasMorePages())
                        <a href="{{ $series->nextPageUrl() }}">Nächste Seite →</a>
                    @endif
                </nav>
            @endif
        @endif
    </section>

    @if ($unassignedSamples->isNotEmpty())
        <section class="bc-content-section" aria-labelledby="unassigned-heading">
            <h2 id="unassigned-heading">Nicht zugeordnete Reihenangaben</h2>
            <ul class="bc-section-copy">
                @foreach ($unassignedSamples as $sample)
                    <li>„{{ $sample->series_statement }}“ ({{ $sample->c }}×)</li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-shell>
