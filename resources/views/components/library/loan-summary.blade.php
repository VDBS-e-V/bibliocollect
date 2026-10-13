@props([
    'title',
    'author' => null,
    'barcode' => null,
    'dueDays' => null,
    'dueDateLabel' => null,
    'href' => null,
    'density' => 'comfortable',
])
@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
@endphp

<article {{ $attributes->class('bc-loan-summary bc-loan-summary--'.$density) }}>
    <div class="bc-loan-summary__main">
        <span class="bc-loan-summary__eyebrow">Ausleihe</span>
        <h3 class="bc-loan-summary__title">
            @if (filled($href)) <a href="{{ $href }}">{{ $title }}</a>
            @else {{ $title }} @endif
        </h3>
        @if (filled($author)) <p class="bc-loan-summary__author">{{ $author }}</p> @endif
        @if (filled($barcode) || filled($dueDateLabel))
            <dl class="bc-loan-summary__meta">
                @if (filled($barcode)) <div><dt>Exemplar</dt><dd>{{ $barcode }}</dd></div> @endif
                @if (filled($dueDateLabel)) <div><dt>Rückgabe am</dt><dd>{{ $dueDateLabel }}</dd></div> @endif
            </dl>
        @endif
    </div>
    <div class="bc-loan-summary__aside">
        <x-library.due-date-status :daysRemaining="$dueDays" :dateLabel="$dueDateLabel" :compact="$density === 'compact'" />
        @isset($actions) <div class="bc-loan-summary__actions">{{ $actions }}</div> @endisset
    </div>
</article>
