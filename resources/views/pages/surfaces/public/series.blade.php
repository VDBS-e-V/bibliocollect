<x-app-shell surface="public" :title="'Reihe '.$series->name">
    <x-ui.page-header
        kicker="Reihe"
        :title="$series->name"
        :lead="count($rows).' '.(count($rows) === 1 ? 'Titel' : 'Titel').' dieser Reihe sind im Bestand, nach Bandnummer geordnet.'"
    />

    <div class="bc-context-actions">
        <a href="{{ route('public.series.index') }}">← Alle Reihen</a>
        <a href="{{ route('public.catalog.index') }}">Im Katalog stöbern</a>
    </div>

    @if ($next)
        <x-ui.alert title="Nächster Band">
            Nach deinen bisherigen Ausleihen kommt als Nächstes
            <a href="{{ route('public.catalog.show', ['titleId' => $next['id']]) }}">{{ $next['title'] }}@if ($next['volume']) (Band {{ $next['volume'] }})@endif</a>.
        </x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="volumes-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="volumes-heading">Bände</h2>
            <span>{{ count($rows) }}</span>
        </div>

        <ul class="bc-showcase__grid">
            @foreach ($rows as $row)
                <li class="bc-showcase__tile">
                    <a href="{{ route('public.catalog.show', ['titleId' => $row['id']]) }}" class="bc-showcase__link">
                        <span class="bc-showcase__cover"><img src="{{ $row['cover'] }}" alt="" loading="lazy"></span>
                        <strong class="bc-showcase__title">{{ $row['title'] }}</strong>
                        @if ($row['volume'])<span class="bc-showcase__authors">Band {{ $row['volume'] }}</span>@endif
                        @if ($row['authors'] !== '')<span class="bc-showcase__authors">{{ $row['authors'] }}</span>@endif
                    </a>
                    <x-ui.badge :variant="$row['variant']">{{ $row['badge'] }}</x-ui.badge>
                    <x-catalog.bookmark-button :title-id="$row['id']" :marked="in_array($row['id'], $marked, true)" />
                </li>
            @endforeach
        </ul>
    </section>
</x-app-shell>
