@props([
    'title',
    'eyebrow' => null,
    'description' => null,
    'level' => 2,
    'compact' => false,
])

@php
    $level = in_array((int) $level, [2, 3, 4], true) ? (int) $level : 2;
@endphp

<div {{ $attributes->class(['bc-section-title', 'bc-section-title--compact' => $compact]) }}>
    <div class="bc-section-title__copy">
        @if ($eyebrow)
            <p class="bc-section-title__eyebrow">{{ $eyebrow }}</p>
        @endif
        <h{{ $level }} class="bc-section-title__heading">{{ $title }}</h{{ $level }}>
        @if ($description)
            <p class="bc-section-title__description">{{ $description }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="bc-section-title__actions">{{ $actions }}</div>
    @endif
</div>
