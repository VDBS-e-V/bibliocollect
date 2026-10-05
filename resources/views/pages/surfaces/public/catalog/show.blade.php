<x-app-shell surface="public" :title="$title->preferred_title">
    <div class="bc-context-actions">
        <a href="{{ route('public.catalog.index') }}">← Zurück zum Katalog</a>
    </div>

    <header class="bc-public-title-header">
        <div>
            <p class="bc-eyebrow">Katalogtitel</p>
            <h1>{{ $title->preferred_title }}</h1>
            @if ($title->subtitle)
                <p class="bc-public-title-header__subtitle">{{ $title->subtitle }}</p>
            @endif
        </div>
        <div class="bc-public-title-header__stock">
            <x-ui.badge :variant="$presenter->holdingVariant($titleSummary)">
                {{ $presenter->holdingLabel($titleSummary) }}
            </x-ui.badge>
            @if ($titleSummary->shelfLocations !== [])
                <p><strong>Standorte:</strong> {{ implode(', ', $titleSummary->shelfLocations) }}</p>
            @endif
        </div>
    </header>

    <div class="bc-public-title-layout">
        <div class="bc-public-title-layout__main">
            <section class="bc-content-section" aria-labelledby="public-title-contributors-heading">
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

            <section class="bc-content-section" aria-labelledby="public-title-editions-heading">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="public-title-editions-heading">Ausgaben</h2>
                    <span>{{ $title->editions->count() }}</span>
                </div>

                @if ($title->editions->isEmpty())
                    <x-ui.alert title="Noch keine Ausgaben">Für diesen Titel sind noch keine konkreten Ausgaben erfasst.</x-ui.alert>
                @else
                    <div class="bc-public-editions">
                        @foreach ($title->editions->sortByDesc('publication_year') as $edition)
                            @php
                                $holding = $editionSummaries[(string) $edition->getKey()];
                                $topics = $editionTopics[(string) $edition->getKey()] ?? [];
                            @endphp
                            <article class="bc-public-edition">
                                <div class="bc-public-edition__heading">
                                    <div>
                                        <h3>{{ $edition->edition_statement ?: 'Ausgabe ohne Auflagenangabe' }}</h3>
                                        <p>
                                            {{ $presenter->mediaTypeLabel($edition->media_type) }}
                                            · {{ $presenter->languageLabel($edition->language_code) }}
                                        </p>
                                    </div>
                                    <x-ui.badge :variant="$presenter->holdingVariant($holding)">
                                        {{ $presenter->holdingLabel($holding) }}
                                    </x-ui.badge>
                                </div>

                                @if ($edition->responsibility_statement)
                                    <p class="bc-public-edition__statement">{{ $edition->responsibility_statement }}</p>
                                @endif

                                <dl class="bc-public-edition__details">
                                    @if ($edition->series_statement)
                                        <div><dt>Reihe</dt><dd>{{ $edition->series_statement }}</dd></div>
                                    @endif
                                    @if ($edition->edition_number)
                                        <div><dt>Auflage</dt><dd>{{ $edition->edition_number }}</dd></div>
                                    @endif
                                    @if ($edition->publication_place)
                                        <div><dt>Erscheinungsort</dt><dd>{{ $edition->publication_place }}</dd></div>
                                    @endif
                                    @if ($edition->publisher_name)
                                        <div><dt>Verlag</dt><dd>{{ $edition->publisher_name }}</dd></div>
                                    @endif
                                    @if ($edition->publication_year)
                                        <div><dt>Erscheinungsjahr</dt><dd>{{ $edition->publication_year }}</dd></div>
                                    @endif
                                    @if ($edition->isbn)
                                        <div><dt>ISBN</dt><dd class="bc-tabular">{{ $edition->isbn }}</dd></div>
                                    @endif
                                    @if (is_array($edition->alternate_identifiers) && $edition->alternate_identifiers !== [])
                                        <div><dt>Weitere IDs</dt><dd>{{ implode(', ', $edition->alternate_identifiers) }}</dd></div>
                                    @endif
                                    @if ($edition->issn)
                                        <div><dt>ISSN</dt><dd class="bc-tabular">{{ $edition->issn }}</dd></div>
                                    @endif
                                    @if ($edition->doi_handle)
                                        <div><dt>DOI / Handle</dt><dd>{{ $edition->doi_handle }}</dd></div>
                                    @endif
                                    @if ($edition->original_language_code)
                                        <div><dt>Originalsprache</dt><dd>{{ $presenter->languageLabel($edition->original_language_code) }}</dd></div>
                                    @endif
                                    @if ($edition->page_count)
                                        <div><dt>Seiten</dt><dd>{{ $edition->page_count }}</dd></div>
                                    @endif
                                    @if ($edition->physical_extent)
                                        <div><dt>Umfang</dt><dd>{{ $edition->physical_extent }}</dd></div>
                                    @endif
                                    @if ($edition->format_type)
                                        <div><dt>Format</dt><dd>{{ $edition->format_type }}</dd></div>
                                    @endif
                                    @if ($edition->target_audience)
                                        <div><dt>Zielgruppe</dt><dd>{{ $edition->target_audience }}</dd></div>
                                    @endif
                                    @if ($edition->minimum_age !== null || $edition->age_rating_label)
                                        <div>
                                            <dt>Altersangabe</dt>
                                            <dd>{{ $edition->age_rating_label ?: 'ab '.$edition->minimum_age }}</dd>
                                        </div>
                                    @endif
                                    @if ($edition->local_classification)
                                        <div><dt>Klassifikation</dt><dd>{{ $edition->local_classification }}</dd></div>
                                    @endif
                                    @if ($topics !== [])
                                        <div><dt>Themen</dt><dd>{{ implode(', ', $topics) }}</dd></div>
                                    @endif
                                    @if ($holding->shelfLocations !== [])
                                        <div><dt>Standort</dt><dd>{{ implode(', ', $holding->shelfLocations) }}</dd></div>
                                    @endif
                                </dl>

                                @if ($edition->summary)
                                    <div class="bc-public-edition__longtext">
                                        <h4>Inhalt</h4>
                                        <p>{{ $edition->summary }}</p>
                                    </div>
                                @endif

                                @if ($edition->subject_keywords || $edition->subject_keywords_system)
                                    <div class="bc-public-edition__longtext">
                                        <h4>Schlagwörter</h4>
                                        @if ($edition->subject_keywords)
                                            <p>{{ $edition->subject_keywords }}</p>
                                        @endif
                                        @if ($edition->subject_keywords_system)
                                            <p class="bc-public-metadata-source">Systematisch: {{ $edition->subject_keywords_system }}</p>
                                        @endif
                                    </div>
                                @endif

                                @if ($edition->metadata_source || $edition->source_record_id)
                                    <p class="bc-public-metadata-source">
                                        Metadatenquelle: {{ strtoupper($edition->metadata_source ?: 'unbekannt') }}
                                        @if ($edition->source_record_id)
                                            · Datensatz {{ $edition->source_record_id }}
                                        @endif
                                        @if ($edition->source_permalink)
                                            · <a href="{{ $edition->source_permalink }}" rel="nofollow noopener">Quelldatensatz</a>
                                        @endif
                                    </p>
                                @endif

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
        </div>

        <aside class="bc-public-title-layout__aside" aria-label="Hinweise zum Bestand">
            <section class="bc-side-panel">
                <h2>Metadaten</h2>
                <p>Bibliografische Angaben können aus DNB-/Normdaten und lokalen Kataloginformationen stammen. Quellen werden an der Ausgabe kenntlich gemacht, wenn sie bekannt sind.</p>
            </section>
            <section class="bc-side-panel">
                <h2>Was bedeutet „aktiv“?</h2>
                <p>Ein aktives Exemplar ist katalogseitig nutzbarer Bestand. BiblioCollect berücksichtigt an dieser Stelle noch keine laufenden Ausleihen.</p>
            </section>
            <section class="bc-side-panel bc-side-panel--quiet">
                <h2>Ausleihe</h2>
                <p>Die Bibliothek bleibt auch ohne Onlinekonto nutzbar. Vormerkungen und persönliche Ausleihdaten folgen in späteren Modulen.</p>
            </section>
        </aside>
    </div>
</x-app-shell>
