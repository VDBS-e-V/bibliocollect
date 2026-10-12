@props([
    'items' => [],
    'label' => 'Hierarchische Navigation',
    'compact' => false,
])

<nav {{ $attributes->class([
    'bc-hierarchy-tree',
    'bc-hierarchy-tree--compact' => $compact,
]) }} aria-label="{{ $label }}">
    <ul class="bc-hierarchy-tree__list">
        @foreach ($items as $item)
            <x-ui.hierarchy-node :item="$item" />
        @endforeach
    </ul>
</nav>
