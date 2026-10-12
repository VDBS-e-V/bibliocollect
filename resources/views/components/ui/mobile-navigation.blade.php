@props([
    'label' => 'Menü',
    'items' => [],
])

<details {{ $attributes->class('bc-mobile-navigation') }} data-ui-dismissable>
    <summary class="bc-mobile-navigation__trigger">
        <span aria-hidden="true">☰</span>
        <span>{{ $label }}</span>
        <span class="bc-mobile-navigation__chevron" aria-hidden="true">⌄</span>
    </summary>
    <nav class="bc-mobile-navigation__body" aria-label="{{ $label }}">
        <ul class="bc-mobile-navigation__list">
            @foreach ($items as $item)
                <li>
                    @if (! empty($item['disabled']) || blank($item['href'] ?? null))
                        <span class="bc-mobile-navigation__item bc-mobile-navigation__item--disabled" aria-disabled="true">{{ $item['label'] ?? 'Eintrag' }}</span>
                    @else
                        <a class="bc-mobile-navigation__item" href="{{ $item['href'] }}" @if (! empty($item['active'])) aria-current="page" @endif>{{ $item['label'] ?? 'Eintrag' }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>
</details>
