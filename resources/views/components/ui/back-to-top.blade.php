@props([
    'href' => null,
    'label' => 'Zurück zum Seitenanfang',
    'density' => 'comfortable',
])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp

@if (filled($href))
    <a {{ $attributes->class('bc-back-to-top bc-back-to-top--'.$density)->merge(['href' => $href]) }}>
        <span aria-hidden="true">↑</span><span>{{ $label }}</span>
    </a>
@else
    <span {{ $attributes->class('bc-back-to-top bc-back-to-top--'.$density.' bc-back-to-top--unavailable') }} aria-disabled="true">
        <span aria-hidden="true">↑</span><span>{{ $label }} (kein Ziel)</span>
    </span>
@endif
