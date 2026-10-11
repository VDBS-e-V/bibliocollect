@props([
    'label' => 'Wird geladen …',
    'size' => 'md',
])

@php($size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md')

<span {{ $attributes->class('bc-spinner bc-spinner--'.$size) }} role="status">
    <span class="bc-spinner__indicator" aria-hidden="true"></span>
    <span class="bc-spinner__label">{{ $label }}</span>
</span>
