@props([
    'title',
    'subtitle' => null,
    'meta' => null,
    'href' => null,
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
@endphp

<div {{ $attributes->class('bc-list-row bc-list-row--'.$density) }}>
    @if (isset($media))
        <div class="bc-list-row__media">{{ $media }}</div>
    @endif
    <div class="bc-list-row__content">
        @if (filled($href))
            <a class="bc-list-row__title" href="{{ $href }}">{{ $title }}</a>
        @else
            <strong class="bc-list-row__title">{{ $title }}</strong>
        @endif
        @if ($subtitle)
            <p class="bc-list-row__subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if ($meta)
        <span class="bc-list-row__meta">{{ $meta }}</span>
    @endif
    @if (isset($trailing))
        <div class="bc-list-row__trailing">{{ $trailing }}</div>
    @endif
</div>
