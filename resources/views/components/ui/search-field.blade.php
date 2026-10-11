@props([
    'label' => 'Suchen',
    'name' => 'q',
    'value' => null,
    'placeholder' => 'Suchbegriff eingeben …',
    'hint' => null,
])

@php
    $id = (string) $attributes->get('id', $name);
    $hintId = $id.'-hint';
@endphp

<div class="bc-search-field">
    <label class="bc-search-field__label" for="{{ $id }}">{{ $label }}</label>
    <div class="bc-search-field__wrap">
        <input {{ $attributes->class('bc-search-field__input')->merge([
            'id' => $id,
            'name' => $name,
            'type' => 'search',
            'value' => $value,
            'placeholder' => $placeholder,
            'aria-describedby' => $hint ? $hintId : null,
        ]) }}>
        <span class="bc-search-field__icon" aria-hidden="true">⌕</span>
    </div>
    @if ($hint)
        <p class="bc-search-field__hint" id="{{ $hintId }}">{{ $hint }}</p>
    @endif
</div>
