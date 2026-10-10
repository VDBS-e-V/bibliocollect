@php
    use App\Modules\Catalog\Enums\MetadataReviewStatus;
    use App\Modules\Catalog\Quality\QualityText;
    use App\Surfaces\Pos\Support\CatalogQualityPresenter as Present;

    $title = $edition->title;
    $kindLabels = Present::kindLabels();
    $suggestions = $proposal?->suggestions() ?? [];
    $differences = $proposal?->differences() ?? [];
    $isOpen = $review->status === MetadataReviewStatus::Open;
    $sourceLabel = match ($proposal?->source) {
        'dnb-id' => 'DNB (über die gespeicherte DNB-ID)',
        'dnb-isbn' => 'DNB (über die ISBN)',
        'openlibrary-isbn' => 'Open Library (über die ISBN, weniger verlässlich als die DNB)',
        'googlebooks-isbn' => 'Google Books (über die ISBN, weniger verlässlich als die DNB)',
        default => 'Lokale Bereinigung (ohne externe Quelle)',
    };
@endphp

<x-app-shell surface="pos" title="Metadaten prüfen">
    <x-ui.page-header
        kicker="Katalogqualität"
        :title="QualityText::clean($title->preferred_title)"
        :lead="trim(($edition->publisher_name ?: 'Verlag fehlt').($edition->publication_year ? ' · '.$edition->publication_year : '').($edition->isbn ? ' · ISBN '.$edition->isbn : ''))"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.catalog.quality.index') }}">← Zur Liste</a>
        @if ($nextId)
            <a href="{{ route('pos.catalog.quality.skip', ['reviewId' => $review->getKey()]) }}">Überspringen →</a>
        @endif
        <a href="{{ route('pos.catalog.titles.show', ['titleId' => $title->getKey()]) }}">Titel öffnen</a>
        <a href="{{ route('pos.catalog.editions.edit', ['editionId' => $edition->getKey()]) }}">Ausgabe manuell bearbeiten</a>
    </div>

    @if (session('catalog_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('catalog_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Nicht übernommen">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="quality-status-heading">
        <div class="bc-section-heading"><h2 id="quality-status-heading">Befund</h2></div>

        <ul class="bc-quality-badges">
            @forelse ($issues as $issue)
                <li><x-ui.badge :variant="$issue->weight() >= 30 ? 'danger' : 'neutral'">{{ $issue->label() }}</x-ui.badge></li>
            @empty
                <li><x-ui.badge variant="success">Keine Probleme mehr</x-ui.badge></li>
            @endforelse
        </ul>

        <p class="bc-intake-note">Status: <strong>{{ $review->status->label() }}</strong></p>

        @if ($review->status === MetadataReviewStatus::Dismissed)
            <x-ui.alert variant="info" title="Kein Handlungsbedarf vermerkt">
                Dieser Fall wurde bewusst abgewiesen. Ändert sich die Ausgabe, öffnet er sich beim nächsten Prüfen wieder.
            </x-ui.alert>
            <form method="post" action="{{ route('pos.catalog.quality.reopen', ['reviewId' => $review->getKey()]) }}" class="bc-intake-actions">
                @csrf
                <x-ui.button type="submit" variant="secondary">Wieder öffnen</x-ui.button>
            </form>
        @elseif ($review->status === MetadataReviewStatus::Resolved)
            <x-ui.alert variant="success" title="Erledigt">Es sind keine Probleme mehr festgestellt worden.</x-ui.alert>
        @endif
    </section>

    @if ($isOpen && $proposal)
        <section class="bc-content-section" aria-labelledby="quality-proposal-heading">
            <div class="bc-section-heading"><h2 id="quality-proposal-heading">Vorschlag</h2></div>

            <p class="bc-intake-note">
                Quelle: <strong>{{ $sourceLabel }}</strong>
                @if ($proposal->permalink)
                    · <a href="{{ $proposal->permalink }}" rel="noopener noreferrer" target="_blank">Datensatz {{ $proposal->recordId }}</a>
                @endif
                @if ($review->proposal_fetched_at)
                    · abgefragt {{ $review->proposal_fetched_at->timezone('Europe/Berlin')->format('d.m.Y H:i') }}
                @endif
            </p>

            @foreach ($proposal->warnings as $warning)
                <x-ui.alert variant="warning" title="Achtung">{{ $warning }}</x-ui.alert>
            @endforeach

            @if ($review->proposal_state === 'unavailable')
                <x-ui.alert variant="warning" title="DNB nicht erreichbar">Es werden nur lokale Bereinigungen vorgeschlagen. Du kannst es später erneut versuchen.</x-ui.alert>
            @endif

            @if ($suggestions === [] && $differences === [])
                <x-ui.alert title="Kein Vorschlag">
                    @if ($proposal->source === 'local')
                        Es gibt keinen automatischen Vorschlag. Bitte prüfe die Ausgabe von Hand.
                    @else
                        Die Quelle bestätigt die vorhandenen Angaben; es gibt nichts zu ergänzen.
                    @endif
                </x-ui.alert>
            @else
                <form method="post" action="{{ route('pos.catalog.quality.apply', ['reviewId' => $review->getKey()]) }}" class="bc-intake-form">
                    @csrf

                    @if ($suggestions !== [])
                        <x-ui.table class="bc-quality-changes">
                            <caption class="sr-only">Vorgeschlagene Änderungen</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Übernehmen</th>
                                    <th scope="col">Feld</th>
                                    <th scope="col">Aktuell</th>
                                    <th scope="col">Vorschlag</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($suggestions as $change)
                                    <tr>
                                        <td class="bc-quality-check-cell">
                                            <label class="bc-quality-check">
                                                <input type="checkbox" name="changes[]" value="{{ $change->key }}" @checked($change->selected)>
                                                <span class="sr-only">{{ $change->label }} übernehmen</span>
                                            </label>
                                        </td>
                                        <th scope="row">
                                            {{ $change->label }}
                                            <div><x-ui.badge :variant="in_array($change->kind, ['fix', 'rename'], true) ? 'success' : 'neutral'">{{ $kindLabels[$change->kind] ?? $change->kind }}</x-ui.badge></div>
                                        </th>
                                        <td>{{ Present::highlight($change->current) }}</td>
                                        <td>
                                            {{ Present::plain($change->proposed) }}
                                            @if ($change->kind === 'rename' && ($change->payload['shared_titles'] ?? 0) > 0)
                                                <div class="bc-catalog-muted">Betrifft auch {{ $change->payload['shared_titles'] }} weitere(n) Titel.</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @endif

                    @if ($differences !== [])
                        <details class="bc-quality-differences">
                            <summary>Weitere Abweichungen zur Quelle ({{ count($differences) }}), nicht vorausgewählt</summary>
                            <p class="bc-intake-note">Hier sind beide Seiten gefüllt und verschieden. Das ist oft nur eine andere Schreibweise. Übernimm nur, was du sicher besser findest.</p>
                            <x-ui.table class="bc-quality-changes">
                                <caption class="sr-only">Abweichungen zur Quelle</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Übernehmen</th>
                                        <th scope="col">Feld</th>
                                        <th scope="col">Aktuell</th>
                                        <th scope="col">Quelle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($differences as $change)
                                        <tr>
                                            <td class="bc-quality-check-cell">
                                                <label class="bc-quality-check">
                                                    <input type="checkbox" name="changes[]" value="{{ $change->key }}">
                                                    <span class="sr-only">{{ $change->label }} übernehmen</span>
                                                </label>
                                            </td>
                                            <th scope="row">{{ $change->label }}</th>
                                            <td>{{ Present::highlight($change->current) }}</td>
                                            <td>{{ Present::plain($change->proposed) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </x-ui.table>
                        </details>
                    @endif

                    <div class="bc-intake-actions">
                        <x-ui.button type="submit">Ausgewählte übernehmen</x-ui.button>
                    </div>
                </form>
            @endif

            <div class="bc-intake-actions bc-quality-secondary-actions">
                <form method="post" action="{{ route('pos.catalog.quality.dismiss', ['reviewId' => $review->getKey()]) }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Kein Handlungsbedarf</x-ui.button>
                </form>
                <form method="post" action="{{ route('pos.catalog.quality.refresh', ['reviewId' => $review->getKey()]) }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Vorschlag neu abfragen</x-ui.button>
                </form>
            </div>
        </section>
    @endif

    @if (! empty($review->history))
        <section class="bc-content-section" aria-labelledby="quality-history-heading">
            <div class="bc-section-heading"><h2 id="quality-history-heading">Verlauf</h2></div>
            <ul class="bc-quality-history">
                @foreach (array_reverse($review->history) as $entry)
                    <li>
                        <strong>{{ ['applied' => 'Übernommen', 'dismissed' => 'Kein Handlungsbedarf', 'reopened' => 'Wieder geöffnet'][$entry['action'] ?? ''] ?? ($entry['action'] ?? '') }}</strong>
                        · {{ \Illuminate\Support\Carbon::parse($entry['at'])->timezone('Europe/Berlin')->format('d.m.Y H:i') }}
                        @if (isset($userNames[$entry['user_id'] ?? 0])) · {{ $userNames[$entry['user_id']] }} @endif
                        @if (! empty($entry['changes']))
                            <ul>
                                @foreach ($entry['changes'] as $applied)
                                    <li>{{ $applied['label'] }}: <span class="bc-catalog-muted">{{ Present::plain($applied['from'] ?? '', 80) ?: 'leer' }}</span> → {{ Present::plain($applied['to'] ?? '', 80) }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-shell>
