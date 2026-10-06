@props([
    'current',
    'skipDetails' => false,
])

@php
    $steps = [
        1 => 'Identifizieren',
        2 => 'Treffer prüfen',
        3 => 'Titel & Ausgabe',
        4 => 'Exemplar',
        5 => 'Prüfen & speichern',
    ];
@endphp

<div class="bc-intake-toolbar">
    <a href="{{ route('pos.catalog.index') }}">← Zurück zur Katalogpflege</a>

    @if ($current > 1 || session()->has('catalog_intake'))
        <form method="post" action="{{ route('pos.catalog.intake.cancel') }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="bc-intake-linkbutton">Vorgang abbrechen</button>
        </form>
    @endif
</div>

<ol class="bc-intake-steps" aria-label="Fortschritt der Erfassung">
    @foreach ($steps as $number => $label)
        @php
            $skipped = $number === 3 && $skipDetails && $current > 3;
            $state = $skipped ? 'skipped' : ($number < $current ? 'done' : ($number === $current ? 'current' : 'upcoming'));
        @endphp
        <li
            class="bc-intake-steps__item bc-intake-steps__item--{{ $state }}"
            @if ($state === 'current') aria-current="step" @endif
        >
            <span class="bc-intake-steps__number" aria-hidden="true">{{ $number }}</span>
            <span>
                {{ $label }}
                @if ($state === 'done')<span class="bc-intake-steps__hint">erledigt</span>@endif
                @if ($state === 'skipped')<span class="bc-intake-steps__hint">entfällt</span>@endif
            </span>
        </li>
    @endforeach
</ol>
