@props([
    'name',
    'label',
    'hint' => null,
    'error' => null,
    'accept' => null,
    'multiple' => false,
])

@php
    $id = (string) $attributes->get('id', $name);
    $hintId = $id.'-hint';
    $errorId = $id.'-error';
    $describedBy = collect([
        $error ? $errorId : null,
        $hint ? $hintId : null,
    ])->filter()->implode(' ');
@endphp

<div class="bc-field bc-field--upload">
    <label class="bc-field__label" for="{{ $id }}">{{ $label }}</label>
    <input {{ $attributes->class([
        'bc-field__control',
        'bc-field__control--upload',
        'bc-field__control--error' => (bool) $error,
    ])->merge([
        'id' => $id,
        'name' => $name,
        'type' => 'file',
        'accept' => $accept,
        'multiple' => $multiple,
        'aria-invalid' => $error ? 'true' : 'false',
        'aria-describedby' => $describedBy !== '' ? $describedBy : null,
    ]) }}>
    @if ($error)
        <p class="bc-field__error" id="{{ $errorId }}"><strong>Fehler:</strong> {{ $error }}</p>
    @endif
    @if ($hint)
        <p class="bc-field__hint" id="{{ $hintId }}">{{ $hint }}</p>
    @endif
</div>
