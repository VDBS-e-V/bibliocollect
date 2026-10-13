@props([
    'title',
    'message' => null,
    'variant' => 'info',
    'dismissible' => true,
])

@php
    $variant = in_array($variant, ['info', 'success', 'warning', 'error'], true) ? $variant : 'info';
@endphp

<div {{ $attributes->class('bc-ui-toast bc-ui-toast--'.$variant) }}
     role="{{ $variant === 'error' ? 'alert' : 'status' }}">
    <div class="bc-ui-toast__content">
        <strong>{{ $title }}</strong>
        @if ($message)
            <p>{{ $message }}</p>
        @endif
    </div>
    @if ($dismissible)
        <button type="button" class="bc-ui-toast__close" data-ui-toast-dismiss aria-label="Hinweis schließen">×</button>
    @endif
</div>
