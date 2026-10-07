<x-app-shell surface="administration" title="Themenbereiche">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Themenbereiche"
        lead="Die Themenbereiche mit ihren Unterbereichen. Sie erscheinen bei den Signaturen und im Katalog."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.signatures.index') }}">← Zurück zu den Signaturen</a>
    </div>

    @if (session('taxonomy_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('taxonomy_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="new-topic-heading">
        <div class="bc-section-heading"><h2 id="new-topic-heading">Themenbereich hinzufügen</h2></div>
        <form method="post" action="{{ route('administration.topics.store') }}" class="bc-audit-filter">
            @csrf
            <x-ui.input label="Name" name="name" id="new-topic-name" :value="old('name')" maxlength="120" required />
            <x-ui.select label="Gehört zu" name="parent_id" id="new-topic-parent">
                <option value="">Hauptbereich</option>
                @foreach ($roots as $root)
                    <option value="{{ $root->getKey() }}" @selected(old('parent_id') === (string) $root->getKey())>{{ $root->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.input label="Schlüssel (für die öffentliche Adresse)" name="public_key" id="new-topic-key" :value="old('public_key')" maxlength="40" />
            <x-ui.input label="Beschreibung" name="description" id="new-topic-description" :value="old('description')" maxlength="300" />
            <x-ui.button type="submit">Hinzufügen</x-ui.button>
        </form>
    </section>

    @foreach ($roots as $root)
        <section class="bc-content-section" aria-labelledby="topic-{{ $root->getKey() }}-heading">
            <div class="bc-section-heading"><h2 id="topic-{{ $root->getKey() }}-heading">{{ $root->name }}</h2></div>
            <ul class="bc-shelf-list">
                @foreach ([$root, ...$topics->where('parent_id', $root->getKey())->all()] as $topic)
                    <li class="bc-shelf">
                        <form method="post" action="{{ route('administration.topics.update', ['topicId' => $topic->getKey()]) }}" class="bc-shelf__form">
                            @csrf
                            @method('PATCH')
                            <x-ui.input label="Name" name="name" id="topic-name-{{ $topic->getKey() }}" :value="$topic->name" maxlength="120" required />
                            <x-ui.select label="Gehört zu" name="parent_id" id="topic-parent-{{ $topic->getKey() }}">
                                <option value="">Hauptbereich</option>
                                @foreach ($roots as $option)
                                    @if ($option->getKey() !== $topic->getKey())
                                        <option value="{{ $option->getKey() }}" @selected($topic->parent_id === $option->getKey())>{{ $option->name }}</option>
                                    @endif
                                @endforeach
                            </x-ui.select>
                            <x-ui.input label="Schlüssel" name="public_key" id="topic-key-{{ $topic->getKey() }}" :value="$topic->public_key" maxlength="40" />
                            <x-ui.input label="Beschreibung" name="description" id="topic-description-{{ $topic->getKey() }}" :value="$topic->description" maxlength="300" />
                            <x-ui.button type="submit" variant="secondary">Speichern</x-ui.button>
                        </form>
                        <div class="bc-shelf__meta">
                            <span>{{ $topic->signatures_count }} Signatur(en)@if ($topic->children_count > 0), {{ $topic->children_count }} Unterbereich(e)@endif</span>
                            @if ($topic->signatures_count === 0 && $topic->children_count === 0)
                                <form method="post" action="{{ route('administration.topics.destroy', ['topicId' => $topic->getKey()]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bc-intake-linkbutton" data-confirm="Themenbereich „{{ $topic->name }}“ wirklich löschen?">Löschen</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</x-app-shell>
