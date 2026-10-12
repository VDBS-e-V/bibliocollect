@props([
    'name' => 'per_page',
    'label' => 'Einträge pro Seite',
    'value' => 20,
    'options' => [10, 20, 50, 100],
    'hint' => null,
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', $name.'-size');
    $hintId = $id.'-hint';
@endphp

<div {{ $attributes->except('id')->class('bc-page-size-selector bc-page-size-selector--'.$density) }}>
    <label class="bc-page-size-selector__label" for="{{ $id }}">{{ $label }}</label>
    <select class="bc-page-size-selector__control"
            id="{{ $id }}"
            name="{{ $name }}"
            @if (filled($hint)) aria-describedby="{{ $hintId }}" @endif>
        @foreach ($options as $option)
            @php($optionValue = is_array($option) ? ($option['value'] ?? '') : $option)
            <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $value)>{{ is_array($option) ? ($option['label'] ?? $optionValue) : $optionValue }}</option>
        @endforeach
    </select>
    @if (filled($hint))
        <p class="bc-page-size-selector__hint" id="{{ $hintId }}">{{ $hint }}</p>
    @endif
</div>
