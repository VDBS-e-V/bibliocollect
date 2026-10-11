@props([
    'name',
    'label',
    'value' => '1',
    'checked' => false,
    'hint' => null,
])

@php($id = (string) $attributes->get('id', $name.'-'.md5((string) $value)))

<div class="bc-choice">
    <label class="bc-choice__label" for="{{ $id }}">
        <input {{ $attributes->class('bc-choice__input')->merge([
            'id' => $id,
            'name' => $name,
            'type' => 'checkbox',
            'value' => $value,
            'checked' => $checked,
        ]) }}>
        <span>{{ $label }}</span>
    </label>
    @if ($hint)
        <p class="bc-choice__hint">{{ $hint }}</p>
    @endif
</div>
