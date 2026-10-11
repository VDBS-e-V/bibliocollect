@props([
    'title' => 'Filter',
    'resultCount' => null,
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
@endphp

<section {{ $attributes->class('bc-filter-toolbar bc-filter-toolbar--'.$density) }} aria-label="{{ $title }}">
    <div class="bc-filter-toolbar__heading">
        <strong>{{ $title }}</strong>
        @if ($resultCount !== null)
            <span class="bc-filter-toolbar__count">{{ (int) $resultCount }} Treffer</span>
        @endif
    </div>
    @isset($filters)
        <div class="bc-filter-toolbar__filters">{{ $filters }}</div>
    @endisset
    @isset($actions)
        <div class="bc-filter-toolbar__actions">{{ $actions }}</div>
    @endisset
</section>
