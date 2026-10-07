<x-app-shell surface="pos" title="Treffer prüfen">
    <x-ui.page-header
        kicker="Medium erfassen"
        title="Treffer prüfen"
        :lead="'Inventarnummer '.$barcode"
    />

    <x-catalog.intake-steps :current="3" />

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    @php
        $localIsbns = collect($localEditions)->pluck('isbn')->filter()->all();
    @endphp

    @if (count($localEditions) > 0)
        <section class="bc-content-section" aria-labelledby="intake-local-heading">
            <div class="bc-section-heading"><h2 id="intake-local-heading">Bereits im Katalog</h2></div>

            <x-ui.alert variant="info" title="Ausgabe vorhanden">
                Zu dieser ISBN gibt es schon eine Ausgabe. Meist ist es richtig, ein weiteres Exemplar zu ergänzen,
                statt das Medium doppelt anzulegen.
            </x-ui.alert>

            <ul class="bc-intake-hits">
                @foreach ($localEditions as $edition)
                    <li class="bc-intake-hit">
                        <div>
                            <h3>{{ $edition->title->preferred_title }}</h3>
                            <p>
                                {{ $edition->publisher_name ?: 'Verlag unbekannt' }}
                                @if ($edition->publication_year) · {{ $edition->publication_year }} @endif
                                @if ($edition->isbn) · ISBN {{ $edition->isbn }} @endif
                                · {{ $edition->copies_count }} {{ $edition->copies_count === 1 ? 'Exemplar' : 'Exemplare' }}
                            </p>
                        </div>
                        <form method="post" action="{{ route('pos.catalog.intake.choose') }}">
                            @csrf
                            <x-ui.button type="submit" name="choice" :value="'edition:'.$edition->getKey()">Exemplar ergänzen</x-ui.button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="intake-hits-heading">
        <div class="bc-section-heading"><h2 id="intake-hits-heading">Treffer der DNB</h2></div>

        @if (! $lookupAvailable)
            <x-ui.alert variant="warning" title="DNB nicht erreichbar">
                Die Abfrage ist gerade nicht möglich. Du kannst das Medium trotzdem manuell erfassen.
            </x-ui.alert>
        @elseif (count($hits) === 0)
            <p class="bc-catalog-muted">Die DNB hat keinen passenden Datensatz geliefert.</p>
        @else
            <p class="bc-intake-note">Wähle den Datensatz, der zu deinem Exemplar passt. Die Angaben kannst du im nächsten Schritt noch ändern.</p>

            <ul class="bc-intake-hits">
                @foreach ($hits as $index => $hit)
                    <li class="bc-intake-hit">
                        <div>
                            <h3>
                                {{ $hit->title }}
                                @if ($hit->subtitle)<small>– {{ $hit->subtitle }}</small>@endif
                            </h3>
                            <p>
                                @if (count($hit->contributors) > 0)
                                    {{ collect($hit->contributors)->take(3)->pluck('name')->implode('; ') }}
                                @elseif ($hit->responsibilityStatement)
                                    {{ $hit->responsibilityStatement }}
                                @endif
                            </p>
                            <p>
                                {{ $hit->publisherName ?: 'Verlag unbekannt' }}
                                @if ($hit->publicationYear) · {{ $hit->publicationYear }} @endif
                                @if ($hit->isbn) · ISBN {{ $hit->isbn }} @endif
                                @if ($hit->editionStatement) · {{ $hit->editionStatement }} @endif
                            </p>
                            @if ($hit->isbn && in_array($hit->isbn, $localIsbns, true))
                                <x-ui.badge variant="success">Bereits im Katalog</x-ui.badge>
                            @endif
                        </div>
                        <form method="post" action="{{ route('pos.catalog.intake.choose') }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary" name="choice" :value="'hit:'.$index">Daten übernehmen</x-ui.button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="intake-manual-heading">
        <div class="bc-section-heading"><h2 id="intake-manual-heading">Nichts passt?</h2></div>

        <form method="post" action="{{ route('pos.catalog.intake.choose') }}" class="bc-intake-actions">
            @csrf
            <x-ui.button type="submit" variant="secondary" name="choice" value="manual">Manuell erfassen</x-ui.button>
            <a href="{{ route('pos.catalog.intake.identify') }}">← Andere Angaben abfragen</a>
        </form>
    </section>
</x-app-shell>
