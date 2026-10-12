@props([
    'title' => 'Änderungen im Vergleich',
    'items' => [],
    'label' => 'Vorher-Nachher-Vergleich',
    'beforeLabel' => 'Vorher',
    'afterLabel' => 'Nachher',
    'emptyLabel' => 'Keine Vergleichsdaten vorhanden.',
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $rows = collect($items)->map(function ($item) {
        $status = in_array($item['status'] ?? '', ['changed', 'added', 'removed', 'unchanged'], true)
            ? $item['status']
            : (($item['before'] ?? null) === ($item['after'] ?? null) ? 'unchanged' : 'changed');

        $item['_status'] = $status;
        $item['_statusLabel'] = match ($status) {
            'added' => 'Hinzugefügt',
            'removed' => 'Entfernt',
            'unchanged' => 'Unverändert',
            default => 'Geändert',
        };

        return $item;
    })->all();
@endphp

<section {{ $attributes->class('bc-change-review bc-change-review--'.$density) }} aria-label="{{ $label }}">
    <header class="bc-change-review__header">
        <strong class="bc-change-review__title">{{ $title }}</strong>
        <span class="bc-change-review__count">{{ count($items) }} {{ count($items) === 1 ? 'Feld' : 'Felder' }}</span>
    </header>
    @if (count($items) > 0)
        <ul class="bc-change-review__list">
            @foreach ($rows as $item)
                <li class="bc-change-review__item bc-change-review__item--{{ $item['_status'] }}">
                    <div class="bc-change-review__item-head">
                        <strong>{{ $item['label'] ?? 'Angabe' }}</strong>
                        <span class="bc-change-review__status bc-change-review__status--{{ $item['_status'] }}">{{ $item['_statusLabel'] }}</span>
                    </div>
                    <dl class="bc-change-review__values">
                        <div>
                            <dt>{{ $beforeLabel }}</dt>
                            <dd>{{ filled($item['before'] ?? null) ? $item['before'] : '—' }}</dd>
                        </div>
                        <div>
                            <dt>{{ $afterLabel }}</dt>
                            <dd>{{ filled($item['after'] ?? null) ? $item['after'] : '—' }}</dd>
                        </div>
                    </dl>
                </li>
            @endforeach
        </ul>
    @else
        <p class="bc-change-review__empty">{{ $emptyLabel }}</p>
    @endif
    @isset($actions)
        <div class="bc-change-review__actions">{{ $actions }}</div>
    @endisset
</section>
