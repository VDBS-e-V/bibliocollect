@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
])

@php($classes = 'bc-button bc-button--'.$variant)

@if ($href)
    <a {{ $attributes->class($classes)->merge(['href' => $href]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($classes)->merge(['type' => $type]) }}>{{ $slot }}</button>
@endif
