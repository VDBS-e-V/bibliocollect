@props(['title' => 'Auswahlübersicht', 'items' => [], 'density' => 'comfortable', 'emptyLabel' => 'Noch nichts ausgewählt.'])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp
<section {{ $attributes->class('bc-selection-summary bc-selection-summary--'.$density) }} aria-label="{{ $title }}">
    <header class="bc-selection-summary__header"><strong>{{ $title }}</strong><span>{{ count($items) }} ausgewählt</span></header>
    @if (count($items))
        <ul class="bc-selection-summary__list">
            @foreach ($items as $item)
                <li class="bc-selection-summary__item">
                    <div><strong>{{ $item['title'] ?? 'Eintrag' }}</strong>
                    @if (filled($item['detail'] ?? null))<p>{{ $item['detail'] }}</p>@endif</div>
                    @if (filled($item['removeHref'] ?? null) && empty($item['disabled']))<a href="{{ $item['removeHref'] }}" aria-label="Auswahl entfernen: {{ $item['title'] ?? 'Eintrag' }}">Entfernen</a>@endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="bc-selection-summary__empty">{{ $emptyLabel }}</p>
    @endif
    @isset($actions)<div class="bc-selection-summary__actions">{{ $actions }}</div>@endisset
</section>
