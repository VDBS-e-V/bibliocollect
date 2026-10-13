@props([
    'title' => 'Dateianlagen',
    'items' => [],
    'emptyLabel' => 'Keine Dateien vorhanden.',
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
@endphp
<section {{ $attributes->class('bc-attachment-list bc-attachment-list--'.$density) }} aria-label="{{ $title }}">
    <header class="bc-attachment-list__header">
        <strong>{{ $title }}</strong><span>{{ count($items) }} {{ count($items) === 1 ? 'Datei' : 'Dateien' }}</span>
    </header>
    @if (count($items) > 0)
        <ul class="bc-attachment-list__items">
            @foreach ($items as $item)
                <li class="bc-attachment-list__item">
                    <div class="bc-attachment-list__details">
                        <strong>{{ $item['name'] ?? 'Datei ohne Namen' }}</strong>
                        <div class="bc-attachment-list__meta">
                            @if (filled($item['type'] ?? null)) <span>{{ $item['type'] }}</span> @endif
                            @if (filled($item['size'] ?? null)) <span>{{ $item['size'] }}</span> @endif
                            @if (filled($item['description'] ?? null)) <span>{{ $item['description'] }}</span> @endif
                        </div>
                    </div>
                    @if (filled($item['href'] ?? null) && empty($item['disabled']))
                        <a class="bc-attachment-list__action" href="{{ $item['href'] }}" @if (! empty($item['download'])) download @endif>{{ $item['actionLabel'] ?? 'Datei öffnen' }}</a>
                    @elseif (! empty($item['disabled']))
                        <span class="bc-attachment-list__disabled" aria-disabled="true">Nicht verfügbar</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="bc-attachment-list__empty">{{ $emptyLabel }}</p>
    @endif
    @isset($actions)
        <div class="bc-attachment-list__actions">{{ $actions }}</div>
    @endisset
</section>
