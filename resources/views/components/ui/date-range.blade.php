@props([
    'name' => 'period',
    'label' => 'Zeitraum',
    'from' => null,
    'to' => null,
    'hint' => null,
    'error' => null,
    'min' => null,
    'max' => null,
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', $name);
    $hintId = $id.'-hint';
    $errorId = $id.'-error';
    $description = collect([$error ? $errorId : null, $hint ? $hintId : null])->filter()->implode(' ');
@endphp

<fieldset {{ $attributes->except('id')->class('bc-date-range bc-date-range--'.$density) }}>
    <legend class="bc-date-range__legend">{{ $label }}</legend>
    <div class="bc-date-range__fields">
        <div class="bc-date-range__field">
            <label for="{{ $id }}-from">Von</label>
            <input type="date" id="{{ $id }}-from" name="{{ $name }}_from" value="{{ $from }}"
                @if (filled($min)) min="{{ $min }}" @endif
                @if (filled($max)) max="{{ $max }}" @endif
                @if ($error) aria-invalid="true" @endif
                @if ($description !== '') aria-describedby="{{ $description }}" @endif>
        </div>
        <div class="bc-date-range__field">
            <label for="{{ $id }}-to">Bis</label>
            <input type="date" id="{{ $id }}-to" name="{{ $name }}_to" value="{{ $to }}"
                @if (filled($min)) min="{{ $min }}" @endif
                @if (filled($max)) max="{{ $max }}" @endif
                @if ($error) aria-invalid="true" @endif
                @if ($description !== '') aria-describedby="{{ $description }}" @endif>
        </div>
    </div>
    @if ($error)
        <p class="bc-date-range__error" id="{{ $errorId }}"><strong>Fehler:</strong> {{ $error }}</p>
    @endif
    @if ($hint)
        <p class="bc-date-range__hint" id="{{ $hintId }}">{{ $hint }}</p>
    @endif
</fieldset>
