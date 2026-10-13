@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
    'size' => null,
])

@php
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : null;
    $classes = 'bc-button bc-button--'.$variant.($size ? ' bc-button--'.$size : '');
@endphp

@if ($href)
    <a {{ $attributes->class($classes)->merge(['href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($classes)->merge(['type' => $type]) }}>{{ $slot }}</button>
@endif
