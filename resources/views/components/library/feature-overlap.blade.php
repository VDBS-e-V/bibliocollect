@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'href' => null,
    'actionLabel' => null,
    'tone' => 'mint',
    'reverse' => false,
    'image' => null,
    'imageAlt' => '',
])

@php
    $tone = in_array($tone, ['mint', 'purple', 'neutral'], true) ? $tone : 'mint';
@endphp

<section {{ $attributes->class(['bc-feature-overlap', 'bc-feature-overlap--'.$tone, 'bc-feature-overlap--reverse' => $reverse]) }}>
    <div class="bc-feature-overlap__media">
        @if (isset($media))
            {{ $media }}
        @elseif ($image)
            <img src="{{ $image }}" alt="{{ $imageAlt }}" loading="lazy">
        @else
            <div class="bc-feature-overlap__illustration" aria-hidden="true">
                <img src="/brand/vdbs/figures-chain.svg" alt="" loading="lazy">
            </div>
        @endif
    </div>
    <div class="bc-feature-overlap__panel">
        @if ($eyebrow)
            <p class="bc-feature-overlap__eyebrow">{{ $eyebrow }}</p>
        @endif
        <h2>{{ $title }}</h2>
        @if ($description)
            <p class="bc-feature-overlap__description">{{ $description }}</p>
        @endif
        {{ $slot }}
        @if ($href && $actionLabel)
            <a class="bc-feature-overlap__action" href="{{ $href }}">{{ $actionLabel }} <span aria-hidden="true">↗</span></a>
        @endif
    </div>
</section>
