<x-app-shell surface="pos" title="Hilfe">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Hilfe"
        lead="Kurze Anleitungen für den Alltag in der Bibliothek."
    />

    <nav class="bc-context-actions" aria-label="Themen">
        @foreach ($topics as $key => $label)
            <a href="{{ route('pos.help.show', ['topic' => $key]) }}" @if ($current === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($html)
        <article class="bc-content-page__body bc-help">{!! $html !!}</article>
    @else
        <section class="bc-content-section">
            <p class="bc-section-copy">Wähle ein Thema.</p>
            <ul>
                @foreach ($topics as $key => $label)
                    <li><a href="{{ route('pos.help.show', ['topic' => $key]) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-shell>
