@props([
    'name', 'label', 'fields' => [], 'items' => [],
    'min' => 0, 'max' => 10, 'hint' => null, 'error' => null,
    'addLabel' => 'Weiteren Eintrag hinzufügen', 'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', 'bc-repeat-'.substr(hash('sha256', $name), 0, 12));
    $min = max(0, (int) $min);
    $max = max($min, (int) $max);
    $rows = array_values($items);
    while (count($rows) < $min) { $rows[] = []; }
    $rows = array_slice($rows, 0, $max);
    $describedBy = collect([$hint ? $id.'-hint' : null, $error ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<fieldset {{ $attributes->except('id')->class('bc-repeater-field bc-repeater-field--'.$density) }}
    data-ui-repeater data-ui-repeater-min="{{ $min }}" data-ui-repeater-max="{{ $max }}"
    data-ui-repeater-next="{{ count($rows) }}"
    @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif>
    <legend>{{ $label }}</legend>
    @if ($hint) <p class="bc-repeater-field__hint" id="{{ $id }}-hint">{{ $hint }}</p> @endif
    <div class="bc-repeater-field__rows" data-ui-repeater-rows>
        @foreach ($rows as $rowIndex => $row)
            <div class="bc-repeater-field__row" data-ui-repeater-row>
                <span class="bc-repeater-field__row-label">Eintrag {{ $rowIndex + 1 }}</span>
                <div class="bc-repeater-field__fields">
                    @foreach ($fields as $field)
                        @php
                            $key = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($field['key'] ?? 'field')) ?: 'field';
                            $type = in_array($field['type'] ?? '', ['text', 'email', 'number', 'url', 'date'], true) ? $field['type'] : 'text';
                        @endphp
                        <div class="bc-repeater-field__field">
                            <label for="{{ $id }}-{{ $rowIndex }}-{{ $key }}">{{ $field['label'] ?? $key }}</label>
                            <input type="{{ $type }}" id="{{ $id }}-{{ $rowIndex }}-{{ $key }}"
                                name="{{ $name }}[{{ $rowIndex }}][{{ $key }}]" value="{{ $row[$key] ?? '' }}"
                                @if (filled($field['placeholder'] ?? null)) placeholder="{{ $field['placeholder'] }}" @endif
                                @required(! empty($field['required']))>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="bc-repeater-field__remove" data-ui-repeater-remove disabled
                    aria-label="Eintrag {{ $rowIndex + 1 }} entfernen">Entfernen</button>
            </div>
        @endforeach
    </div>
    <p class="bc-repeater-field__empty" data-ui-repeater-empty @if (count($rows) > 0) hidden @endif>Noch keine Einträge vorhanden.</p>
    <button type="button" class="bc-repeater-field__add" data-ui-repeater-add disabled>{{ $addLabel }}</button>
    <p class="bc-repeater-field__status" data-ui-repeater-status role="status">{{ count($rows) }} Einträge</p>
    @if ($error) <p class="bc-repeater-field__error" id="{{ $id }}-error"><strong>Fehler:</strong> {{ $error }}</p> @endif
    <template data-ui-repeater-template>
        <div class="bc-repeater-field__row" data-ui-repeater-row>
            <span class="bc-repeater-field__row-label" data-ui-repeater-number>Neuer Eintrag</span>
            <div class="bc-repeater-field__fields">
                @foreach ($fields as $field)
                    @php
                        $key = preg_replace('/[^a-zA-Z0-9_]/', '', (string) ($field['key'] ?? 'field')) ?: 'field';
                        $type = in_array($field['type'] ?? '', ['text', 'email', 'number', 'url', 'date'], true) ? $field['type'] : 'text';
                    @endphp
                    <div class="bc-repeater-field__field">
                        <label for="{{ $id }}-__INDEX__-{{ $key }}">{{ $field['label'] ?? $key }}</label>
                        <input type="{{ $type }}" id="{{ $id }}-__INDEX__-{{ $key }}"
                            name="{{ $name }}[__INDEX__][{{ $key }}]"
                            @if (filled($field['placeholder'] ?? null)) placeholder="{{ $field['placeholder'] }}" @endif
                            @required(! empty($field['required']))>
                    </div>
                @endforeach
            </div>
            <button type="button" class="bc-repeater-field__remove" data-ui-repeater-remove disabled>Entfernen</button>
        </div>
    </template>
</fieldset>
