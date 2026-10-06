@php
    $statusLabels = [
        'open' => 'Offen',
        'dismissed' => 'Kein Handlungsbedarf',
        'resolved' => 'Erledigt',
    ];
    $statusCounts = [
        'open' => $counts['open_defects'],
        'dismissed' => $counts['dismissed'],
        'resolved' => $counts['resolved'],
    ];
    $chipLink = static fn (array $overrides): string => route('pos.catalog.quality.index', array_filter(
        array_merge(['status' => $filters['status'], 'problem' => $filters['problem'], 'q' => $filters['q']], $overrides),
        static fn ($value): bool => $value !== null && $value !== '' && $value !== 'open',
    ));
@endphp

<x-app-shell surface="pos" title="Katalogqualität">
    <x-ui.page-header
        kicker="Katalogpflege"
        title="Katalogqualität"
        lead="Ausgaben mit unvollständigen oder fehlerhaften Metadaten prüfen. Vorschläge werden erst nach deiner Bestätigung übernommen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.index') }}">← Zurück zur Katalogpflege</a>
        <form method="post" action="{{ route('pos.catalog.quality.scan') }}" class="bc-quality-inline-form">
            @csrf
            <button type="submit" class="bc-intake-linkbutton">Bestand neu prüfen</button>
        </form>
    </div>

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <nav class="bc-quality-tabs" aria-label="Status der Fälle">
        @foreach ($statusLabels as $status => $label)
            <a
                href="{{ route('pos.catalog.quality.index', $status === 'open' ? [] : ['status' => $status]) }}"
                class="bc-quality-tab {{ $filters['status'] === $status ? 'bc-quality-tab--active' : '' }}"
                @if ($filters['status'] === $status) aria-current="page" @endif
            >
                {{ $label }} <span class="bc-quality-count">{{ $statusCounts[$status] }}</span>
            </a>
        @endforeach
    </nav>

    <section class="bc-content-section" aria-labelledby="quality-filter-heading">
        <div class="bc-section-heading"><h2 id="quality-filter-heading">Nach Problem filtern</h2></div>

        <ul class="bc-quality-chips">
            <li>
                <a
                    href="{{ $chipLink(['problem' => null]) }}"
                    class="bc-quality-chip {{ $filters['problem'] === null ? 'bc-quality-chip--active' : '' }}"
                    @if ($filters['problem'] === null) aria-current="true" @endif
                >Alle Mängel <span class="bc-quality-count">{{ $counts['open_defects'] }}</span></a>
            </li>
            @foreach ($defectIssues as $issue)
                @continue($counts['by_problem'][$issue->value] === 0 && $filters['problem'] !== $issue->value)
                <li>
                    <a
                        href="{{ $chipLink(['problem' => $issue->value, 'status' => 'open']) }}"
                        class="bc-quality-chip {{ $filters['problem'] === $issue->value ? 'bc-quality-chip--active' : '' }}"
                        @if ($filters['problem'] === $issue->value) aria-current="true" @endif
                    >{{ $issue->shortLabel() }} <span class="bc-quality-count">{{ $counts['by_problem'][$issue->value] }}</span></a>
                </li>
            @endforeach
        </ul>

        <p class="bc-catalog-muted">
            Anreicherung (kein Mangel, fehlt fast überall):
            @foreach ($enrichmentIssues as $issue)
                <a href="{{ $chipLink(['problem' => $issue->value, 'status' => 'open']) }}">{{ $issue->shortLabel() }} ({{ $counts['by_problem'][$issue->value] }})</a>@if (! $loop->last) · @endif
            @endforeach
        </p>

        <form method="get" action="{{ route('pos.catalog.quality.index') }}" class="bc-quality-search" role="search">
            @if ($filters['status'] !== 'open')<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
            @if ($filters['problem'])<input type="hidden" name="problem" value="{{ $filters['problem'] }}">@endif
            <x-ui.input
                label="Titel, ISBN oder Verlag"
                name="q"
                type="search"
                :value="$filters['q']"
                :error="$errors->first('q')"
                maxlength="120"
            />
            <x-ui.button type="submit" variant="secondary">Filtern</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="quality-list-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="quality-list-heading">Fälle</h2>
            <span>{{ $reviews->total() }}</span>
        </div>

        @if ($reviews->total() === 0)
            <x-ui.alert title="Keine Fälle">
                @if ($filters['status'] === 'open' && $filters['problem'] === null && $filters['q'] === null)
                    Es gibt keine offenen Mängel. Nach Änderungen am Bestand kann „Bestand neu prüfen“ neue Fälle finden.
                @else
                    Für diese Auswahl gibt es keine Fälle.
                @endif
            </x-ui.alert>
        @else
            <x-catalog.pagination-controls
                :paginator="$reviews"
                :query-parameters="$queryParameters"
                route-name="pos.catalog.quality.index"
                id-prefix="quality-top"
            />

            <x-ui.table>
                <thead>
                    <tr>
                        <th scope="col">Titel</th>
                        <th scope="col">Ausgabe</th>
                        <th scope="col">Probleme</th>
                        <th scope="col">Vorschlag</th>
                        <th scope="col"><span class="sr-only">Aktion</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reviews as $review)
                        @php
                            $edition = $review->edition;
                            $title = $edition->title;
                            $proposalReady = $review->proposal_state === 'ready';
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]) }}"><strong>{{ \App\Surfaces\Pos\Support\CatalogQualityPresenter::highlight($title->preferred_title) }}</strong></a>
                                @if ($title->subtitle)
                                    <div class="bc-catalog-muted">{{ \App\Surfaces\Pos\Support\CatalogQualityPresenter::highlight($title->subtitle) }}</div>
                                @endif
                            </td>
                            <td>
                                {{ $edition->publisher_name ?: 'Verlag fehlt' }}
                                @if ($edition->publication_year) · {{ $edition->publication_year }} @endif
                                @if ($edition->isbn)<div class="bc-catalog-muted">ISBN {{ $edition->isbn }}</div>@endif
                            </td>
                            <td>
                                <ul class="bc-quality-badges">
                                    @foreach ($review->issues as $value)
                                        @php($issue = \App\Modules\Catalog\Enums\MetadataIssue::tryFrom($value))
                                        {{-- Anreicherung ist kein Mangel und bleibt in der Liste unsichtbar, außer man filtert gezielt danach. --}}
                                        @if ($issue && (! $issue->isEnrichment() || $filters['problem'] === $issue->value))
                                            <li><x-ui.badge :variant="$issue->weight() >= 30 ? 'danger' : 'neutral'">{{ $issue->shortLabel() }}</x-ui.badge></li>
                                        @endif
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                @if ($proposalReady)
                                    <x-ui.badge variant="success">Vorschlag liegt vor</x-ui.badge>
                                @elseif ($review->proposal_state === 'unavailable')
                                    <span class="bc-catalog-muted">DNB nicht erreichbar</span>
                                @elseif ($review->proposal_state === 'none')
                                    <span class="bc-catalog-muted">Kein Vorschlag möglich</span>
                                @else
                                    <span class="bc-catalog-muted">Noch nicht geprüft</span>
                                @endif
                            </td>
                            <td><a href="{{ route('pos.catalog.quality.show', ['reviewId' => $review->getKey()]) }}">Prüfen →</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-catalog.pagination-controls
                :paginator="$reviews"
                :query-parameters="$queryParameters"
                route-name="pos.catalog.quality.index"
                id-prefix="quality-bottom"
            />
        @endif
    </section>
</x-app-shell>
