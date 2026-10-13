@props([
    'label' => 'Hilfe anzeigen',
    'text',
])

@php($tooltipId = (string) $attributes->get('id', 'bc-tooltip-'.uniqid()))

<span {{ $attributes->except('id')->class('bc-tooltip') }}>
    <button type="button" class="bc-tooltip__trigger" aria-describedby="{{ $tooltipId }}">{{ $label }} <span aria-hidden="true">ⓘ</span></button>
    <span id="{{ $tooltipId }}" role="tooltip" class="bc-tooltip__bubble">{{ $text }}</span>
</span>
