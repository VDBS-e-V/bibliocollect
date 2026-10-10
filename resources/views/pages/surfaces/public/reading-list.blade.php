<x-app-shell surface="public" :title="$list->name">
    <x-ui.page-header
        kicker="Leseliste"
        :title="$list->name"
        :lead="$list->description ?? 'Bücher, die für dich ausgesucht wurden. Du brauchst kein Konto, um sie anzusehen.'"
    />

    <div class="bc-context-actions">
        <a href="{{ route('public.catalog.index') }}">Im Katalog stöbern</a>
        @if ($list->classes->isNotEmpty())
            <span>Für: {{ $list->classes->pluck('name')->join(', ') }}</span>
        @endif
        @if ($list->ends_on)
            <span>bis {{ $list->ends_on->format('d.m.Y') }}</span>
        @endif
    </div>

    <section class="bc-content-section" aria-labelledby="items-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="items-heading">Bücher auf der Liste</h2>
            <span>{{ count($rows) }}</span>
        </div>

        @if ($rows === [])
            <p class="bc-section-copy">Auf dieser Liste stehen zurzeit keine Bücher, die im Bestand sind.</p>
        @else
            <ul class="bc-showcase__grid">
                @foreach ($rows as $row)
                    <li class="bc-showcase__tile">
                        <a href="{{ route('public.catalog.show', ['titleId' => $row['id']]) }}" class="bc-showcase__link">
                            <span class="bc-showcase__cover"><img src="{{ $row['cover'] }}" alt="" loading="lazy"></span>
                            <strong class="bc-showcase__title">{{ $row['title'] }}</strong>
                            @if ($row['authors'] !== '')<span class="bc-showcase__authors">{{ $row['authors'] }}</span>@endif
                        </a>
                        <x-ui.badge :variant="$row['variant']">{{ $row['badge'] }}</x-ui.badge>
                        <x-catalog.bookmark-button :title-id="$row['id']" :marked="in_array($row['id'], $marked, true)" />
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-shell>
