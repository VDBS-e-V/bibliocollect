@props([
    'daysRemaining' => null,
    'dateLabel' => null,
    'compact' => false,
])

@php
    // View-only component: callers provide a calendar-day difference, never an e-mail reminder rule.
    $days = $daysRemaining === null ? null : (int) $daysRemaining;
    $state = $days === null ? 'unknown' : ($days < 0 ? 'overdue' : ($days === 0 ? 'today' : ($days <= 7 ? 'soon' : 'normal')));
    $statusLabel = match ($state) {
        'overdue' => 'Überfällig',
        'today' => 'Heute fällig',
        'soon' => 'Bald fällig',
        'normal' => 'Ausgeliehen',
        default => 'Frist unbekannt',
    };
    $detail = match ($state) {
        'overdue' => 'Seit '.abs($days).' '.(abs($days) === 1 ? 'Tag' : 'Tagen').' überfällig',
        'today' => 'Rückgabe heute',
        'soon' => 'Rückgabe in '.$days.' '.($days === 1 ? 'Tag' : 'Tagen'),
        'normal' => 'Rückgabe in '.$days.' Tagen',
        default => null,
    };
@endphp

<span {{ $attributes->class([
    'bc-due-status',
    'bc-due-status--'.$state,
    'bc-due-status--compact' => $compact,
]) }}>
    <strong>{{ $statusLabel }}</strong>
    @if ($detail)
        <span class="bc-due-status__detail">{{ $detail }}</span>
    @endif
    @if (filled($dateLabel))
        <span class="bc-due-status__date">{{ $dateLabel }}</span>
    @endif
</span>
