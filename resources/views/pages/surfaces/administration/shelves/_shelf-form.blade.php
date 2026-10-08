{{-- Formular für ein Regalbrett. Neu: $shelf ist null und $rackId ist das Regal, in das es kommt. --}}
@php
    $key = $shelf?->getKey() ?? 'new-'.($rackId ?? 'free');
    $currentRack = $shelf ? (string) $shelf->section_id : (string) ($rackId ?? '');
@endphp
<form method="post" action="{{ $shelf ? route('administration.shelves.update', ['shelfId' => $shelf->getKey()]) : route('administration.shelves.store') }}" class="bc-loc-form">
    @csrf
    @if ($shelf)
        @method('PATCH')
    @endif
    <x-ui.select label="Regal" name="rack_id" :id="'shelf-rack-'.$key" hint="Mit Regal und Bezeichnung setzt sich der Standort selbst zusammen.">
        <option value="">Ohne Regal (freier Standort-Code)</option>
        @foreach ($rackOptions as $id => $display)
            <option value="{{ $id }}" @selected($currentRack === (string) $id)>{{ $display }}</option>
        @endforeach
    </x-ui.select>
    <x-ui.input label="Bezeichnung des Regalbretts im Regal" name="board" :id="'shelf-board-'.$key" :value="$shelf?->board" maxlength="20" hint="Zum Beispiel „a“, „b“ oder „oben“." />
    @if (! $shelf || ! $shelf->section_id)
        <x-ui.input label="Freier Standort-Code (nur ohne Regal)" name="code" :id="'shelf-code-'.$key" :value="$shelf?->code" maxlength="40" />
    @endif
    <x-ui.input label="Beschriftung am Regalbrett" name="label" :id="'shelf-label-'.$key" :value="$shelf?->label" maxlength="120" hint="Was dort steht, zum Beispiel „Fantasy ab 10 Jahren“." />
    <x-ui.input label="Reihenfolge" name="sort_order" :id="'shelf-order-'.$key" type="number" min="0" max="9999" :value="$shelf?->sort_order ?? $nextOrder ?? 0" />
    @if ($shelf)
        <label class="bc-checkbox-line"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($shelf->is_active)> Beim Einsortieren auswählbar</label>
    @endif
    <div class="bc-loc-form__topics">
        <strong>Themenbereiche, die auf diesem Regalbrett stehen</strong>
        @include('pages.surfaces.administration.shelves._topic-checklist', ['topicGroups' => $topicGroups, 'selected' => $shelf ? $shelf->topics->map(fn ($topic): string => (string) $topic->getKey())->all() : []])
    </div>
    <x-ui.button type="submit" :variant="$shelf ? 'secondary' : 'primary'">{{ $shelf ? 'Speichern' : 'Regalbrett anlegen' }}</x-ui.button>
</form>
