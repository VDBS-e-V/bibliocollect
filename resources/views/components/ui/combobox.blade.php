@props([
    'name', 'label', 'options' => [], 'value' => null, 'placeholder' => 'Bitte auswählen',
    'hint' => null, 'error' => null, 'searchUrl' => null, 'minChars' => 2,
    'disabled' => false, 'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', $name);
    $describedBy = collect([$error ? $id.'-error' : null, $hint ? $id.'-hint' : null])->filter()->implode(' ');
@endphp
<div {{ $attributes->except('id')->class('bc-combobox bc-combobox--'.$density) }}
    data-ui-combobox
    @if (filled($searchUrl)) data-ui-combobox-url="{{ $searchUrl }}" @endif
    data-ui-combobox-min="{{ max(1, (int) $minChars) }}">
    <label for="{{ $id }}">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}" class="bc-combobox__native"
        @disabled($disabled) @if ($error) aria-invalid="true" @endif
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $option)
            <option value="{{ $option['value'] ?? '' }}"
                @selected((string) $value === (string) ($option['value'] ?? ''))
                @disabled(! empty($option['disabled']))>{{ $option['label'] ?? ($option['value'] ?? 'Option') }}</option>
        @endforeach
    </select>
    @if ($error) <p id="{{ $id }}-error" class="bc-combobox__error"><strong>Fehler:</strong> {{ $error }}</p> @endif
    @if ($hint) <p id="{{ $id }}-hint" class="bc-combobox__hint">{{ $hint }}</p> @endif
</div>
