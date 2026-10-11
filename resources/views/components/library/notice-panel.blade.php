@props([
    'variant' => 'info',
    'title',
    'compact' => false,
])

@php
    $variant = in_array($variant, ['info', 'success', 'warning', 'error'], true) ? $variant : 'info';
@endphp

<section {{ $attributes->class(['bc-notice-panel', 'bc-notice-panel--'.$variant, 'bc-notice-panel--compact' => $compact]) }} role="{{ $variant === 'error' ? 'alert' : 'status' }}">
    <span class="bc-notice-panel__icon" aria-hidden="true">
        @switch($variant)
            @case('success') ✓ @break
            @case('warning') ! @break
            @case('error') × @break
            @default i
        @endswitch
    </span>
    <div class="bc-notice-panel__body">
        <h3>{{ $title }}</h3>
        <div class="bc-notice-panel__text">{{ $slot }}</div>
        @if (isset($actions))
            <div class="bc-notice-panel__actions">{{ $actions }}</div>
        @endif
    </div>
</section>
