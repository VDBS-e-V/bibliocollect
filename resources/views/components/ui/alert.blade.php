@props([
    'variant' => 'info',
    'title' => null,
])

<div {{ $attributes->class('bc-alert bc-alert--'.$variant) }} role="{{ $variant === 'error' ? 'alert' : 'status' }}">
    @if ($title)
        <strong class="bc-alert__title">{{ $title }}</strong>
    @endif
    <div>{{ $slot }}</div>
</div>
