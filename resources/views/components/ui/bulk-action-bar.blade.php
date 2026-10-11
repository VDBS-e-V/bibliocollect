@props([
    'selected' => 0,
    'total' => null,
    'label' => 'Auswahl',
])

@php($selected = max(0, (int) $selected))

<section {{ $attributes->class('bc-bulk-action-bar') }} aria-label="{{ $label }}">
    <div class="bc-bulk-action-bar__summary" role="status">
        <strong>{{ $selected }} ausgewählt</strong>
        @if ($total !== null)
            <span>von {{ max(0, (int) $total) }}</span>
        @endif
    </div>
    @if ($selected > 0 && isset($actions))
        <div class="bc-bulk-action-bar__actions">{{ $actions }}</div>
    @endif
</section>
