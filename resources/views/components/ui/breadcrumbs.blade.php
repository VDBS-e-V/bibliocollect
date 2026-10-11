@props(['items' => []])

<nav {{ $attributes->class('bc-breadcrumbs') }} aria-label="Brotkrumennavigation">
    <ol class="bc-breadcrumbs__list">
        @foreach ($items as $item)
            <li class="bc-breadcrumbs__item">
                @if (! $loop->last && filled($item['href'] ?? null))
                    <a href="{{ $item['href'] }}">{{ $item['label'] ?? 'Seite' }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" @endif>{{ $item['label'] ?? 'Seite' }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
