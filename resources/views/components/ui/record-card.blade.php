@props([
    'title',
    'items' => [],
    'eyebrow' => null,
    'href' => null,
    'density' => 'comfortable',
])

@php($density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable')

<article {{ $attributes->class('bc-record-card bc-record-card--'.$density) }}>
    <header class="bc-record-card__header">
        <div class="bc-record-card__identity">
            @if ($eyebrow)
                <span class="bc-record-card__eyebrow">{{ $eyebrow }}</span>
            @endif
            <h3 class="bc-record-card__title">
                @if (filled($href))
                    <a href="{{ $href }}">{{ $title }}</a>
                @else
                    {{ $title }}
                @endif
            </h3>
        </div>
        @isset($status)
            <div class="bc-record-card__status">{{ $status }}</div>
        @endisset
    </header>
    <dl class="bc-record-card__data">
        @foreach ($items as $item)
            <div class="bc-record-card__datum">
                <dt>{{ $item['label'] ?? 'Angabe' }}</dt>
                <dd>{{ $item['value'] ?? '—' }}</dd>
            </div>
        @endforeach
    </dl>
    @isset($actions)
        <footer class="bc-record-card__actions">{{ $actions }}</footer>
    @endisset
</article>
