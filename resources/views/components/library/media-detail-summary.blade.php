@props([
    'title',
    'author' => null,
    'subtitle' => null,
    'cover' => null,
    'coverVariant' => null,
    'metadata' => [],
    'availability' => null,
    'availabilityDetail' => null,
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
@endphp

<article {{ $attributes->class('bc-media-detail-summary bc-media-detail-summary--'.$density) }}>
    <div class="bc-media-detail-summary__cover">
        <x-library.media-cover :src="$cover" :title="$title" :author="$author ?? ''" :variant="$coverVariant" size="normal" />
    </div>
    <div class="bc-media-detail-summary__content">
        <div class="bc-media-detail-summary__heading">
            <span class="bc-media-detail-summary__eyebrow">Mediendetails</span>
            <h3>{{ $title }}</h3>
            @if (filled($subtitle)) <p class="bc-media-detail-summary__subtitle">{{ $subtitle }}</p> @endif
            @if (filled($author)) <p class="bc-media-detail-summary__author">{{ $author }}</p> @endif
        </div>
        @if (filled($availability))
            <div class="bc-media-detail-summary__availability">
                <x-library.availability-status :status="$availability" :detail="$availabilityDetail" :compact="$density === 'compact'" />
            </div>
        @endif
        @if (count($metadata) > 0)
            <x-ui.description-list :items="$metadata" :density="$density" />
        @endif
        @isset($status) <div class="bc-media-detail-summary__status">{{ $status }}</div> @endisset
        @isset($actions) <div class="bc-media-detail-summary__actions">{{ $actions }}</div> @endisset
    </div>
</article>
