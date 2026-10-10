<x-app-shell surface="portal" :title="$list->name">
    <x-ui.page-header
        :kicker="'Leseliste'.($list->schoolClass ? ' · '.$list->schoolClass->name : '')"
        :title="$list->name"
        :lead="$list->description ?? ($owner ? 'Stelle hier Bücher für deine Klasse zusammen.' : 'Bücher, die deine Lehrkraft für dich ausgesucht hat.')"
    />

    <div class="bc-context-actions">
        <a href="{{ route('portal.reading-lists') }}">← Alle Leselisten</a>
        <a href="{{ route('public.catalog.index') }}">Im Katalog stöbern</a>
    </div>

    @if (session('portal_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('portal_success') }}</x-ui.alert>
    @endif

    @if (session('portal_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('portal_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if ($owner && ! $list->isVisibleToClass())
        @php($why = ! $list->is_published ? 'Die Liste ist ausgeschaltet.' : ($list->school_class_id === null ? 'Wähle unten eine Klasse aus.' : 'Die Laufzeit ist vorbei.'))
        <x-ui.alert title="Noch nicht für die Klasse sichtbar">{{ $why }} Die Schüler:innen sehen die Liste erst, wenn du das änderst.</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="items-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="items-heading">Bücher auf der Liste</h2>
            <span>{{ count($rows) }}</span>
        </div>

        @if ($rows === [])
            <p class="bc-section-copy">{{ $owner ? 'Die Liste ist noch leer. Suche unten nach Büchern oder nimm welche von deiner Merkliste.' : 'Auf dieser Liste stehen zurzeit keine Bücher, die im Bestand sind.' }}</p>
        @else
            <ul class="bc-showcase__grid">
                @foreach ($rows as $row)
                    <li class="bc-showcase__tile">
                        <a href="{{ route('public.catalog.show', ['titleId' => $row['id']]) }}" class="bc-showcase__link">
                            <span class="bc-showcase__cover"><img src="{{ $row['cover'] }}" alt="" loading="lazy"></span>
                            <strong class="bc-showcase__title">{{ $row['title'] }}</strong>
                            @if ($row['authors'] !== '')<span class="bc-showcase__authors">{{ $row['authors'] }}</span>@endif
                        </a>
                        <x-ui.badge :variant="$row['variant']">{{ $row['badge'] }}</x-ui.badge>
                        <x-catalog.bookmark-button :title-id="$row['id']" :marked="in_array($row['id'], $marked, true)" />
                        @if ($owner)
                            <form method="post" action="{{ route('portal.reading-lists.remove', ['listId' => $list->getKey(), 'titleId' => $row['id']]) }}">
                                @csrf
                                <button type="submit" class="bc-intake-linkbutton" aria-label="{{ $row['title'] }} von der Liste entfernen">Entfernen</button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($owner)
        <section class="bc-content-section" aria-labelledby="add-heading">
            <h2 id="add-heading">Bücher hinzufügen</h2>

            <form method="get" action="{{ route('portal.reading-lists.show', ['listId' => $list->getKey()]) }}" class="bc-calendar-form" role="search">
                <x-ui.input label="Im Katalog suchen" name="q" id="list-search" :value="$term" hint="Titel, Autor:in oder Stichwort." />
                <x-ui.button type="submit">Suchen</x-ui.button>
            </form>

            @if ($term !== '')
                @if ($results === [])
                    <p class="bc-section-copy">Keine passenden Bücher gefunden, die nicht schon auf der Liste stehen.</p>
                @else
                    <ul class="bc-wish-list">
                        @foreach ($results as $row)
                            <li class="bc-reading-item">
                                <strong>{{ $row['title'] }}</strong>
                                @if ($row['authors'] !== '')<span>{{ $row['authors'] }}</span>@endif
                                <form method="post" action="{{ route('portal.reading-lists.add', ['listId' => $list->getKey(), 'titleId' => $row['id']]) }}">
                                    @csrf
                                    <input type="hidden" name="q" value="{{ $term }}">
                                    <button type="submit" class="bc-intake-linkbutton" aria-label="{{ $row['title'] }} zur Liste hinzufügen">Hinzufügen</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif

            @if ($fromBookmarks !== [])
                <h3>Von meiner Merkliste</h3>
                <ul class="bc-wish-list">
                    @foreach ($fromBookmarks as $row)
                        <li class="bc-reading-item">
                            <strong>{{ $row['title'] }}</strong>
                            @if ($row['authors'] !== '')<span>{{ $row['authors'] }}</span>@endif
                            <form method="post" action="{{ route('portal.reading-lists.add', ['listId' => $list->getKey(), 'titleId' => $row['id']]) }}">
                                @csrf
                                <button type="submit" class="bc-intake-linkbutton" aria-label="{{ $row['title'] }} zur Liste hinzufügen">Hinzufügen</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="bc-content-section" aria-labelledby="settings-heading">
            <h2 id="settings-heading">Angaben zur Liste</h2>
            <form method="post" action="{{ route('portal.reading-lists.update', ['listId' => $list->getKey()]) }}" class="bc-calendar-form">
                @csrf
                @include('pages.surfaces.partials.reading-list-form', ['prefix' => 'edit', 'classes' => $classes, 'list' => $list])
                <x-ui.button type="submit">Speichern</x-ui.button>
            </form>

            <form method="post" action="{{ route('portal.reading-lists.destroy', ['listId' => $list->getKey()]) }}">
                @csrf
                <button type="submit" class="bc-intake-linkbutton">Leseliste löschen</button>
            </form>
        </section>
    @endif
</x-app-shell>
