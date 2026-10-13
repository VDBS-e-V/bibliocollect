@props([
    'name',
    'label',
    'hint' => 'Datei auswählen oder hier ablegen.',
    'accept' => null,
    'multiple' => false,
])

@php
    $id = (string) $attributes->get('id', $name);
    $hintId = $id.'-hint';
    $feedbackId = $id.'-feedback';
@endphp

<div {{ $attributes->except('id')->class('bc-file-drop-zone') }} data-ui-file-zone>
    <label class="bc-file-drop-zone__label" for="{{ $id }}">{{ $label }}</label>
    <div class="bc-file-drop-zone__surface">
        <span class="bc-file-drop-zone__mark" aria-hidden="true">↑</span>
        <span class="bc-file-drop-zone__text">Datei auswählen oder ablegen</span>
        <input id="{{ $id }}" name="{{ $name }}" type="file"
               class="bc-file-drop-zone__input" data-ui-file-input
               @if (filled($accept)) accept="{{ $accept }}" @endif
               @if ($multiple) multiple @endif
               aria-describedby="{{ $hintId }} {{ $feedbackId }}">
    </div>
    <p class="bc-file-drop-zone__hint" id="{{ $hintId }}">{{ $hint }}</p>
    <output class="bc-file-drop-zone__feedback" id="{{ $feedbackId }}" for="{{ $id }}" data-ui-file-feedback aria-live="polite">Noch keine Datei ausgewählt.</output>
</div>
