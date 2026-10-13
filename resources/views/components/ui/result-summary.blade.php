@props([
    'count' => 0,
    'query' => null,
    'label' => 'Suchergebnisse',
    'description' => null,
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $count = max(0, (int) $count);
@endphp

<section {{ $attributes->class('bc-result-summary bc-result-summary--'.$density) }} aria-label="{{ $label }}">
    <div class="bc-result-summary__copy">
        <strong class="bc-result-summary__count">{{ $count }} Treffer</strong>
        @if (filled($query))
            <span class="bc-result-summary__query">für „{{ $query }}“</span>
        @endif
        @if (filled($description))
            <p class="bc-result-summary__description">{{ $description }}</p>
        @endif
    </div>
    @isset($filters)
        <div class="bc-result-summary__filters">{{ $filters }}</div>
    @endisset
    @isset($actions)
        <div class="bc-result-summary__actions">{{ $actions }}</div>
    @endisset
</section>
