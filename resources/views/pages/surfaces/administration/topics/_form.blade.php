{{-- Formular für einen Themenbereich. Neu: $topic ist null; $fixedParent legt den Hauptbereich fest (neuer Unterbereich). --}}
@php
    $key = $topic?->getKey() ?? 'new-'.($fixedParent?->getKey() ?? 'root');
@endphp
<form method="post" action="{{ $topic ? route('administration.topics.update', ['topicId' => $topic->getKey()]) : route('administration.topics.store') }}" class="bc-loc-form">
    @csrf
    @if ($topic)
        @method('PATCH')
    @endif
    <x-ui.input label="Name" name="name" :id="'topic-name-'.$key" :value="$topic?->name" maxlength="120" required />
    @if (isset($fixedParent) && $fixedParent)
        <input type="hidden" name="parent_id" value="{{ $fixedParent->getKey() }}">
    @else
        <x-ui.select label="Gehört zu" name="parent_id" :id="'topic-parent-'.$key">
            <option value="">Hauptbereich (steht ganz oben)</option>
            @foreach ($roots as $option)
                @if (! $topic || $option->getKey() !== $topic->getKey())
                    <option value="{{ $option->getKey() }}" @selected($topic && $topic->parent_id === $option->getKey())>Unterbereich von „{{ $option->name }}“</option>
                @endif
            @endforeach
        </x-ui.select>
    @endif
    <x-ui.input label="Schlüssel (für die öffentliche Adresse)" name="public_key" :id="'topic-key-'.$key" :value="$topic?->public_key" maxlength="40" />
    <x-ui.input label="Beschreibung" name="description" :id="'topic-description-'.$key" :value="$topic?->description" maxlength="300" />
    <x-ui.button type="submit" :variant="$topic ? 'secondary' : 'primary'">{{ $topic ? 'Speichern' : (isset($fixedParent) && $fixedParent ? 'Unterbereich anlegen' : 'Hauptbereich anlegen') }}</x-ui.button>
</form>
