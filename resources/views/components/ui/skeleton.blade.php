@props([
    'lines' => 3,
    'compact' => false,
])

@php($lines = max(1, min(6, (int) $lines)))

<div {{ $attributes->class(['bc-skeleton', 'bc-skeleton--compact' => $compact]) }} aria-hidden="true">
    @for ($i = 0; $i < $lines; $i++)
        <span class="bc-skeleton__line"></span>
    @endfor
</div>
