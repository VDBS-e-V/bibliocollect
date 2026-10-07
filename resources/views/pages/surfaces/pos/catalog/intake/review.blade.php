@php
    use App\Surfaces\Pos\Support\CatalogIntakeVocabulary;

    $existing = $context['existing'];
    $hasIsbn = $existing ? filled($existing->isbn) : filled($details?->isbn);
@endphp

<x-app-shell surface="pos" title="Prüfen und speichern">
    <x-ui.page-header
        kicker="Medium erfassen"
        title="Prüfen & speichern"
        lead="Kontrolliere die Angaben. Erst mit dem Speichern wird das Medium in den Katalog übernommen."
    />

    <x-catalog.intake-steps :current="6" :skip-details="$detailsSkipped" />

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="intake-review-medium-heading">
        <div class="bc-section-heading">
            <h2 id="intake-review-medium-heading">{{ $existing ? 'Vorhandene Ausgabe' : 'Medium' }}</h2>
        </div>

        <dl class="bc-intake-summary">
            <dt>Titel</dt>
            <dd>
                <strong>{{ $context['title'] }}</strong>
                @if ($context['subtitle']) – {{ $context['subtitle'] }} @endif
            </dd>

            @if ($existing)
                <dt>Ausgabe</dt>
                <dd>
                    {{ $existing->publisher_name ?: 'Verlag unbekannt' }}
                    @if ($existing->publication_year) · {{ $existing->publication_year }} @endif
                    @if ($existing->isbn) · ISBN {{ $existing->isbn }} @endif
                </dd>
            @elseif ($details)
                @if (count($details->contributors) > 0)
                    <dt>Verantwortliche</dt>
                    <dd>
                        @foreach ($details->contributors as $contributor)
                            {{ $contributor['name'] }} <small>({{ CatalogIntakeVocabulary::role($contributor['role']) }})</small>@if (! $loop->last); @endif
                        @endforeach
                    </dd>
                @endif

                @if ($details->responsibilityStatement)
                    <dt>Verantwortlichkeitsangabe</dt>
                    <dd>{{ $details->responsibilityStatement }}</dd>
                @endif

                <dt>Veröffentlichung</dt>
                <dd>
                    {{ $details->publisherName ?: 'Verlag unbekannt' }}
                    @if ($details->publicationPlace) · {{ $details->publicationPlace }} @endif
                    @if ($details->publicationYear) · {{ $details->publicationYear }} @endif
                    @if ($details->editionStatement) · {{ $details->editionStatement }} @endif
                </dd>

                @if ($details->isbn)
                    <dt>ISBN</dt>
                    <dd>{{ $details->isbn }}</dd>
                @endif

                @if ($details->physicalExtent)
                    <dt>Umfang</dt>
                    <dd>{{ $details->physicalExtent }}</dd>
                @endif

                <dt>Medientyp / Sprache</dt>
                <dd>
                    {{ CatalogIntakeVocabulary::mediaType($details->mediaType) ?? 'nicht angegeben' }}
                    · {{ $details->languageCode ?? 'Sprache nicht angegeben' }}
                    @if ($details->originalLanguageCode) (Original: {{ $details->originalLanguageCode }}) @endif
                </dd>

                @if ($details->seriesStatement)
                    <dt>Reihe</dt>
                    <dd>{{ $details->seriesStatement }}</dd>
                @endif

                @if ($details->localClassification)
                    <dt>Lokale Klassifikation</dt>
                    <dd>{{ $details->localClassification }}</dd>
                @endif

                @if ($details->targetAudience)
                    <dt>Zielgruppe</dt>
                    <dd>{{ $details->targetAudience }}</dd>
                @endif

                <dt>Mindestalter für die Ausleihe</dt>
                <dd>{{ $details->minimumAge !== null ? 'ab '.$details->minimumAge.' Jahren' : 'keine Beschränkung' }}</dd>

                @if ($details->subjectKeywords)
                    <dt>Schlagwörter</dt>
                    <dd>{{ $details->subjectKeywords }}</dd>
                @endif

                @if ($details->summary)
                    <dt>Zusammenfassung</dt>
                    <dd>{{ \Illuminate\Support\Str::limit($details->summary, 400) }}</dd>
                @endif

                @if ($provenance)
                    <dt>Datenquelle</dt>
                    <dd>
                        {{ strtoupper($provenance->source) }}
                        @if ($provenance->permalink)
                            · <a href="{{ $provenance->permalink }}" rel="noopener noreferrer" target="_blank">{{ $provenance->recordId }}</a>
                        @endif
                    </dd>
                @endif
            @endif
        </dl>

        @unless ($detailsSkipped)
            <p class="bc-intake-note"><a href="{{ route('pos.catalog.intake.details') }}">Titel- und Ausgabedaten ändern</a></p>
        @endunless
    </section>

    <section class="bc-content-section" aria-labelledby="intake-review-copy-heading">
        <div class="bc-section-heading"><h2 id="intake-review-copy-heading">Exemplar</h2></div>

        <dl class="bc-intake-summary">
            <dt>Inventarnummer</dt>
            <dd><strong>{{ $barcode }}</strong></dd>
            <dt>Standort</dt>
            <dd>wird beim Einsortieren ins Regal vermerkt (Stapel „Einsortieren“)</dd>
            <dt>Zustand im Bestand</dt>
            <dd>{{ CatalogIntakeVocabulary::copyStatus($copy['status']->value) }}</dd>
        </dl>

        <p class="bc-intake-note"><a href="{{ route('pos.catalog.intake.copy') }}">Exemplardaten ändern</a></p>
    </section>

    @if (! $existing)
        <section class="bc-content-section" aria-labelledby="intake-review-cover-heading">
            <div class="bc-section-heading"><h2 id="intake-review-cover-heading">Cover</h2></div>
            <p class="bc-intake-note">
                @if ($hasIsbn && $willFetchCover)
                    Nach dem Speichern wird das Cover automatisch im Hintergrund geladen (Open Library, bei Bedarf Google Books)
                    und lokal abgelegt. Bis dahin zeigt der Katalog einen Platzhalter.
                @elseif (! $hasIsbn)
                    Ohne ISBN kann kein Cover automatisch gesucht werden. Der Katalog zeigt einen Platzhalter.
                @else
                    Der automatische Cover-Abruf ist nicht aktiviert. Der Katalog zeigt einen Platzhalter.
                @endif
            </p>
        </section>
    @endif

    <form method="post" action="{{ route('pos.catalog.intake.commit') }}" class="bc-intake-actions">
        @csrf
        <a href="{{ route('pos.catalog.intake.copy') }}">← Zurück</a>
        <x-ui.button type="submit" name="next" value="again">Speichern &amp; nächstes Medium</x-ui.button>
        <x-ui.button type="submit" name="next" value="open" variant="secondary">Speichern &amp; Titel öffnen</x-ui.button>
    </form>
</x-app-shell>
