@props([
    'label' => 'Tastaturkürzel',
    'keys' => [],
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $keys = collect($keys)->filter(fn ($key) => filled($key))->values()->all();
@endphp

@if (count($keys))
    <span {{ $attributes->class('bc-shortcut-hint bc-shortcut-hint--'.$density) }}>
        <span class="bc-shortcut-hint__label">{{ $label }}</span>
        <span class="bc-shortcut-hint__keys" aria-hidden="true">
            @foreach ($keys as $key)
                @unless ($loop->first) <span class="bc-shortcut-hint__separator" aria-hidden="true">+</span> @endunless
                <kbd aria-hidden="true">{{ $key }}</kbd>
            @endforeach
        </span>
        <span class="sr-only">{{ implode(' plus ', $keys) }}</span>
    </span>
@endif
