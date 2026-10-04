@props(['variant' => 'neutral'])

<span {{ $attributes->class('bc-badge bc-badge--'.$variant) }}>{{ $slot }}</span>
