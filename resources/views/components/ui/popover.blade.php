@props([
    'label' => 'Weitere Informationen',
    'align' => 'start',
])

@php($align = in_array($align, ['start', 'end'], true) ? $align : 'start')

<details {{ $attributes->class('bc-popover bc-popover--'.$align) }} data-ui-dismissable>
    <summary class="bc-popover__trigger">{{ $label }} <span aria-hidden="true">⌄</span></summary>
    <div class="bc-popover__panel">
        {{ $slot }}
    </div>
</details>
