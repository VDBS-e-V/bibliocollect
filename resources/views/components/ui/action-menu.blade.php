@props([
    'label' => 'Weitere Aktionen',
    'items' => [],
    'density' => 'comfortable',
    'align' => 'start',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $align = in_array($align, ['start', 'end'], true) ? $align : 'start';
@endphp

<details {{ $attributes->class('bc-action-menu bc-action-menu--'.$density.' bc-action-menu--'.$align) }} data-ui-dismissable>
    <summary class="bc-action-menu__trigger">{{ $label }} <span aria-hidden="true">⌄</span></summary>
    <div class="bc-action-menu__panel">
        @foreach ($items as $item)
            @if (! empty($item['divider']))
                <hr class="bc-action-menu__divider">
            @elseif (! empty($item['disabled']) || blank($item['href'] ?? null))
                <span class="bc-action-menu__item bc-action-menu__item--disabled" aria-disabled="true">{{ $item['label'] ?? 'Aktion' }}</span>
            @else
                <a href="{{ $item['href'] }}" class="bc-action-menu__item">{{ $item['label'] ?? 'Aktion' }}</a>
            @endif
        @endforeach
    </div>
</details>
