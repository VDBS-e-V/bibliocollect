@props([
    'title' => 'Standort',
    'segments' => [],
    'signature' => null,
    'detail' => null,
    'emptyLabel' => 'Kein Standort angegeben.',
    'density' => 'comfortable',
])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp

<section {{ $attributes->class('bc-location-path bc-location-path--'.$density) }} aria-label="{{ $title }}">
    <strong class="bc-location-path__title">{{ $title }}</strong>
    @if (count($segments) > 0)
        <ol class="bc-location-path__segments">
            @foreach ($segments as $segment)
                <li>
                    @if (filled($segment['href'] ?? null) && empty($segment['disabled']))
                        <a href="{{ $segment['href'] }}">{{ $segment['label'] ?? 'Bereich' }}</a>
                    @else
                        <span>{{ $segment['label'] ?? 'Bereich' }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    @else
        <p class="bc-location-path__empty">{{ $emptyLabel }}</p>
    @endif
    @if (filled($signature) || filled($detail))
        <dl class="bc-location-path__meta">
            @if (filled($signature)) <div><dt>Signatur</dt><dd>{{ $signature }}</dd></div> @endif
            @if (filled($detail)) <div><dt>Hinweis</dt><dd>{{ $detail }}</dd></div> @endif
        </dl>
    @endif
</section>
