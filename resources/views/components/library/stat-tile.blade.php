@props([
    'label',
    'value',
    'detail' => null,
    'tone' => 'neutral',
    'href' => null,
    'actionLabel' => null,
    'eyebrow' => null,
])

@php
    $tone = in_array($tone, ['neutral', 'mint', 'purple', 'dark'], true) ? $tone : 'neutral';
@endphp

<article {{ $attributes->class('bc-stat-tile bc-stat-tile--'.$tone) }}>
    @if ($eyebrow)<span class="bc-stat-tile__eyebrow">{{ $eyebrow }}</span>@endif
    <span class="bc-stat-tile__label">{{ $label }}</span>
    <strong class="bc-stat-tile__number">{{ $value }}</strong>
    @if ($detail)<p class="bc-stat-tile__detail">{{ $detail }}</p>@endif
    @if (isset($footer))<div class="bc-stat-tile__footer">{{ $footer }}</div>@endif
    @if ($href && $actionLabel)
        <a class="bc-stat-tile__link" href="{{ $href }}">{{ $actionLabel }} <span aria-hidden="true">↗</span></a>
    @endif
</article>
