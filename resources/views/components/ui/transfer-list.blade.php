@props([
    'name', 'label', 'items' => [], 'selected' => [],
    'availableLabel' => 'Verfügbar', 'selectedLabel' => 'Ausgewählt',
    'hint' => null, 'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', 'bc-transfer-'.substr(hash('sha256', $name), 0, 12));
    $selectedValues = collect(is_array($selected) ? $selected : [$selected])->map(fn ($v) => (string) $v)->all();
    $inputName = str_ends_with($name, '[]') ? $name : $name.'[]';
    $allItems = collect($items)->filter(fn ($item) => is_array($item) && array_key_exists('value', $item) && (string) $item['value'] !== '');
    $chosen = $allItems->filter(fn ($item) => in_array((string) $item['value'], $selectedValues, true))->values();
    $available = $allItems->reject(fn ($item) => in_array((string) $item['value'], $selectedValues, true))->values();
@endphp

<section {{ $attributes->except('id')->class('bc-transfer-list bc-transfer-list--'.$density) }}
    data-ui-transfer-list data-ui-transfer-name="{{ $inputName }}" aria-label="{{ $label }}">
    <strong class="bc-transfer-list__title">{{ $label }}</strong>
    @if (filled($hint)) <p class="bc-transfer-list__hint" id="{{ $id }}-hint">{{ $hint }}</p> @endif
    <div class="bc-transfer-list__grid">
        <div class="bc-transfer-list__group">
            <label for="{{ $id }}-available">{{ $availableLabel }}</label>
            <select id="{{ $id }}-available" multiple size="6" data-ui-transfer-available
                @if ($hint) aria-describedby="{{ $id }}-hint" @endif>
                @foreach ($available as $item)
                    <option value="{{ $item['value'] }}" @disabled(! empty($item['disabled']))>{{ $item['label'] ?? $item['value'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="bc-transfer-list__actions">
            <button type="button" data-ui-transfer-add disabled aria-label="Markierte Einträge zur Auswahl hinzufügen">Hinzufügen <span aria-hidden="true">→</span></button>
            <button type="button" data-ui-transfer-remove disabled aria-label="Markierte Einträge aus der Auswahl entfernen"><span aria-hidden="true">←</span> Entfernen</button>
        </div>
        <div class="bc-transfer-list__group">
            <label for="{{ $id }}-selected">{{ $selectedLabel }}</label>
            <select id="{{ $id }}-selected" multiple size="6" data-ui-transfer-selected
                @if ($hint) aria-describedby="{{ $id }}-hint" @endif>
                @foreach ($chosen as $item)
                    <option value="{{ $item['value'] }}" @disabled(! empty($item['disabled']))>{{ $item['label'] ?? $item['value'] }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div data-ui-transfer-hidden>
        @foreach ($chosen as $item)
            <input type="hidden" name="{{ $inputName }}" value="{{ $item['value'] }}">
        @endforeach
    </div>
    <p class="bc-transfer-list__status" data-ui-transfer-status role="status">{{ $chosen->count() }} ausgewählt</p>
</section>
