<x-app-shell surface="portal" title="Merkliste">
    <x-ui.page-header
        kicker="Mein Konto"
        title="Merkliste"
        lead="Bücher, die du dir merken möchtest. Hier findest du sie wieder und siehst, ob sie gerade da sind."
    />

    <div class="bc-context-actions">
        <a href="{{ route('portal.home') }}">← Zurück zur Übersicht</a>
        <a href="{{ route('public.catalog.index') }}">Im Katalog stöbern</a>
    </div>

    @if (session('bookmark_notice'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('bookmark_notice') }}</x-ui.alert>
    @endif

    @if (session('bookmark_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('bookmark_error') }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="bookmarks-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="bookmarks-heading">Gemerkte Titel</h2>
            <span>{{ count($rows) }}</span>
        </div>

        @if ($rows === [])
            <p class="bc-section-copy">Deine Merkliste ist leer. Im Katalog findest du bei jedem Buch den Knopf „Merken“.</p>
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
                        <form method="post" action="{{ route('portal.bookmarks.toggle', ['titleId' => $row['id']]) }}">
                            @csrf
                            <button type="submit" class="bc-intake-linkbutton" aria-label="{{ $row['title'] }} von der Merkliste entfernen">Entfernen</button>
                        </form>
                    </li>
                @endforeach
            </ul>
            @if ($total > count($rows))
                <p class="bc-section-copy">{{ $total - count($rows) }} gemerkte Titel sind zurzeit nicht im Bestand und deshalb ausgeblendet.</p>
            @endif
        @endif
    </section>
</x-app-shell>
