@props([
    'label',
    'value' => null,
    'max' => 100,
    'description' => null,
    'compact' => false,
])

@php
    $max = max(1, (int) $max);
    $value = $value === null ? null : max(0, min($max, (int) $value));
@endphp

<div {{ $attributes->class(['bc-progress', 'bc-progress--compact' => $compact]) }}>
    <div class="bc-progress__heading">
        <strong>{{ $label }}</strong>
        @if ($value !== null)
            <span>{{ (int) round($value / $max * 100) }} %</span>
        @endif
    </div>
    <progress max="{{ $max }}" @if ($value !== null) value="{{ $value }}" @endif aria-label="{{ $label }}"></progress>
    @if ($description)
        <p class="bc-progress__description">{{ $description }}</p>
    @endif
</div>
