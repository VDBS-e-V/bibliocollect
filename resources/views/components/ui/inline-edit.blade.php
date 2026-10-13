@props([
    'name',
    'label',
    'value' => null,
    'hint' => null,
    'compact' => false,
])

<details {{ $attributes->class([
    'bc-inline-edit',
    'bc-inline-edit--compact' => $compact,
]) }}>
    <summary class="bc-inline-edit__summary">
        <span class="bc-inline-edit__summary-copy">
            <span class="bc-inline-edit__label">{{ $label }}</span>
            <strong class="bc-inline-edit__value">{{ filled($value) ? $value : 'Noch keine Angabe' }}</strong>
        </span>
        <span class="bc-inline-edit__trigger" aria-hidden="true">Bearbeiten ↓</span>
    </summary>
    <div class="bc-inline-edit__editor">
        <x-ui.input :name="$name" :label="$label" :value="$value" :hint="$hint" />
        @isset($actions)
            <div class="bc-inline-edit__actions">{{ $actions }}</div>
        @endisset
    </div>
</details>
