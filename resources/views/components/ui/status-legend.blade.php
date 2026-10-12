@props([
    'label' => 'Statuslegende',
    'items' => [],
    'density' => 'comfortable',
])

@php($density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable')

<section {{ $attributes->class('bc-status-legend bc-status-legend--'.$density) }} aria-label="{{ $label }}">
    <strong class="bc-status-legend__title">{{ $label }}</strong>
    <ul class="bc-status-legend__list">
        @foreach ($items as $item)
            @php
                $tone = in_array($item['tone'] ?? 'neutral', ['green', 'purple', 'orange', 'red', 'blue', 'neutral'], true)
                    ? $item['tone']
                    : 'neutral';
            @endphp
            <li class="bc-status-legend__item">
                <span class="bc-status-legend__marker bc-status-legend__marker--{{ $tone }}" aria-hidden="true"></span>
                <span>{{ $item['label'] ?? 'Status' }}</span>
                @if (filled($item['description'] ?? null))
                    <small>{{ $item['description'] }}</small>
                @endif
            </li>
        @endforeach
    </ul>
</section>
