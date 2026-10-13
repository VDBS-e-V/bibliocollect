@props([
    'name' => 'contact_channels',
    'label' => 'Kontaktwege',
    'options' => [],
    'selected' => [],
    'hint' => null,
    'error' => null,
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', 'bc-contact-'.substr(hash('sha256', $name), 0, 12));
    $selectedValues = collect(is_array($selected) ? $selected : [$selected])->map(fn ($value) => (string) $value)->all();
    $inputName = str_ends_with($name, '[]') ? $name : $name.'[]';
    $describedBy = collect([$hint ? $id.'-hint' : null, $error ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<fieldset {{ $attributes->except('id')->class('bc-contact-preferences bc-contact-preferences--'.$density) }}
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
    <legend>{{ $label }}</legend>
    @if ($hint) <p id="{{ $id }}-hint" class="bc-contact-preferences__hint">{{ $hint }}</p> @endif
    @if (count($options) > 0)
        <div class="bc-contact-preferences__options">
            @foreach ($options as $index => $option)
                <div class="bc-contact-preferences__item">
                    <x-ui.checkbox :id="$id.'-choice-'.$index" :name="$inputName"
                        :value="$option['value'] ?? ''" :label="$option['label'] ?? 'Kontaktweg'"
                        :checked="in_array((string) ($option['value'] ?? ''), $selectedValues, true)"
                        :disabled="! empty($option['disabled'])"
                        :hint="$option['description'] ?? null" />
                    @if (! empty($option['disabled']))
                        <small class="bc-contact-preferences__disabled">Derzeit nicht auswählbar</small>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <p class="bc-contact-preferences__empty">Keine Kontaktwege zur Auswahl.</p>
    @endif
    @if ($error) <p id="{{ $id }}-error" class="bc-contact-preferences__error"><strong>Fehler:</strong> {{ $error }}</p> @endif
</fieldset>
