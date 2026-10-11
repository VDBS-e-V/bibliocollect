@props([
    'title',
    'message' => null,
    'barcode' => null,
    'status' => 'info',
    'compact' => false,
])

@php
    $status = in_array($status, ['info', 'success', 'warning', 'error'], true) ? $status : 'info';
    $symbol = match ($status) {
        'success' => '✓',
        'warning' => '!',
        'error' => '×',
        default => 'i',
    };
@endphp

<div {{ $attributes->class([
    'bc-scan-feedback',
    'bc-scan-feedback--'.$status,
    'bc-scan-feedback--compact' => $compact,
]) }} role="{{ $status === 'error' ? 'alert' : 'status' }}">
    <span class="bc-scan-feedback__symbol" aria-hidden="true">{{ $symbol }}</span>
    <div class="bc-scan-feedback__content">
        <strong>{{ $title }}</strong>
        @if (filled($message))
            <p>{{ $message }}</p>
        @endif
        @if (filled($barcode))
            <p class="bc-scan-feedback__barcode">Barcode: <code>{{ $barcode }}</code></p>
        @endif
    </div>
</div>
