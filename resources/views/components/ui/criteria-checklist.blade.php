@props([
    'title' => 'Prüfkriterien',
    'items' => [],
    'density' => 'comfortable',
    'emptyLabel' => 'Keine Prüfkriterien vorhanden.',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $rows = collect($items)->map(function ($item) {
        $state = in_array($item['state'] ?? '', ['met', 'unmet', 'pending', 'not-applicable'], true) ? $item['state'] : 'pending';
        $item['_state'] = $state;
        $item['_label'] = match ($state) {
            'met' => 'Erfüllt',
            'unmet' => 'Nicht erfüllt',
            'not-applicable' => 'Nicht anwendbar',
            default => 'Noch ungeprüft',
        };
        return $item;
    })->all();
    $metCount = collect($rows)->where('_state', 'met')->count();
@endphp
<section {{ $attributes->class('bc-criteria-checklist bc-criteria-checklist--'.$density) }} aria-label="{{ $title }}">
    <header class="bc-criteria-checklist__header">
        <strong>{{ $title }}</strong>
        @if (count($rows) > 0)<span>{{ $metCount }} von {{ count($rows) }} erfüllt</span>@endif
    </header>
    @if (count($rows) > 0)
        <ul class="bc-criteria-checklist__items">
            @foreach ($rows as $item)
                <li class="bc-criteria-checklist__item bc-criteria-checklist__item--{{ $item['_state'] }}">
                    <div class="bc-criteria-checklist__main">
                        <strong>{{ $item['title'] ?? 'Kriterium' }}</strong>
                        @if (filled($item['description'] ?? null)) <p>{{ $item['description'] }}</p> @endif
                    </div>
                    <span class="bc-criteria-checklist__state bc-criteria-checklist__state--{{ $item['_state'] }}">{{ $item['_label'] }}</span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="bc-criteria-checklist__empty">{{ $emptyLabel }}</p>
    @endif
    @isset($actions)<div class="bc-criteria-checklist__actions">{{ $actions }}</div>@endisset
</section>
