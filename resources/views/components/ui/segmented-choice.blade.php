@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'hint' => null,
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', $name);
@endphp

<fieldset {{ $attributes->except('id')->class('bc-segmented-choice bc-segmented-choice--'.$density) }}>
    <legend class="bc-segmented-choice__legend">{{ $label }}</legend>
    <div class="bc-segmented-choice__group">
        @foreach ($options as $index => $option)
            <label class="bc-segmented-choice__option">
                <input type="radio" name="{{ $name }}" value="{{ $option['value'] ?? '' }}"
                    @checked((string) $selected === (string) ($option['value'] ?? ''))
                    @disabled(! empty($option['disabled']))
                    @if (filled($hint)) aria-describedby="{{ $id }}-hint" @endif>
                <span>{{ $option['label'] ?? 'Auswahl' }}</span>
            </label>
        @endforeach
    </div>
    @if ($hint)
        <p class="bc-segmented-choice__hint" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif
</fieldset>
