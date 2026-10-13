@props([
    'name',
    'label',
    'value',
    'checked' => false,
    'hint' => null,
])

@php($id = (string) $attributes->get('id', $name.'-'.md5((string) $value)))
@php($hintId = $id.'-hint')

<div class="bc-choice">
    <label class="bc-choice__label" for="{{ $id }}">
        <input {{ $attributes->class('bc-choice__input')->merge([
            'id' => $id,
            'name' => $name,
            'type' => 'radio',
            'value' => $value,
            'checked' => $checked,
            'aria-describedby' => $hint ? $hintId : null,
        ]) }}>
        <span>{{ $label }}</span>
    </label>
    @if ($hint)
        <p class="bc-choice__hint" id="{{ $hintId }}">{{ $hint }}</p>
    @endif
</div>
