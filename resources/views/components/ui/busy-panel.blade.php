@props([
    'title' => 'Daten werden geladen',
    'description' => null,
    'busy' => true,
    'compact' => false,
])

<div {{ $attributes->class(['bc-busy-panel', 'bc-busy-panel--compact' => $compact])->merge([
    'aria-busy' => $busy ? 'true' : 'false',
]) }}>
    @if ($busy)
        <div class="bc-busy-panel__indicator">
            <x-ui.spinner :label="$title" size="sm" />
            @if (filled($description))
                <p>{{ $description }}</p>
            @endif
        </div>
        <x-ui.skeleton :lines="2" compact />
    @else
        <strong class="bc-busy-panel__ready">{{ $title }}</strong>
        <div class="bc-busy-panel__body">{{ $slot }}</div>
    @endif
</div>
