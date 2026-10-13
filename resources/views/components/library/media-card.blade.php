@props([
    'title',
    'author' => null,
    'cover' => null,
    'href' => null,
    'coverVariant' => null,
    'density' => 'comfortable',
    'eyebrow' => null,
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
@endphp

<article {{ $attributes->class('bc-media-card bc-media-card--'.$density) }}>
    <div class="bc-media-card__cover">
        <x-library.media-cover :src="$cover" :title="$title" :author="$author ?? ''" :variant="$coverVariant" size="small" />
    </div>
    <div class="bc-media-card__content">
        @if ($eyebrow)
            <span class="bc-media-card__eyebrow">{{ $eyebrow }}</span>
        @endif
        <h3 class="bc-media-card__title">
            @if (filled($href))
                <a href="{{ $href }}">{{ $title }}</a>
            @else
                {{ $title }}
            @endif
        </h3>
        @if ($author)
            <p class="bc-media-card__author">{{ $author }}</p>
        @endif
        @isset($status)
            <div class="bc-media-card__status">{{ $status }}</div>
        @endisset
        @isset($actions)
            <div class="bc-media-card__actions">{{ $actions }}</div>
        @endisset
    </div>
</article>
