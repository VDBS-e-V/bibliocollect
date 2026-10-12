@props([
    'items' => [],
    'title' => 'Verlauf',
    'compact' => false,
])

@php($states = ['default', 'success', 'warning', 'error'])

<section {{ $attributes->class(['bc-activity-timeline', 'bc-activity-timeline--compact' => $compact]) }} aria-label="{{ $title }}">
    <ol class="bc-activity-timeline__list">
        @foreach ($items as $item)
            @php
                $status = in_array($item['status'] ?? 'default', $states, true) ? $item['status'] : 'default';
            @endphp
            <li class="bc-activity-timeline__item bc-activity-timeline__item--{{ $status }}">
                <div class="bc-activity-timeline__row">
                    <strong>{{ $item['title'] ?? 'Eintrag' }}</strong>
                    @if (filled($item['datetime'] ?? null))
                        <time datetime="{{ $item['datetime'] }}">{{ $item['dateLabel'] ?? $item['datetime'] }}</time>
                    @elseif (filled($item['dateLabel'] ?? null))
                        <span class="bc-activity-timeline__time">{{ $item['dateLabel'] }}</span>
                    @endif
                </div>
                @if (filled($item['description'] ?? null))
                    <p>{{ $item['description'] }}</p>
                @endif
                @if (filled($item['href'] ?? null))
                    <a href="{{ $item['href'] }}">{{ $item['linkLabel'] ?? 'Details ansehen' }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</section>
