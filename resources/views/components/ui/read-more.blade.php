@props([
    'title' => 'Weiterführende Informationen',
    'excerpt' => null,
    'moreLabel' => 'Mehr lesen',
    'open' => false,
    'density' => 'comfortable',
])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp

<details {{ $attributes->class('bc-read-more bc-read-more--'.$density) }} @if ($open) open @endif>
    <summary class="bc-read-more__summary">
        <span class="bc-read-more__more-label">{{ $moreLabel }}</span>
        <span class="bc-read-more__title">{{ $title }}</span>
    </summary>
    @if (filled($excerpt))
        <p class="bc-read-more__excerpt">{{ $excerpt }}</p>
    @endif
    <div class="bc-read-more__body">{{ $slot }}</div>
</details>
