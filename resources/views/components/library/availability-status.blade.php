@props([
    'status' => 'unknown',
    'detail' => null,
    'compact' => false,
])

@php
    $labels = [
        'available' => 'Verfügbar',
        'on-loan' => 'Ausgeliehen',
        'reserved' => 'Vorgemerkt',
        'unavailable' => 'Nicht verfügbar',
        'unknown' => 'Status unbekannt',
    ];
    $status = array_key_exists($status, $labels) ? $status : 'unknown';
@endphp

<span {{ $attributes->class([
    'bc-availability',
    'bc-availability--'.$status,
    'bc-availability--compact' => $compact,
]) }}>
    <strong>{{ $labels[$status] }}</strong>
    @if (filled($detail))
        <span class="bc-availability__detail">{{ $detail }}</span>
    @endif
</span>
