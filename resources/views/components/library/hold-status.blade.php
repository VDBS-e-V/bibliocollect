@props([
    'state' => 'unknown',
    'position' => null,
    'detail' => null,
    'expiresLabel' => null,
    'compact' => false,
])
@php
    $labels = [
        'waiting' => 'Vormerkung aktiv',
        'ready' => 'Zur Abholung bereit',
        'paused' => 'Vormerkung pausiert',
        'expired' => 'Abholfrist abgelaufen',
        'cancelled' => 'Vormerkung beendet',
        'unknown' => 'Vormerkstatus unbekannt',
    ];
    $state = array_key_exists($state, $labels) ? $state : 'unknown';
    $position = filter_var($position, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
@endphp

<div {{ $attributes->class(['bc-hold-status', 'bc-hold-status--'.$state, 'bc-hold-status--compact' => $compact]) }}>
    <strong class="bc-hold-status__state">{{ $labels[$state] }}</strong>
    @if ($state === 'waiting' && $position !== null)
        <span class="bc-hold-status__position">Position {{ $position }} in der Warteliste</span>
    @endif
    @if ($state === 'ready' && filled($expiresLabel))
        <span class="bc-hold-status__deadline">Abholen bis: {{ $expiresLabel }}</span>
    @endif
    @if (filled($detail)) <span class="bc-hold-status__detail">{{ $detail }}</span> @endif
</div>
