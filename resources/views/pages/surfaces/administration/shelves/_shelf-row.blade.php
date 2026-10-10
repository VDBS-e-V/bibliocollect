@php
    $copies = (int) ($counts[$shelf->code] ?? 0);
@endphp
<li class="bc-board {{ $shelf->is_active ? '' : 'bc-board--off' }}">
    <div class="bc-board__row">
        <span class="bc-loc-code">{{ $shelf->code }}</span>
        <span class="bc-board__label">{{ $shelf->label ?: 'ohne Beschriftung' }}@unless ($shelf->is_active) <x-ui.badge>ausgeschaltet</x-ui.badge>@endunless</span>
        <span class="bc-board__count">
            @if ($shelf->capacity)
                <span class="bc-meter" role="img" aria-label="{{ $copies }} von {{ $shelf->capacity }} Plätzen belegt"><span class="bc-meter__bar {{ $copies >= $shelf->capacity ? 'bc-meter__bar--full' : '' }}" style="width: {{ min(100, (int) round($copies / $shelf->capacity * 100)) }}%"></span></span>
                {{ $copies }} von {{ $shelf->capacity }}
                @if ($copies >= $shelf->capacity)<x-ui.badge variant="warning">voll</x-ui.badge>@else<small>· {{ $shelf->capacity - $copies }} frei</small>@endif
            @else
                {{ $copies }} {{ $copies === 1 ? 'Exemplar' : 'Exemplare' }}
            @endif
        </span>
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
        <form method="post" action="{{ route('administration.shelves.destroy', ['shelfId' => $shelf->getKey()]) }}">
            @csrf @method('DELETE')
            @if ($copies === 0)
                <button type="submit" class="bc-intake-linkbutton" data-confirm="Regalbrett „{{ $shelf->code }}“ wirklich löschen?" data-confirm-label="Löschen">Regalbrett löschen</button>
            @else
                <input type="hidden" name="release_copies" value="1">
                <button type="submit" class="bc-intake-linkbutton" data-confirm="Regalbrett „{{ $shelf->code }}“ löschen? {{ $copies }} {{ $copies === 1 ? 'Exemplar verliert' : 'Exemplare verlieren' }} dabei ihren Standort und stehen wieder zum Einsortieren an. Die Medien und ihre Inventarnummern bleiben." data-confirm-label="Löschen">Regalbrett löschen (Standort von {{ $copies }} {{ $copies === 1 ? 'Exemplar' : 'Exemplaren' }} entfernen)</button>
            @endif
        </form>
    </details>
</li>
