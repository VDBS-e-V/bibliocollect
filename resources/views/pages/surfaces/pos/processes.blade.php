<x-app-shell surface="pos" title="Alle Vorgänge">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Alle Vorgänge"
        lead="Alles, was du in der Bibliothek tun kannst, getrennt in Betrieb und Verwaltung. Gezeigt wird, was dein Konto darf."
    />

    @if (count($areas) > 1)
        <nav class="bc-context-actions" aria-label="Bereiche">
            @foreach ($areas as $area)
                <a href="#bereich-{{ $area['key'] }}">{{ $area['title'] }}</a>
            @endforeach
        </nav>
    @endif

    @foreach ($areas as $area)
        <section class="bc-process-area" id="bereich-{{ $area['key'] }}" aria-labelledby="area-{{ $area['key'] }}-heading">
            <div class="bc-section-heading"><h2 id="area-{{ $area['key'] }}-heading">{{ $area['title'] }}</h2></div>
            <p class="bc-section-copy">{{ $area['lead'] }}</p>

            @foreach ($area['groups'] as $group)
                <section aria-labelledby="group-{{ $area['key'] }}-{{ $loop->index }}-heading">
                    <h3 id="group-{{ $area['key'] }}-{{ $loop->index }}-heading" class="bc-process-group-title">{{ $group['title'] }}</h3>
                    <ul class="bc-process-list">
                        @foreach ($group['items'] as $item)
                            <li class="bc-process">
                                <a href="{{ route($item['route']) }}"><strong>{{ $item['label'] }}</strong></a>
                                <span>{{ $item['text'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </section>
    @endforeach
</x-app-shell>
