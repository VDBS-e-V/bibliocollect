@php
    $copies = (int) ($counts[$shelf->code] ?? 0);
@endphp
<li class="bc-board {{ $shelf->is_active ? '' : 'bc-board--off' }}">
    <div class="bc-board__row">
        <span class="bc-loc-code">{{ $shelf->code }}</span>
        <span class="bc-board__label">{{ $shelf->label ?: 'ohne Beschriftung' }}@unless ($shelf->is_active) <x-ui.badge>ausgeschaltet</x-ui.badge>@endunless</span>
        <span class="bc-board__count">{{ $copies }} {{ $copies === 1 ? 'Exemplar' : 'Exemplare' }}</span>
        <span class="bc-board__topics">
            @forelse ($shelf->topics as $topic)
                <span class="bc-chip">{{ $topic->name }}</span>
            @empty
                <span class="bc-board__none">Kein Thema zugeordnet (kein Vorschlag beim Einsortieren)</span>
            @endforelse
        </span>
    </div>
    <details class="bc-loc__edit">
        <summary>Regalbrett bearbeiten</summary>
        @include('pages.surfaces.administration.shelves._shelf-form', ['shelf' => $shelf, 'rackId' => $shelf->section_id])
        @if ($copies === 0)
            <form method="post" action="{{ route('administration.shelves.destroy', ['shelfId' => $shelf->getKey()]) }}">
                @csrf @method('DELETE')
                <button type="submit" class="bc-intake-linkbutton" data-confirm="Regalbrett „{{ $shelf->code }}“ wirklich löschen?" data-confirm-label="Löschen">Regalbrett löschen</button>
            </form>
        @endif
    </details>
</li>
