@php
    $children = (int) $topic->children_count;
    $shelves = (int) $topic->shelves_count;
@endphp
<form method="post" action="{{ route('administration.topics.destroy', ['topicId' => $topic->getKey()]) }}" class="bc-delete-options">
    @csrf @method('DELETE')
    @if ($children === 0 && $shelves === 0)
        <button type="submit" class="bc-intake-linkbutton" data-confirm="Themenbereich „{{ $topic->name }}“ wirklich löschen?" data-confirm-label="Löschen">Löschen</button>
    @else
        <p class="bc-field__hint">
            Dieser Themenbereich hat
            @if ($children > 0){{ $children }} {{ $children === 1 ? 'Unterbereich' : 'Unterbereiche' }}@endif
            @if ($children > 0 && $shelves > 0) und @endif
            @if ($shelves > 0){{ $shelves }} {{ $shelves === 1 ? 'Regalbrett' : 'Regalbretter' }}@endif.
            Beim Löschen werden die Zuordnungen zu Regalbrettern gelöst; Medien sind davon nicht betroffen.
        </p>
        <input type="hidden" name="detach_shelves" value="1">
        @if ($children > 0)
            <label class="bc-field__label" for="children-{{ $topic->getKey() }}">Was geschieht mit den Unterbereichen?</label>
            <select id="children-{{ $topic->getKey() }}" name="children" class="bc-field__control">
                <option value="move">Eine Ebene nach oben verschieben</option>
                <option value="delete">Mit löschen</option>
            </select>
        @endif
        <button type="submit" class="bc-intake-linkbutton" data-confirm="Themenbereich „{{ $topic->name }}“ löschen? Zuordnungen zu Regalbrettern werden gelöst, Unterbereiche gemäß deiner Auswahl behandelt. Medien bleiben unverändert." data-confirm-label="Löschen">Themenbereich löschen</button>
    @endif
</form>
