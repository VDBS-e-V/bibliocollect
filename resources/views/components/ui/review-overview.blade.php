@props([
    'title' => 'Bearbeitungsübersicht',
    'items' => [],
    'label' => 'Status der Bereiche',
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $completeCount = collect($items)->filter(fn ($item) => ($item['status'] ?? '') === 'complete')->count();
@endphp

<section {{ $attributes->class('bc-review-overview bc-review-overview--'.$density) }} aria-label="{{ $label }}">
    <header class="bc-review-overview__header">
        <strong class="bc-review-overview__title">{{ $title }}</strong>
        @if (count($items) > 0)
            <span class="bc-review-overview__count">{{ $completeCount }} von {{ count($items) }} Bereichen geprüft</span>
        @endif
    </header>
    @if (count($items) > 0)
        <ul class="bc-review-overview__list">
            @foreach ($items as $item)
                @php
                    $status = in_array($item['status'] ?? '', ['complete', 'current', 'attention', 'pending'], true)
                        ? $item['status']
                        : 'pending';
                    $statusLabel = match ($status) {
                        'complete' => 'Geprüft',
                        'current' => 'In Bearbeitung',
                        'attention' => 'Prüfung nötig',
                        default => 'Noch offen',
                    };
                @endphp
                <li class="bc-review-overview__item bc-review-overview__item--{{ $status }}" @if ($status === 'current') aria-current="step" @endif>
                    <div class="bc-review-overview__item-head">
                        @if (filled($item['href'] ?? null) && ! ($item['disabled'] ?? false))
                            <a href="{{ $item['href'] }}">{{ $item['title'] ?? 'Bereich' }}</a>
                        @else
                            <strong>{{ $item['title'] ?? 'Bereich' }}</strong>
                        @endif
                        <span class="bc-review-overview__status bc-review-overview__status--{{ $status }}">{{ $statusLabel }}</span>
                    </div>
                    @if (filled($item['description'] ?? null))
                        <p>{{ $item['description'] }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="bc-review-overview__empty">Noch keine Bereiche vorhanden.</p>
    @endif
    @isset($actions)
        <div class="bc-review-overview__actions">{{ $actions }}</div>
    @endisset
</section>
