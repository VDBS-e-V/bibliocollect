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

    <div class="bc-acc-tools">
        <button type="button" class="bc-intake-linkbutton" data-acc-toggle="open">Alles aufklappen</button>
        <button type="button" class="bc-intake-linkbutton" data-acc-toggle="close">Alles zuklappen</button>
    </div>

    @foreach ($roots as $root)
        @php
            $children = $topics->where('parent_id', $root->getKey())->values();
        @endphp
        <details class="bc-acc bc-acc--group" data-acc>
            <summary>
                <i class="bc-dot bc-dot--group"></i>
                <span class="bc-acc__kind">Hauptbereich</span>
                <h2 class="bc-acc__title" id="topic-{{ $root->getKey() }}-heading">{{ $root->name }}</h2>
                <span class="bc-acc__meta">{{ $children->count() }} {{ $children->count() === 1 ? 'Unterbereich' : 'Unterbereiche' }}</span>
            </summary>
            <div class="bc-acc__body">
                @if ($root->description)<p class="bc-loc__note">{{ $root->description }}</p>@endif
                <p class="bc-topic__shelves">@include('pages.surfaces.administration.topics._shelves', ['topic' => $root])</p>
                @include('pages.surfaces.administration.topics._feature', ['topic' => $root])

                <ul class="bc-sub-list">
                    @foreach ($children as $topic)
                        <li class="bc-sub">
                            <div class="bc-sub__row">
                                <span class="bc-sub__arrow" aria-hidden="true">↳</span>
                                <strong class="bc-sub__name">{{ $topic->name }}</strong>
                                <span class="bc-acc__kind">Unterbereich von {{ $root->name }}</span>
                                <span class="bc-sub__shelves">@include('pages.surfaces.administration.topics._shelves', ['topic' => $topic])</span>
                            </div>
                            @if ($topic->description)<p class="bc-loc__note">{{ $topic->description }}</p>@endif
                            @include('pages.surfaces.administration.topics._feature', ['topic' => $topic])
                            <details class="bc-loc__edit">
                                <summary>Unterbereich bearbeiten</summary>
                                @include('pages.surfaces.administration.topics._form', ['topic' => $topic, 'roots' => $roots])
                                @include('pages.surfaces.administration.topics._delete', ['topic' => $topic])
                            </details>
                        </li>
                    @endforeach
                </ul>

                <details class="bc-loc__add">
                    <summary>Unterbereich zu „{{ $root->name }}“ hinzufügen</summary>
                    @include('pages.surfaces.administration.topics._form', ['topic' => null, 'roots' => $roots, 'fixedParent' => $root])
                </details>
                <details class="bc-loc__edit">
                    <summary>Hauptbereich „{{ $root->name }}“ bearbeiten</summary>
                    @include('pages.surfaces.administration.topics._form', ['topic' => $root, 'roots' => $roots])
                    @include('pages.surfaces.administration.topics._delete', ['topic' => $root])
                </details>
            </div>
        </details>
    @endforeach

    <details class="bc-loc__add bc-loc__add--top" @if ($roots->isEmpty()) open @endif>
        <summary>Neuen Hauptbereich hinzufügen</summary>
        @include('pages.surfaces.administration.topics._form', ['topic' => null, 'roots' => $roots])
    </details>
</x-app-shell>
