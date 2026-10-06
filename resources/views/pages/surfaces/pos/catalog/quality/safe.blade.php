@php
    $kindLabels = ['fill' => 'ergänzt', 'fix' => 'korrigiert', 'local' => 'bereinigt'];
@endphp

<x-app-shell surface="pos" title="Eindeutige Vorschläge">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Eindeutige Vorschläge gesammelt übernehmen"
        lead="Fälle, bei denen der Vorschlag ohne Warnung ist und nur leere Felder füllt, belegte Fehler korrigiert oder lokal bereinigt. Noch ist nichts geändert."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.quality.index') }}">← Zurück zur Katalogqualität</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="safe-summary-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="safe-summary-heading">Stand</h2>
            <span>{{ count($entries) }} eindeutige Fälle</span>
        </div>
        <p class="bc-section-copy">
            {{ $counts['open'] }} offene Fälle mit Mangel, für {{ $counts['fetched'] }} liegt eine Abfrage der DNB vor, {{ $counts['safe'] }} davon sind eindeutig.
            @if ($counts['fetched'] < $counts['open'])
                Für die übrigen holt <code>php artisan catalog:quality:propose --limit=200</code> die Vorschläge vorab.
            @endif
            Person ergänzen, Namen umbenennen und abweichende Werte sowie alle Fälle mit Warnung bleiben der Einzelprüfung vorbehalten.
        </p>

        @if ($entries === [])
            <x-ui.alert title="Nichts eindeutig">Es gibt aktuell keine eindeutigen Vorschläge.</x-ui.alert>
        @else
            <form method="post" action="{{ route('pos.catalog.quality.safe.apply') }}">
                @csrf
                <label class="bc-public-catalog-filter__check" for="confirm">
                    <input id="confirm" name="confirm" type="checkbox" value="1">
                    <span><strong>Ich habe die Änderungen unten geprüft und möchte sie für {{ count($entries) }} Fälle übernehmen.</strong></span>
                </label>
                <x-ui.button type="submit">Alle eindeutigen Vorschläge übernehmen</x-ui.button>
            </form>
        @endif
    </section>

    @if ($entries !== [])
        <section class="bc-content-section" aria-labelledby="safe-list-heading">
            <div class="bc-section-heading"><h2 id="safe-list-heading">Änderungen</h2></div>
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Titel</th>
                        <th scope="col">Feld</th>
                        <th scope="col">Art</th>
                        <th scope="col">Bisher</th>
                        <th scope="col">Neu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        @foreach ($entry['changes'] as $change)
                            <tr>
                                @if ($loop->first)
                                    <th scope="row" rowspan="{{ count($entry['changes']) }}">
                                        <a href="{{ route('pos.catalog.quality.show', ['reviewId' => $entry['review']->getKey()]) }}">{{ $entry['review']->edition->title->preferred_title }}</a>
                                    </th>
                                @endif
                                <td>{{ $change->label }}</td>
                                <td><x-ui.badge>{{ $kindLabels[$change->kind] ?? $change->kind }}</x-ui.badge></td>
                                <td>{{ \Illuminate\Support\Str::limit((string) $change->current, 80) ?: '—' }}</td>
                                <td><strong>{{ \Illuminate\Support\Str::limit((string) $change->proposed, 80) ?: '—' }}</strong></td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</x-app-shell>
