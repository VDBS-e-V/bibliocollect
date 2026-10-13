@props([
    'name', 'label', 'value' => null, 'min' => null, 'max' => null,
    'step' => 60, 'hint' => null, 'error' => null,
    'required' => false, 'disabled' => false, 'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', $name);
    $describedBy = collect([$error ? $id.'-error' : null, $hint ? $id.'-hint' : null])->filter()->implode(' ');
@endphp
<div {{ $attributes->except('id')->class('bc-date-time-picker bc-date-time-picker--'.$density) }}>
    <label for="{{ $id }}">{{ $label }}</label>
    <input id="{{ $id }}" type="datetime-local" name="{{ $name }}" value="{{ $value }}"
        @if (filled($min)) min="{{ $min }}" @endif
        @if (filled($max)) max="{{ $max }}" @endif
        step="{{ max(1, (int) $step) }}" @required($required) @disabled($disabled)
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
    @if ($error) <p class="bc-date-time-picker__error" id="{{ $id }}-error"><strong>Fehler:</strong> {{ $error }}</p> @endif
    @if ($hint) <p class="bc-date-time-picker__hint" id="{{ $id }}-hint">{{ $hint }}</p> @endif
</div>
