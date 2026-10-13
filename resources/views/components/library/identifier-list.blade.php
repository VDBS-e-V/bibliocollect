@props([
    'title' => 'Kennungen',
    'items' => [],
    'emptyLabel' => 'Keine Kennungen vorhanden.',
    'density' => 'comfortable',
])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp

<section {{ $attributes->class('bc-identifier-list bc-identifier-list--'.$density) }} aria-label="{{ $title }}">
    <strong class="bc-identifier-list__title">{{ $title }}</strong>
    @if (count($items) > 0)
        <dl class="bc-identifier-list__entries">
            @foreach ($items as $item)
                <div class="bc-identifier-list__entry">
                    <dt>{{ $item['label'] ?? 'Kennung' }}</dt>
                    <dd>
                        @if (filled($item['value'] ?? null))
                            @if (filled($item['href'] ?? null) && empty($item['disabled']))
                                <a href="{{ $item['href'] }}">{{ $item['value'] }}</a>
                            @else
                                <span class="bc-identifier-list__value">{{ $item['value'] }}</span>
                            @endif
                        @else
                            <span>—</span>
                        @endif
                        @if (filled($item['description'] ?? null))
                            <small>{{ $item['description'] }}</small>
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>
    @else
        <p class="bc-identifier-list__empty">{{ $emptyLabel }}</p>
    @endif
</section>
