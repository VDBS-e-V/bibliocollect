{{-- Auswahl der Themenbereiche einer Signatur, gruppiert nach Hauptbereich. --}}
<div class="bc-topic-checklist">
    @foreach ($topicGroups as $group)
        <fieldset>
            <legend>{{ $group['root']->name }}</legend>
            @forelse ($group['children'] as $child)
                <label class="bc-checkbox-line">
                    <input type="checkbox" name="topics[]" value="{{ $child->getKey() }}" @checked(in_array((string) $child->getKey(), $selected, true))>
                    {{ $child->name }}
                </label>
            @empty
                <label class="bc-checkbox-line">
                    <input type="checkbox" name="topics[]" value="{{ $group['root']->getKey() }}" @checked(in_array((string) $group['root']->getKey(), $selected, true))>
                    {{ $group['root']->name }}
                </label>
            @endforelse
        </fieldset>
    @endforeach
</div>
