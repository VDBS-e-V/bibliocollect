@php
    $backLabel = 'Alle Hilfeartikel'.($query !== '' ? ' (Suche „'.$query.'“)' : '');
@endphp
<x-app-shell surface="pos" :title="$article['title']">
    <x-ui.page-header
        :kicker="'Hilfe · '.$areas[$article['area']]"
        :title="$article['title']"
        :lead="$article['summary']"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.help', array_filter(['q' => $query])) }}">← {{ $backLabel }}</a>
        <a href="{{ route('pos.help', ['bereich' => $article['area']]) }}">Mehr aus „{{ $areas[$article['area']] }}“</a>
    </div>

    <p class="bc-help-audience">
        Für:
        @foreach ($article['roles'] as $roleKey)<x-ui.badge variant="neutral">{{ $roles[$roleKey] }}</x-ui.badge>@endforeach
    </p>

    <article class="bc-content-page__body bc-help">{!! $html !!}</article>

    @if ($related !== [])
        <section class="bc-content-section" aria-labelledby="help-related-heading">
            <div class="bc-section-heading"><h2 id="help-related-heading">Weitere Artikel zum Thema</h2></div>
            <ul class="bc-help-list">
                @foreach ($related as $item)
                    <li class="bc-help-card">
                        <a class="bc-help-card__title" href="{{ route('pos.help.show', ['topic' => $item['slug']]) }}">{{ $item['title'] }}</a>
                        <p>{{ $item['summary'] }}</p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-shell>
