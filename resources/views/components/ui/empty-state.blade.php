@props([
    'title',
    'description' => null,
    'tone' => 'neutral',
    'compact' => false,
])

@php
    $tone = in_array($tone, ['neutral', 'brand'], true) ? $tone : 'neutral';
@endphp

<div {{ $attributes->class([
    'bc-empty-state',
    'bc-empty-state--'.$tone,
    'bc-empty-state--compact' => $compact,
]) }}>
    <span class="bc-empty-state__symbol" aria-hidden="true">○</span>
    <div class="bc-empty-state__content">
        <h3>{{ $title }}</h3>
        @if ($description)
            <p>{{ $description }}</p>
        @endif
        @if (isset($actions))
            <div class="bc-empty-state__actions">{{ $actions }}</div>
        @endif
    </div>
</div>
