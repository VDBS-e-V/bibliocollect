@props([
    'label',
    'name',
    'hint' => null,
    'error' => null,
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

<div class="bc-field">
    <label class="bc-field__label" for="{{ $id }}">{{ $label }}</label>
    <select
        {{ $attributes->class(['bc-field__control', 'bc-field__control--error' => (bool) $error])->merge([
            'id' => $id,
            'name' => $name,
            'aria-invalid' => $error ? 'true' : 'false',
            'aria-describedby' => $describedBy !== '' ? $describedBy : null,
        ]) }}
    >{{ $slot }}</select>
    @if ($error)
        <p class="bc-field__error" id="{{ $errorId }}"><strong>Fehler:</strong> {{ $error }}</p>
    @endif
    @if ($hint)
        <p class="bc-field__hint" id="{{ $hintId }}">{{ $hint }}</p>
    @endif
</div>
