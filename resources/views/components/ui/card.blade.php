@props([
    'as' => 'section',
    'tone' => 'default',
    'density' => null,
])

@php
    $as = in_array($as, ['section', 'article', 'aside', 'div'], true) ? $as : 'section';
    $tone = in_array($tone, ['default', 'muted', 'brand', 'stage'], true) ? $tone : 'default';
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : null;
@endphp

<{{ $as }} {{ $attributes->class('bc-card') }}>
    {{ $slot }}
</{{ $as }}>
