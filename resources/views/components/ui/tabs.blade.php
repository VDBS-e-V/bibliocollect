@props([
    'items' => [],
    'label' => 'Bereichsnavigation',
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
@endphp

<nav {{ $attributes->class('bc-tabs bc-tabs--'.$density) }} aria-label="{{ $label }}">
    <ul class="bc-tabs__list">
        @foreach ($items as $item)
            <li>
                @if (! empty($item['disabled']))
                    <span class="bc-tabs__link bc-tabs__link--disabled" aria-disabled="true">{{ $item['label'] ?? 'Tab' }}</span>
                @else
                    <a href="{{ $item['href'] ?? '#' }}"
                       @class(['bc-tabs__link', 'bc-tabs__link--active' => ! empty($item['active'])])
                       @if (! empty($item['active'])) aria-current="page" @endif>{{ $item['label'] ?? 'Tab' }}</a>
                @endif
            </li>
        @endforeach
    </ul>
</nav>
