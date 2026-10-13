@props([
    'title' => 'Weitere Filter',
    'count' => 0,
    'open' => false,
    'clearHref' => null,
])

<details {{ $attributes->class('bc-filter-panel') }} @if ($open) open @endif>
    <summary class="bc-filter-panel__summary">
        <span>{{ $title }}</span>
        @if ((int) $count > 0)
            <span class="bc-filter-panel__count">{{ (int) $count }} aktiv</span>
        @endif
    </summary>
    <div class="bc-filter-panel__body">
        <div class="bc-filter-panel__controls">{{ $slot }}</div>
        @if (filled($clearHref))
            <a class="bc-filter-panel__clear" href="{{ $clearHref }}">Filter zurücksetzen</a>
        @endif
    </div>
</details>
