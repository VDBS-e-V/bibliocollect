@props(['title' => 'Abweichende Zeiten', 'items' => [], 'density' => 'comfortable', 'emptyLabel' => 'Keine abweichenden Zeiten eingetragen.'])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp
<section {{ $attributes->class('bc-schedule-exceptions bc-schedule-exceptions--'.$density) }} aria-label="{{ $title }}">
    <strong class="bc-schedule-exceptions__title">{{ $title }}</strong>
    @if (count($items))
        <ul class="bc-schedule-exceptions__list">
            @foreach ($items as $item)
                <li class="bc-schedule-exceptions__item">
                    <div><strong>{{ $item['label'] ?? 'Ausnahme' }}</strong>
                        @if (filled($item['date'] ?? null)) <time @if (filled($item['datetime'] ?? null)) datetime="{{ $item['datetime'] }}" @endif>{{ $item['date'] }}</time> @endif
                        @if (filled($item['note'] ?? null)) <p>{{ $item['note'] }}</p> @endif
                    </div>
                    @if (! empty($item['closed']))
                        <span class="bc-schedule-exceptions__closed">Geschlossen</span>
                    @elseif (filled($item['hours'] ?? null))
                        <span class="bc-schedule-exceptions__hours">{{ $item['hours'] }}</span>
                    @else
                        <span class="bc-schedule-exceptions__unknown">Zeiten offen</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="bc-schedule-exceptions__empty">{{ $emptyLabel }}</p>
    @endif
</section>
