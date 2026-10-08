<x-app-shell surface="administration" title="Themenbereiche">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Themenbereiche"
        lead="Jedes Medium bekommt beim Erfassen ein Thema. Themen sind in Hauptbereiche und Unterbereiche gegliedert. Welche Regalbretter zu einem Thema gehören, stellst du bei den Regalbrettern ein."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        <a href="{{ route('administration.shelves.index') }}">Regale und Regalbretter</a>
    </div>

    @if (session('taxonomy_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('taxonomy_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    @foreach ($roots as $root)
        @php
            $children = $topics->where('parent_id', $root->getKey())->values();
        @endphp
        <article class="bc-topic" aria-labelledby="topic-{{ $root->getKey() }}-heading">
            <header class="bc-topic__head">
                <span class="bc-loc__kind">Hauptbereich</span>
                <h2 id="topic-{{ $root->getKey() }}-heading">{{ $root->name }}</h2>
                <span class="bc-loc__count">{{ $children->count() }} {{ $children->count() === 1 ? 'Unterbereich' : 'Unterbereiche' }}</span>
            </header>
            @if ($root->description)<p class="bc-loc__note">{{ $root->description }}</p>@endif
            <p class="bc-topic__shelves">@include('pages.surfaces.administration.topics._shelves', ['topic' => $root])</p>

            <ul class="bc-topic__children">
                @foreach ($children as $topic)
                    <li class="bc-topic-child">
                        <div class="bc-topic-child__main">
                            <span class="bc-loc__kind">Unterbereich von {{ $root->name }}</span>
                            <strong>{{ $topic->name }}</strong>
                            @if ($topic->description)<span class="bc-loc__note">{{ $topic->description }}</span>@endif
                            <span class="bc-topic__shelves">@include('pages.surfaces.administration.topics._shelves', ['topic' => $topic])</span>
                        </div>
                        <details class="bc-loc__edit">
                            <summary>Unterbereich bearbeiten</summary>
                            @include('pages.surfaces.administration.topics._form', ['topic' => $topic, 'roots' => $roots])
                            @if ($topic->shelves_count === 0 && $topic->children_count === 0)
                                <form method="post" action="{{ route('administration.topics.destroy', ['topicId' => $topic->getKey()]) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bc-intake-linkbutton" data-confirm="Themenbereich „{{ $topic->name }}“ wirklich löschen?" data-confirm-label="Löschen">Löschen</button>
                                </form>
                            @endif
                        </details>
                    </li>
                @endforeach
            </ul>

            <details class="bc-loc__edit">
                <summary>Hauptbereich „{{ $root->name }}“ bearbeiten</summary>
                @include('pages.surfaces.administration.topics._form', ['topic' => $root, 'roots' => $roots])
                @if ($root->shelves_count === 0 && $root->children_count === 0)
                    <form method="post" action="{{ route('administration.topics.destroy', ['topicId' => $root->getKey()]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="bc-intake-linkbutton" data-confirm="Themenbereich „{{ $root->name }}“ wirklich löschen?" data-confirm-label="Löschen">Löschen</button>
                    </form>
                @endif
            </details>
            <details class="bc-loc__add">
                <summary>Unterbereich zu „{{ $root->name }}“ hinzufügen</summary>
                @include('pages.surfaces.administration.topics._form', ['topic' => null, 'roots' => $roots, 'fixedParent' => $root])
            </details>
        </article>
    @endforeach

    <section class="bc-content-section" aria-labelledby="new-topic-heading">
        <div class="bc-section-heading"><h2 id="new-topic-heading">Neuer Hauptbereich</h2></div>
        <details class="bc-loc__add" @if ($roots->isEmpty()) open @endif>
            <summary>Hauptbereich hinzufügen</summary>
            @include('pages.surfaces.administration.topics._form', ['topic' => null, 'roots' => $roots])
        </details>
    </section>
</x-app-shell>
