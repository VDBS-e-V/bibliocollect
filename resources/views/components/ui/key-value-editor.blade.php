@props([
    'name', 'label' => 'Metadatenpaare', 'items' => [],
    'keyLabel' => 'Schlüssel', 'valueLabel' => 'Wert',
    'min' => 0, 'max' => 10, 'hint' => null, 'error' => null,
    'density' => 'comfortable',
])
@php
    $fields = [
        ['key' => 'key', 'label' => $keyLabel, 'required' => true],
        ['key' => 'value', 'label' => $valueLabel],
    ];
@endphp
<x-ui.repeater-field {{ $attributes }} :name="$name" :label="$label"
    :fields="$fields" :items="$items" :min="$min" :max="$max"
    :hint="$hint" :error="$error" :density="$density"
    addLabel="Weiteres Wertepaar hinzufügen" />
