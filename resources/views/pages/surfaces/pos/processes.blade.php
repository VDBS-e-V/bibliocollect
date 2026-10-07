<x-app-shell surface="pos" title="Alle Vorgänge">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Alle Vorgänge"
        lead="Alles, was du in der Bibliothek tun kannst, nach Aufgaben geordnet. Gezeigt wird, was dein Konto darf."
    />

    @foreach ($groups as $group)
        <section class="bc-content-section" aria-labelledby="group-{{ $loop->index }}-heading">
            <div class="bc-section-heading"><h2 id="group-{{ $loop->index }}-heading">{{ $group['title'] }}</h2></div>
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
</x-app-shell>
