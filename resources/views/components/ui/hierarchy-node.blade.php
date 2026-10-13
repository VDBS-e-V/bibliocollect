@props(['item'])

@php
    $label = $item['label'] ?? 'Bereich';
    $children = $item['children'] ?? [];
    $hasChildren = count($children) > 0;
    $href = $item['href'] ?? null;
    $active = (bool) ($item['active'] ?? false);
    $count = $item['count'] ?? null;
@endphp

<li class="bc-hierarchy-tree__node">
    @if ($hasChildren)
        <details class="bc-hierarchy-tree__branch" @if (! empty($item['open'])) open @endif>
            <summary class="bc-hierarchy-tree__summary">
                <span>{{ $label }}</span>
                @if ($count !== null)
                    <span class="bc-hierarchy-tree__count">{{ $count }}</span>
                @endif
            </summary>
            @if (filled($href))
                <a class="bc-hierarchy-tree__branch-link" href="{{ $href }}" @if ($active) aria-current="page" @endif>Bereich öffnen: {{ $label }}</a>
            @endif
            <ul class="bc-hierarchy-tree__list bc-hierarchy-tree__list--nested">
                @foreach ($children as $child)
                    <x-ui.hierarchy-node :item="$child" />
                @endforeach
            </ul>
        </details>
    @elseif (filled($href))
        <a class="bc-hierarchy-tree__leaf" href="{{ $href }}" @if ($active) aria-current="page" @endif>
            <span>{{ $label }}</span>
            @if ($count !== null)
                <span class="bc-hierarchy-tree__count">{{ $count }}</span>
            @endif
        </a>
    @else
        <span class="bc-hierarchy-tree__leaf bc-hierarchy-tree__leaf--disabled">
            <span>{{ $label }}</span>
            @if ($count !== null)
                <span class="bc-hierarchy-tree__count">{{ $count }}</span>
            @endif
        </span>
    @endif
</li>
