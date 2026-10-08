@php
    $editions = $title->editions->sortByDesc('publication_year')->values();
    $multipleEditions = $editions->count() > 1;

    $editionLabel = static fn ($edition, int $index): string => $edition->edition_statement
        ?: ($edition->publication_year ? 'Ausgabe '.$edition->publication_year : 'Ausgabe '.($index + 1));

    $summaries = $editions
        ->filter(static fn ($edition) => filled($edition->summary) && ! \App\Modules\Catalog\Quality\QualityText::isPlaceholderSummary($edition->summary))
        ->unique(static fn ($edition) => trim((string) $edition->summary))
        ->values();

    $keywordEditions = $editions->filter(static fn ($edition) => $edition->subject_keywords || $edition->subject_keywords_system);
    $sourceEditions = $editions->filter(static fn ($edition) => $edition->metadata_source || $edition->source_record_id);

    $mediaTypes = $editions->pluck('media_type')->filter()->unique()->map(fn ($type) => $presenter->mediaTypeLabel($type))->values();
    $languages = $editions->pluck('language_code')->filter()->unique()->map(fn ($code) => $presenter->languageLabel($code))->values();
    $years = $editions->pluck('publication_year')->filter()->unique()->sort()->values();
    $ages = $editions
        ->map(static fn ($edition) => $edition->age_rating_label ?: ($edition->minimum_age !== null ? 'ab '.$edition->minimum_age : null))
        ->filter()->unique()->values();

    $sections = collect([
        ['id' => 'zusammenfassung', 'label' => 'Zusammenfassung', 'show' => $summaries->isNotEmpty()],
        ['id' => 'standorte', 'label' => 'Standorte und Exemplare', 'show' => true],
        ['id' => 'details', 'label' => 'Details', 'show' => $editions->isNotEmpty()],
        ['id' => 'verantwortliche', 'label' => 'Verantwortliche', 'show' => true],
        ['id' => 'schlagwoerter', 'label' => 'Schlagwörter', 'show' => $keywordEditions->isNotEmpty()],
        ['id' => 'quelle', 'label' => 'Metadatenquelle', 'show' => $sourceEditions->isNotEmpty()],
    ])->filter(static fn (array $section): bool => $section['show']);
@endphp

<x-app-shell surface="public" :title="$title->preferred_title">
    <div class="bc-context-actions">
        <a href="{{ route('public.catalog.index') }}">← Zurück zum Katalog</a>
    </div>

    <header class="bc-public-title-header" id="top">
        <div class="bc-public-title-header__cover">
            <span class="bc-public-title-cover-frame">
                <img src="{{ $coverUrl }}" alt="" class="bc-public-title-cover">
            </span>
        </div>

        <div class="bc-public-title-header__main">
            <p class="bc-eyebrow">{{ $mediaTypes->isNotEmpty() ? $mediaTypes->join(' · ') : 'Katalogtitel' }}</p>
            <h1>{{ $title->preferred_title }}</h1>
            @if ($title->subtitle)
                <p class="bc-public-title-header__subtitle">{{ $title->subtitle }}</p>
            @endif

            @if ($title->contributions->isNotEmpty())
                <p class="bc-public-title-header__contributors">
                    {{ $title->contributions->pluck('contributor.display_name')->filter()->join(', ') }}
                </p>
            @endif

            @if ($editions->isNotEmpty())
                <p class="bc-public-title-header__imprint">
                    {{ $editions->pluck('publisher_name')->filter()->unique()->join(', ') }}
                    @if ($years->isNotEmpty())
                        {{ $editions->pluck('publisher_name')->filter()->isNotEmpty() ? '·' : '' }}
                        {{ $years->count() > 1 ? $years->first().'–'.$years->last() : $years->first() }}
                    @endif
                </p>
            @endif
        </div>

        <div class="bc-public-title-header__stock">
            <x-ui.badge :variant="$presenter->availabilityVariant($titleSummary, $titleAvailability)">
                {{ $presenter->availabilityLabel($titleSummary, $titleAvailability) }}
            </x-ui.badge>
            @if ($presenter->availabilityHint($titleAvailability))
                <p>{{ $presenter->availabilityHint($titleAvailability) }}</p>
            @endif
            @if ($titleSummary->shelfLocations !== [])
                <p><strong>Standorte:</strong> {{ implode(', ', $titleSummary->shelfLocations) }}</p>
            @endif
        </div>
    </header>

    <div class="bc-public-title-layout">
        <div class="bc-public-title-layout__main">
            @if ($summaries->isNotEmpty())
                <section class="bc-content-section" id="zusammenfassung" aria-labelledby="public-title-summary-heading">
                    <div class="bc-section-heading">
                        <h2 id="public-title-summary-heading">Zusammenfassung</h2>
                    </div>
                    @foreach ($summaries as $edition)
                        <div class="bc-public-summary">
                            @if ($summaries->count() > 1)
                                <h3>{{ $editionLabel($edition, $editions->search($edition)) }}</h3>
                            @endif
                            <p>{{ $edition->summary }}</p>
                        </div>
                    @endforeach
                </section>
            @endif

            <section class="bc-content-section" id="standorte" aria-labelledby="public-title-copies-heading">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="public-title-copies-heading">Standorte und Exemplare</h2>
                    <span>{{ $titleAvailability->activeCopies }}</span>
                </div>

                @if ($reserveState === 'ready')
                    <form method="post" action="{{ route('portal.reservations.store') }}" class="bc-public-reserve">
                        @csrf
                        <input type="hidden" name="title_id" value="{{ $title->getKey() }}">
                        @if ($titleAvailability->isAvailable())
                            <x-ui.button type="submit">Zurücklegen lassen</x-ui.button>
                            <span>Ein Exemplar ist da. Es wird für dich zurückgelegt, du holst es in der Abholfrist in der Bibliothek ab.</span>
                        @else
                            <x-ui.button type="submit">Titel vormerken</x-ui.button>
                            <span>Alle Exemplare sind ausgeliehen. Du wirst in der Warteschlange eingereiht.</span>
                        @endif
                    </form>
                @elseif ($reserveState === 'reserved')
                    <p class="bc-public-reserve"><x-ui.badge variant="success">Vorgemerkt</x-ui.badge> <a href="{{ route('portal.home') }}#vormerkungen">Zu meinen Vormerkungen</a></p>
                @elseif ($reserveState === 'full')
                    <p class="bc-public-reserve">Alle Exemplare sind ausgeliehen und es gibt schon so viele Vormerkungen wie Exemplare. Versuche es später noch einmal.</p>
                @elseif ($reserveState === 'limit')
                    <p class="bc-public-reserve">Du hast schon so viele Vormerkungen, wie erlaubt sind. <a href="{{ route('portal.home') }}#vormerkungen">Zu meinen Vormerkungen</a></p>
                @elseif ($reserveState === 'off')
                    <p class="bc-public-reserve">Vormerken ist zurzeit nicht möglich.</p>
                @elseif ($reserveState === 'login')
                    <p class="bc-public-reserve"><a href="{{ route('login') }}">Melde dich an</a>, um den Titel vorzumerken oder zurücklegen zu lassen.</p>
                @endif

                @if ($editions->isEmpty())
                    <x-ui.alert title="Noch keine Ausgaben">Für diesen Titel sind noch keine konkreten Ausgaben erfasst.</x-ui.alert>
                @else
                    <div class="bc-public-editions">
                        @foreach ($editions as $index => $edition)
                            @php
                                $holding = $editionSummaries[(string) $edition->getKey()];
                                $editionAvailability = $editionAvailabilities[(string) $edition->getKey()];
                                $activeCopies = $edition->copies
                                    ->filter(static fn ($copy) => $copy->status->value === 'active')
                                    ->sortBy(static fn ($copy) => [$copy->shelf_location ?? '', $copy->barcode])
                                    ->values();
                            @endphp
                            <article class="bc-public-edition">
                                <div class="bc-public-edition__heading">
                                    <div>
                                        <h3>{{ $editionLabel($edition, $index) }}</h3>
                                        <p>
                                            {{ $presenter->mediaTypeLabel($edition->media_type) }}
                                            · {{ $presenter->languageLabel($edition->language_code) }}
                                            @if ($edition->publisher_name || $edition->publication_year)
                                                · {{ trim($edition->publisher_name.' '.$edition->publication_year) }}
                                            @endif
                                        </p>
                                    </div>
                                    <x-ui.badge :variant="$presenter->availabilityVariant($holding, $editionAvailability)">
                                        {{ $presenter->availabilityLabel($holding, $editionAvailability) }}
                                    </x-ui.badge>
                                </div>

                                <section class="bc-public-copies" aria-label="Exemplare dieser Ausgabe">
                                    @if ($editionAvailability->hasActiveCopies())
                                        <p class="bc-public-copies__summary">{{ $presenter->copySummary($editionAvailability) }}</p>
                                    @endif
                                    @if ($activeCopies->isEmpty())
                                        <p class="bc-section-copy">Für diese Ausgabe gibt es derzeit kein ausleihbares Exemplar.</p>
                                    @else
                                        <table class="bc-public-copies__table">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Exemplar</th>
                                                    <th scope="col">Standort</th>
                                                    <th scope="col">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($activeCopies as $copy)
                                                    @php
                                                        $state = $copyStates[(string) $copy->getKey()];
                                                    @endphp
                                                    <tr>
                                                        <th scope="row">{{ $loop->iteration }}</th>
                                                        <td>{{ $copy->shelf_location ?: '—' }}</td>
                                                        <td>
                                                            <x-ui.badge :variant="$presenter->copyStateVariant($state)">{{ $presenter->copyStateLabel($state) }}</x-ui.badge>
                                                            @if (\App\Modules\Catalog\Enums\CopyAccess::noteFor($copy->access_status))
                                                                <small class="bc-public-metadata-source">{{ \App\Modules\Catalog\Enums\CopyAccess::noteFor($copy->access_status) }}</small>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                        @if ($titleAvailability->waitingReservations > 0)
                                            <p class="bc-public-copies__note">
                                                {{ $titleAvailability->waitingReservations === 1 ? '1 Vormerkung' : $titleAvailability->waitingReservations.' Vormerkungen' }} für diesen Titel.
                                                Vormerken ist in der Bibliothek möglich, solange kein Exemplar verfügbar ist.
                                            </p>
                                        @elseif (! $editionAvailability->isAvailable() && $editionAvailability->hasActiveCopies())
                                            <p class="bc-public-copies__note">Vormerken ist in der Bibliothek möglich, solange kein Exemplar verfügbar ist.</p>
                                        @endif
                                    @endif
                                </section>

                                @if ($holding->damagedCopies > 0 || $holding->lostCopies > 0 || $holding->withdrawnCopies > 0)
                                    <p class="bc-public-edition__secondary-stock">
                                        Weitere Katalogzustände:
                                        @if ($holding->damagedCopies > 0) {{ $holding->damagedCopies }} beschädigt @endif
                                        @if ($holding->lostCopies > 0) · {{ $holding->lostCopies }} verloren @endif
                                        @if ($holding->withdrawnCopies > 0) · {{ $holding->withdrawnCopies }} ausgesondert @endif
                                    </p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            @if ($editions->isNotEmpty())
                <section class="bc-content-section" id="details" aria-labelledby="public-title-details-heading">
                    <div class="bc-section-heading bc-section-heading--with-meta">
                        <h2 id="public-title-details-heading">Details</h2>
                        <span>{{ $editions->count() }} {{ $editions->count() === 1 ? 'Ausgabe' : 'Ausgaben' }}</span>
                    </div>

                    @foreach ($editions as $index => $edition)
                        @php
                            $topics = $editionTopics[(string) $edition->getKey()] ?? [];
                        @endphp
                        <div class="bc-public-detail-block">
                            @if ($multipleEditions)
                                <h3>{{ $editionLabel($edition, $index) }}</h3>
                            @endif
                            <dl class="bc-public-detail-table">
                                <div><dt>Titel</dt><dd>{{ $title->preferred_title }}</dd></div>
                                @if ($title->subtitle)
                                    <div><dt>Untertitel</dt><dd>{{ $title->subtitle }}</dd></div>
                                @endif
                                @if ($edition->responsibility_statement)
                                    <div><dt>Verantwortlichkeitsangabe</dt><dd>{{ $edition->responsibility_statement }}</dd></div>
                                @endif
                                <div><dt>Medientyp</dt><dd>{{ $presenter->mediaTypeLabel($edition->media_type) }}</dd></div>
                                <div><dt>Sprache</dt><dd>{{ $presenter->languageLabel($edition->language_code) }}</dd></div>
                                @if ($edition->original_language_code)
                                    <div><dt>Originalsprache</dt><dd>{{ $presenter->languageLabel($edition->original_language_code) }}</dd></div>
                                @endif
                                @if ($edition->edition_statement || $edition->edition_number)
                                    <div><dt>Ausgabe</dt><dd>{{ collect([$edition->edition_statement, $edition->edition_number])->filter()->unique()->join(' · ') }}</dd></div>
                                @endif
                                @if ($edition->publisher_name)
                                    <div><dt>Verlag</dt><dd>{{ $edition->publisher_name }}</dd></div>
                                @endif
                                @if ($edition->publication_place)
                                    <div><dt>Erscheinungsort</dt><dd>{{ $edition->publication_place }}</dd></div>
                                @endif
                                @if ($edition->publication_year)
                                    <div><dt>Erschienen</dt><dd>{{ $edition->publication_year }}</dd></div>
                                @endif
                                @if ($edition->series_statement)
                                    <div><dt>Reihe</dt><dd>{{ $edition->series_statement }}</dd></div>
                                @endif
                                @if ($edition->physical_extent || $edition->page_count)
                                    <div><dt>Umfang</dt><dd>{{ $edition->physical_extent ?: $edition->page_count.' Seiten' }}</dd></div>
                                @endif
                                @if ($edition->format_type)
                                    <div><dt>Format</dt><dd>{{ $edition->format_type }}</dd></div>
                                @endif
                                @if ($edition->target_audience)
                                    <div><dt>Zielgruppe</dt><dd>{{ $edition->target_audience }}</dd></div>
                                @endif
                                @if ($edition->minimum_age !== null || $edition->age_rating_label)
                                    <div><dt>Altersangabe</dt><dd>{{ $edition->age_rating_label ?: 'ab '.$edition->minimum_age }}</dd></div>
                                @endif
                                @if ($edition->isbn)
                                    <div><dt>ISBN</dt><dd class="bc-tabular">{{ $edition->isbn }}</dd></div>
                                @endif
                                @if ($edition->issn)
                                    <div><dt>ISSN</dt><dd class="bc-tabular">{{ $edition->issn }}</dd></div>
                                @endif
                                @if ($edition->doi_handle)
                                    <div><dt>DOI / Handle</dt><dd>{{ $edition->doi_handle }}</dd></div>
                                @endif
                                @if (is_array($edition->alternate_identifiers) && $edition->alternate_identifiers !== [])
                                    <div><dt>Weitere Kennungen</dt><dd>{{ implode(', ', $edition->alternate_identifiers) }}</dd></div>
                                @endif
                                @if ($edition->local_classification)
                                    <div><dt>Themenbereich</dt><dd>{{ $edition->local_classification }}</dd></div>
                                @endif
                                @if ($topics !== [])
                                    <div><dt>Themen</dt><dd>{{ implode(', ', $topics) }}</dd></div>
                                @endif
                            </dl>
                        </div>
                    @endforeach
                </section>
            @endif

            <section class="bc-content-section" id="verantwortliche" aria-labelledby="public-title-contributors-heading">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="public-title-contributors-heading">Verantwortliche</h2>
                    <span>{{ $title->contributions->count() }}</span>
                </div>

                @if ($title->contributions->isEmpty())
                    <p class="bc-section-copy">Zu diesem Titel sind noch keine Verantwortlichen erfasst.</p>
                @else
                    <dl class="bc-public-contributor-list">
                        @foreach ($title->contributions as $contribution)
                            <div>
                                <dt>{{ $presenter->roleLabel($contribution->role_key) }}</dt>
                                <dd>
                                    {{ $contribution->contributor->display_name }}
                                    @if ($contribution->contributor->gnd_id)
                                        <small class="bc-public-metadata-source">GND {{ $contribution->contributor->gnd_id }}</small>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            @if ($keywordEditions->isNotEmpty())
                <section class="bc-content-section" id="schlagwoerter" aria-labelledby="public-title-keywords-heading">
                    <div class="bc-section-heading">
                        <h2 id="public-title-keywords-heading">Schlagwörter</h2>
                    </div>
                    @foreach ($keywordEditions as $edition)
                        <div class="bc-public-summary">
                            @if ($keywordEditions->count() > 1)
                                <h3>{{ $editionLabel($edition, $editions->search($edition)) }}</h3>
                            @endif
                            @if ($edition->subject_keywords)
                                <p>{{ $edition->subject_keywords }}</p>
                            @endif
                            @if ($edition->subject_keywords_system)
                                <p class="bc-public-metadata-source">Systematisch: {{ $edition->subject_keywords_system }}</p>
                            @endif
                        </div>
                    @endforeach
                </section>
            @endif

            @if ($sourceEditions->isNotEmpty())
                <section class="bc-content-section" id="quelle" aria-labelledby="public-title-source-heading">
                    <div class="bc-section-heading">
                        <h2 id="public-title-source-heading">Metadatenquelle</h2>
                    </div>
                    @foreach ($sourceEditions as $edition)
                        <p class="bc-public-metadata-source">
                            @if ($multipleEditions)
                                {{ $editionLabel($edition, $editions->search($edition)) }}:
                            @endif
                            Metadatenquelle: {{ strtoupper($edition->metadata_source ?: 'unbekannt') }}
                            @if ($edition->source_record_id)
                                · Datensatz {{ $edition->source_record_id }}
                            @endif
                            @if ($edition->source_permalink)
                                · <a href="{{ $edition->source_permalink }}" rel="nofollow noopener">Quelldatensatz</a>
                            @endif
                        </p>
                    @endforeach
                </section>
            @endif
        </div>

        <aside class="bc-public-title-layout__aside" aria-label="Übersicht und Navigation">
            <nav class="bc-title-nav" aria-labelledby="title-nav-heading">
                <h2 id="title-nav-heading">Auf dieser Seite</h2>
                <ul>
                    <li><a href="#top">Nach oben</a></li>
                    @foreach ($sections as $section)
                        <li><a href="#{{ $section['id'] }}">{{ $section['label'] }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <section class="bc-title-glance" aria-labelledby="title-glance-heading">
                <h2 id="title-glance-heading">Auf einen Blick</h2>
                <dl>
                    <div>
                        <dt>Verfügbarkeit</dt>
                        <dd>
                            <x-ui.badge :variant="$presenter->availabilityVariant($titleSummary, $titleAvailability)">
                                {{ $presenter->availabilityLabel($titleSummary, $titleAvailability) }}
                            </x-ui.badge>
                            @if ($presenter->availabilityHint($titleAvailability))
                                <small>{{ $presenter->availabilityHint($titleAvailability) }}</small>
                            @endif
                        </dd>
                    </div>
                    @if ($titleSummary->shelfLocations !== [])
                        <div><dt>Standort</dt><dd>{{ implode(', ', $titleSummary->shelfLocations) }}</dd></div>
                    @endif
                    @if ($mediaTypes->isNotEmpty())
                        <div><dt>Medientyp</dt><dd>{{ $mediaTypes->join(', ') }}</dd></div>
                    @endif
                    @if ($languages->isNotEmpty())
                        <div><dt>Sprache</dt><dd>{{ $languages->join(', ') }}</dd></div>
                    @endif
                    @if ($years->isNotEmpty())
                        <div><dt>Erschienen</dt><dd>{{ $years->join(', ') }}</dd></div>
                    @endif
                    @if ($ages->isNotEmpty())
                        <div><dt>Altersangabe</dt><dd>{{ $ages->join(', ') }}</dd></div>
                    @endif
                    @if ($title->contributions->isNotEmpty())
                        <div><dt>Verantwortlich</dt><dd>{{ $title->contributions->pluck('contributor.display_name')->filter()->unique()->join(', ') }}</dd></div>
                    @endif
                </dl>
            </section>

            <details class="bc-title-notes">
                <summary>Hinweise zu den Angaben</summary>
                <p><strong>Metadaten:</strong> Bibliografische Angaben können aus DNB-/Normdaten und lokalen Kataloginformationen stammen. Die Quelle steht unter „Metadatenquelle“, wenn sie bekannt ist.</p>
                <p><strong>Verfügbarkeit:</strong> „Verfügbar“ heißt: Mindestens ein nutzbares Exemplar ist gerade nicht ausgeliehen. Beschädigte, verlorene oder ausgesonderte Exemplare zählen nicht mit.</p>
                <p><strong>Cover:</strong> Der Katalog verwendet nur lokal gespeicherte Cover. Fehlt ein Bild, erscheint ein neutraler Platzhalter.</p>
            </details>
        </aside>
    </div>
</x-app-shell>
