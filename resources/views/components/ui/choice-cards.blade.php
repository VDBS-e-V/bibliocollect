@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'multiple' => false,
    'hint' => null,
    'error' => null,
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', 'bc-choice-'.md5($name));
    $selectedValues = collect(is_array($selected) ? $selected : [$selected])->map(fn ($value) => (string) $value)->all();
    $inputName = $multiple && ! str_ends_with($name, '[]') ? $name.'[]' : $name;
@endphp
<fieldset {{ $attributes->except('id')->class('bc-choice-cards bc-choice-cards--'.$density) }} @if ($error) aria-invalid="true" @endif>
    <legend class="bc-choice-cards__legend">{{ $label }}</legend>
    @if ($hint)
        <p id="{{ $id }}-hint" class="bc-choice-cards__hint">{{ $hint }}</p>
    @endif
    <div class="bc-choice-cards__grid">
        @foreach ($options as $option)
            <label class="bc-choice-cards__option">
                <input type="{{ $multiple ? 'checkbox' : 'radio' }}" name="{{ $inputName }}" value="{{ $option['value'] ?? '' }}"
                    @checked(in_array((string) ($option['value'] ?? ''), $selectedValues, true))
                    @disabled(! empty($option['disabled']))
                    @if ($hint || $error) aria-describedby="{{ implode(' ', array_filter([$hint ? $id.'-hint' : null, $error ? $id.'-error' : null])) }}" @endif>
                <span class="bc-choice-cards__body">
                    <strong>{{ $option['title'] ?? $option['label'] ?? 'Option' }}</strong>
                    @if (filled($option['description'] ?? null))
                        <span>{{ $option['description'] }}</span>
                    @endif
                    @if (! empty($option['disabled'])) <small>Nicht auswählbar</small> @endif
                </span>
            </label>
        @endforeach
    </div>
    @if (count($options) === 0)
        <p class="bc-choice-cards__empty">Keine Auswahlmöglichkeiten vorhanden.</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="bc-choice-cards__error">{{ $error }}</p>
    @endif
</fieldset>
