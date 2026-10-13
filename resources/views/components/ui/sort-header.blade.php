@props([
    'label',
    'href' => null,
    'direction' => null,
])

@php
    $direction = in_array($direction, ['ascending', 'descending'], true) ? $direction : null;
@endphp

<th {{ $attributes->class('bc-sort-header')->merge([
    'scope' => 'col',
    'aria-sort' => $direction,
]) }}>
    @if (filled($href))
        <a class="bc-sort-header__link" href="{{ $href }}">
            <span>{{ $label }}</span>
            <span class="bc-sort-header__mark" aria-hidden="true">{{ $direction === 'ascending' ? '↑' : ($direction === 'descending' ? '↓' : '↕') }}</span>
        </a>
    @else
        <span>{{ $label }}</span>
    @endif
</th>
