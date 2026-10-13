@props([
    'name' => 'hours', 'label' => 'Zeitfenster', 'from' => null, 'to' => null,
    'min' => null, 'max' => null, 'step' => 60, 'hint' => null, 'error' => null,
    'required' => false, 'disabled' => false, 'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', $name);
    $describedBy = collect([$error ? $id.'-error' : null, $hint ? $id.'-hint' : null])->filter()->implode(' ');
@endphp
<fieldset {{ $attributes->except('id')->class('bc-time-range bc-time-range--'.$density) }}>
    <legend>{{ $label }}</legend>
    <div class="bc-time-range__fields">
        <div><label for="{{ $id }}-from">Beginn</label>
            <input type="time" id="{{ $id }}-from" name="{{ $name }}_from" value="{{ $from }}"
                @if (filled($min)) min="{{ $min }}" @endif @if (filled($max)) max="{{ $max }}" @endif
                step="{{ max(1, (int) $step) }}" @required($required) @disabled($disabled)
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
        </div>
        <div><label for="{{ $id }}-to">Ende</label>
            <input type="time" id="{{ $id }}-to" name="{{ $name }}_to" value="{{ $to }}"
                @if (filled($min)) min="{{ $min }}" @endif @if (filled($max)) max="{{ $max }}" @endif
                step="{{ max(1, (int) $step) }}" @required($required) @disabled($disabled)
                @if ($error) aria-invalid="true" @endif
                @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
        </div>
    </div>
    @if ($error) <p class="bc-time-range__error" id="{{ $id }}-error"><strong>Fehler:</strong> {{ $error }}</p> @endif
    @if ($hint) <p class="bc-time-range__hint" id="{{ $id }}-hint">{{ $hint }}</p> @endif
</fieldset>
