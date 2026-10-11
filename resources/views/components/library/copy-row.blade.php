@props([
    'title',
    'barcode',
    'location' => null,
    'availability' => 'unknown',
    'dueDays' => null,
    'density' => 'comfortable',
    'href' => null,
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
@endphp

<article {{ $attributes->class('bc-copy-row bc-copy-row--'.$density) }}>
    <div class="bc-copy-row__main">
        <span class="bc-copy-row__eyebrow">Exemplar</span>
        @if (filled($href))
            <a class="bc-copy-row__title" href="{{ $href }}">{{ $title }}</a>
        @else
            <strong class="bc-copy-row__title">{{ $title }}</strong>
        @endif
        <dl class="bc-copy-row__meta">
            <div>
                <dt>Barcode</dt>
                <dd>{{ $barcode }}</dd>
            </div>
            @if (filled($location))
                <div>
                    <dt>Standort</dt>
                    <dd>{{ $location }}</dd>
                </div>
            @endif
        </dl>
    </div>
    <div class="bc-copy-row__side">
        <x-library.availability-status :status="$availability" compact />
        @if ($dueDays !== null)
            <x-library.due-date-status :daysRemaining="$dueDays" compact />
        @endif
        @isset($actions)
            <div class="bc-copy-row__actions">{{ $actions }}</div>
        @endisset
    </div>
</article>
