<x-app-shell surface="public" title="Reihen">
    <x-ui.page-header
        kicker="Öffentlicher Katalog"
        title="Reihen"
        lead="Bücher, die zusammengehören, mit allen Bänden in der richtigen Reihenfolge."
    />

    <div class="bc-context-actions">
        <a href="{{ route('public.catalog.index') }}">← Zum Katalog</a>
    </div>

    <section class="bc-content-section" aria-labelledby="series-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="series-heading">Alle Reihen</h2>
            <span>{{ count($series) }}</span>
        </div>

        @if ($series === [])
            <p class="bc-section-copy">Es gibt noch keine Reihe mit mindestens zwei Titeln im Bestand.</p>
        @else
            <ul class="bc-wish-list">
                @foreach ($series as $item)
                    <li class="bc-reading-item">
                        <a href="{{ route('public.series', ['slug' => $item['slug']]) }}"><strong>{{ $item['name'] }}</strong></a>
                        <span>{{ $item['titles'] }} Titel</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-shell>
