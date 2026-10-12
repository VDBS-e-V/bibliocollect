@props([
    'title' => 'Auf dieser Seite',
    'items' => [],
    'label' => 'Abschnittsnavigation',
    'current' => null,
    'density' => 'comfortable',
])

@php($density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable')

<nav {{ $attributes->class('bc-section-index bc-section-index--'.$density) }} aria-label="{{ $label }}" data-ui-section-index>
    <strong class="bc-section-index__title">{{ $title }}</strong>
    @if (count($items) > 0)
        <ol class="bc-section-index__list">
            @foreach ($items as $item)
                <li class="bc-section-index__item">
                    @if (filled($item['href'] ?? null) && ! ($item['disabled'] ?? false))
                        <a class="bc-section-index__link" href="{{ $item['href'] }}" @if (($item['href'] ?? null) === $current) aria-current="location" @endif>
                            <span class="bc-section-index__number" aria-hidden="true">{{ $loop->iteration }}</span>
                            <span>{{ $item['label'] ?? 'Abschnitt '.$loop->iteration }}</span>
                        </a>
                    @else
                        <span class="bc-section-index__link bc-section-index__link--disabled" aria-disabled="true">
                            <span class="bc-section-index__number" aria-hidden="true">{{ $loop->iteration }}</span>
                            <span>{{ $item['label'] ?? 'Abschnitt '.$loop->iteration }}</span>
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    @else
        <p class="bc-section-index__empty">Keine Abschnitte vorhanden.</p>
    @endif
</nav>
