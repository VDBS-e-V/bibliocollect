@props([
    'as' => 'section',
    'tone' => 'default',
    'density' => null,
])

@php
    $tone = in_array($tone, ['default', 'muted', 'brand', 'stage'], true) ? $tone : 'default';
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : null;
@endphp

<{{ $as }} {{ $attributes->class([
    'bc-card',
    'bc-card--'.$tone => $tone !== 'default',
    'bc-card--'.$density => $density !== null,
]) }}>
    {{ $slot }}
</{{ $as }}>
