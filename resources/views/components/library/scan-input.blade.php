@props([
    'name' => 'barcode',
    'label' => 'Barcode scannen',
    'placeholder' => 'Barcode eingeben oder scannen …',
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

<div class="bc-scan-input">
    <label class="bc-scan-input__label" for="{{ $id }}">{{ $label }}</label>
    <div class="bc-scan-input__control">
        <span class="bc-scan-input__icon" aria-hidden="true">▥</span>
        <input {{ $attributes->class('bc-scan-input__field')->merge([
            'id' => $id,
            'type' => 'text',
            'name' => $name,
            'placeholder' => $placeholder,
            'inputmode' => 'text',
            'autocomplete' => 'off',
            'aria-invalid' => $error ? 'true' : 'false',
            'aria-describedby' => $describedBy !== '' ? $describedBy : null,
        ]) }}>
    </div>
    @if ($error)
        <p class="bc-scan-input__error" id="{{ $errorId }}"><strong>Fehler:</strong> {{ $error }}</p>
    @endif
    @if ($hint)
        <p class="bc-scan-input__hint" id="{{ $hintId }}">{{ $hint }}</p>
    @endif
</div>
