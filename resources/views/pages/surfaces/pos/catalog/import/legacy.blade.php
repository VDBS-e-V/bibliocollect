<x-app-shell surface="pos" title="Altbestand übernehmen">
    <x-ui.page-header
        kicker="Bestand"
        title="Altbestand übernehmen"
        lead="Den Katalog aus dem alten BiblioCollect hier hochladen, prüfen und übernehmen. Das geht ohne Konsole und ist für den Start gedacht."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.import.create') }}">Neue Medien per CSV importieren</a>
    </div>

    @if (session('legacy_report'))
        <x-ui.alert variant="success" title="Altbestand übernommen">
            @foreach (session('legacy_report') as $key => $value)
                @if ($value > 0){{ str_replace('_', ' ', $key) }}: {{ $value }}@if (! $loop->last) · @endif @endif
            @endforeach
        </x-ui.alert>
        @foreach ((array) session('legacy_warnings', []) as $warning)
            <x-ui.alert variant="warning" title="Hinweis">{{ $warning }}</x-ui.alert>
        @endforeach
    @endif

    @if (session('legacy_wishes'))
        <x-ui.alert variant="success" title="Buchwünsche">{{ session('legacy_wishes') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Nicht möglich">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if ($report)
        <section class="bc-content-section" aria-labelledby="legacy-check-heading">
            <div class="bc-section-heading"><h2 id="legacy-check-heading">Ergebnis der Prüfung</h2></div>
            <p class="bc-section-copy">Noch ist nichts übernommen. So viele Datensätze würden angelegt oder wiederverwendet:</p>
            <ul class="bc-section-copy">
                @foreach ($report->summary as $key => $value)
                    <li>{{ str_replace('_', ' ', $key) }}: <strong>{{ $value }}</strong></li>
                @endforeach
            </ul>
            @foreach ($report->warnings as $warning)
                <x-ui.alert variant="warning" title="Warnung">{{ $warning }}</x-ui.alert>
            @endforeach
            @foreach ($report->conflicts as $conflict)
                <x-ui.alert variant="error" title="Konflikt">{{ $conflict }}</x-ui.alert>
            @endforeach

            @if (! $report->hasConflicts())
                <form method="post" action="{{ route('pos.catalog.legacy.commit', ['token' => $token]) }}" class="bc-calendar-form">
                    @csrf
                    <label><input type="checkbox" name="confirm" value="1"> Ja, den Altbestand jetzt übernehmen (das kann eine Minute dauern).</label>
                    <x-ui.button type="submit">Altbestand übernehmen</x-ui.button>
                </form>
            @endif
        </section>
    @else
        <section class="bc-content-section" aria-labelledby="legacy-upload-heading">
            <div class="bc-section-heading"><h2 id="legacy-upload-heading">1. Dateien hochladen und prüfen</h2></div>
            <p class="bc-section-copy">In phpMyAdmin des alten Systems die Tabellen als <strong>JSON</strong> exportieren (Export → Format JSON, ohne Spalten zu ändern). Benötigt wird <code>mediaList</code>; <code>mediaTopicList</code> und <code>mediaSignatures</code> bringen Themenbereiche und Signaturen mit. Beim Prüfen wird noch nichts geschrieben.</p>
            <form method="post" action="{{ route('pos.catalog.legacy.analyze') }}" enctype="multipart/form-data" class="bc-calendar-form">
                @csrf
                <x-ui.input label="mediaList (JSON, Pflicht)" name="media" type="file" accept=".json,.txt" />
                <x-ui.input label="mediaTopicList (JSON, optional)" name="topics" type="file" accept=".json,.txt" />
                <x-ui.input label="mediaSignatures (JSON, optional)" name="signatures" type="file" accept=".json,.txt" />
                <x-ui.button type="submit">Prüfen</x-ui.button>
            </form>
        </section>

        <section class="bc-content-section" aria-labelledby="legacy-wishes-heading">
            <div class="bc-section-heading"><h2 id="legacy-wishes-heading">Buchwünsche aus dem Altsystem</h2></div>
            <p class="bc-section-copy">Die Tabelle <code>bookWishes</code> als JSON hochladen. Die Wünsche kommen als neue, offene Wünsche ohne Person in die Buchwunsch-Liste. Doppelte (gleiche ISBN) werden übersprungen.</p>
            <form method="post" action="{{ route('pos.catalog.legacy.wishes') }}" enctype="multipart/form-data" class="bc-calendar-form">
                @csrf
                <x-ui.input label="bookWishes (JSON)" name="wishes" type="file" accept=".json,.txt" />
                <x-ui.button type="submit" variant="secondary">Buchwünsche übernehmen</x-ui.button>
            </form>
        </section>
    @endif
</x-app-shell>
