@props([
    'items' => [],
    'label' => 'Direkt zu einem Seitenbereich springen',
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $links = collect($items)->filter(fn ($item) => is_array($item) && filled($item['href'] ?? null) && empty($item['disabled']))->values();
@endphp
@if ($links->isNotEmpty())
    <nav {{ $attributes->class('bc-skip-links bc-skip-links--'.$density) }} aria-label="{{ $label }}">
        @foreach ($links as $link)
            <a class="bc-skip-links__link" href="{{ $link['href'] }}">{{ $link['label'] ?? 'Zum Inhalt springen' }}</a>
        @endforeach
    </nav>
@endif
