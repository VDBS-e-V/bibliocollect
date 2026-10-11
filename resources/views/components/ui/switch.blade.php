@props([
    'name',
    'label',
    'checked' => false,
    'hint' => null,
])

@php($id = (string) $attributes->get('id', $name))

<div class="bc-switch">
    <label class="bc-switch__label" for="{{ $id }}">
        <input {{ $attributes->class('bc-switch__input')->merge([
            'id' => $id,
            'name' => $name,
            'type' => 'checkbox',
            'role' => 'switch',
            'value' => '1',
            'checked' => $checked,
        ]) }}>
        <span class="bc-switch__track" aria-hidden="true"></span>
        <span>{{ $label }}</span>
    </label>
    @if ($hint)
        <p class="bc-switch__hint">{{ $hint }}</p>
    @endif
</div>
