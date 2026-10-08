<x-app-shell surface="administration" title="Regale und Regalbretter">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Regale und Regalbretter"
        lead="So ist die Bibliothek aufgebaut: Jedes Regalbrett liegt in einem Regal, das Regal in einem Bereich und der Bereich in einer Bereichsgruppe. Beim Einsortieren schlägt das System anhand des Themas die passenden Bretter vor."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        <a href="{{ route('administration.topics.index') }}">Themenbereiche</a>
    </div>

    @if (session('shelf_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('shelf_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="structure-heading">
        <div class="bc-section-heading"><h2 id="structure-heading">So hängt es zusammen</h2></div>
        <ol class="bc-loc-path" aria-label="Ebenen des Standorts">
            <li class="bc-loc-path__step bc-loc-path__step--group"><strong>Bereichsgruppe</strong><span>z. B. „I“</span></li>
            <li class="bc-loc-path__step bc-loc-path__step--area"><strong>Bereich</strong><span>z. B. „A“</span></li>
            <li class="bc-loc-path__step bc-loc-path__step--rack"><strong>Regal</strong><span>z. B. „1“</span></li>
            <li class="bc-loc-path__step bc-loc-path__step--board"><strong>Regalbrett</strong><span>z. B. „a“</span></li>
        </ol>
        <p class="bc-section-copy">Zusammen ergibt das den <strong>Standort</strong> eines Exemplars: <span class="bc-loc-code">I. A 1 a</span>. Der Standort steht auf dem Etikett und in der Exemplarliste. Ändert sich eine Kennung, ändert sich der Standort der Exemplare darunter mit.</p>
    </section>

    @forelse ($groups as $group)
        <article class="bc-loc bc-loc--group" aria-labelledby="group-{{ $group->getKey() }}">
            <header class="bc-loc__head">
                <span class="bc-loc__kind">Bereichsgruppe</span>
                <h2 id="group-{{ $group->getKey() }}">{{ $group->code }} <span class="bc-loc__name">{{ $group->name ?: 'noch ohne Namen' }}</span></h2>
            </header>
            @if ($group->description)<p class="bc-loc__note">{{ $group->description }}</p>@endif
            <details class="bc-loc__edit">
                <summary>Bereichsgruppe bearbeiten</summary>
                @include('pages.surfaces.administration.shelves._section-form', ['section' => $group, 'kind' => \App\Modules\Catalog\Enums\ShelfSectionKind::Group, 'parentId' => null])
                @if ($group->children->isEmpty())
                    <form method="post" action="{{ route('administration.sections.destroy', ['sectionId' => $group->getKey()]) }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="bc-intake-linkbutton" data-confirm="Bereichsgruppe „{{ $group->code }}“ wirklich löschen?" data-confirm-label="Löschen">Bereichsgruppe löschen</button>
                    </form>
                @endif
            </details>

            @foreach ($group->children as $area)
                <section class="bc-loc bc-loc--area" aria-labelledby="area-{{ $area->getKey() }}">
                    <header class="bc-loc__head">
                        <span class="bc-loc__kind">Bereich</span>
                        <h3 id="area-{{ $area->getKey() }}">{{ $group->code }} › {{ $area->code }} <span class="bc-loc__name">{{ $area->name ?: 'noch ohne Namen' }}</span></h3>
                    </header>
                    @if ($area->description)<p class="bc-loc__note">{{ $area->description }}</p>@endif
                    <details class="bc-loc__edit">
                        <summary>Bereich bearbeiten</summary>
                        @include('pages.surfaces.administration.shelves._section-form', ['section' => $area, 'kind' => \App\Modules\Catalog\Enums\ShelfSectionKind::Area, 'parentId' => $group->getKey()])
                        @if ($area->children->isEmpty())
                            <form method="post" action="{{ route('administration.sections.destroy', ['sectionId' => $area->getKey()]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="bc-intake-linkbutton" data-confirm="Bereich „{{ $area->code }}“ wirklich löschen?" data-confirm-label="Löschen">Bereich löschen</button>
                            </form>
                        @endif
                    </details>

                    @foreach ($area->children as $rack)
                        <section class="bc-loc bc-loc--rack" aria-labelledby="rack-{{ $rack->getKey() }}">
                            <header class="bc-loc__head">
                                <span class="bc-loc__kind">Regal</span>
                                <h4 id="rack-{{ $rack->getKey() }}">{{ $group->code }} › {{ $area->code }} › {{ $rack->code }} <span class="bc-loc__name">{{ $rack->name ?: 'noch ohne Namen' }}</span></h4>
                                <span class="bc-loc__count">{{ $rack->shelves->count() }} {{ $rack->shelves->count() === 1 ? 'Regalbrett' : 'Regalbretter' }}</span>
                            </header>
                            @if ($rack->description)<p class="bc-loc__note">{{ $rack->description }}</p>@endif
                            <details class="bc-loc__edit">
                                <summary>Regal bearbeiten</summary>
                                @include('pages.surfaces.administration.shelves._section-form', ['section' => $rack, 'kind' => \App\Modules\Catalog\Enums\ShelfSectionKind::Rack, 'parentId' => $area->getKey()])
                                @if ($rack->shelves->isEmpty())
                                    <form method="post" action="{{ route('administration.sections.destroy', ['sectionId' => $rack->getKey()]) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bc-intake-linkbutton" data-confirm="Regal „{{ $rack->code }}“ wirklich löschen?" data-confirm-label="Löschen">Regal löschen</button>
                                    </form>
                                @endif
                            </details>

                            @if ($rack->shelves->isNotEmpty())
                                <ul class="bc-board-list">
                                    @foreach ($rack->shelves as $shelf)
                                        @include('pages.surfaces.administration.shelves._shelf-row', ['shelf' => $shelf])
                                    @endforeach
                                </ul>
                            @else
                                <p class="bc-loc__note">In diesem Regal liegt noch kein Regalbrett.</p>
                            @endif

                            <details class="bc-loc__add">
                                <summary>Regalbrett in Regal {{ $rack->code }} hinzufügen</summary>
                                @include('pages.surfaces.administration.shelves._shelf-form', ['shelf' => null, 'rackId' => $rack->getKey(), 'nextOrder' => ((int) $rack->shelves->max('sort_order')) + 1])
                            </details>
                        </section>
                    @endforeach

                    <details class="bc-loc__add">
                        <summary>Regal in Bereich {{ $area->code }} hinzufügen</summary>
                        @include('pages.surfaces.administration.shelves._section-form', ['section' => null, 'kind' => \App\Modules\Catalog\Enums\ShelfSectionKind::Rack, 'parentId' => $area->getKey(), 'nextOrder' => ((int) $area->children->max('sort_order')) + 1, 'hint' => 'Zum Beispiel „1“ oder „2“.'])
                    </details>
                </section>
            @endforeach

            <details class="bc-loc__add">
                <summary>Bereich in Bereichsgruppe {{ $group->code }} hinzufügen</summary>
                @include('pages.surfaces.administration.shelves._section-form', ['section' => null, 'kind' => \App\Modules\Catalog\Enums\ShelfSectionKind::Area, 'parentId' => $group->getKey(), 'nextOrder' => ((int) $group->children->max('sort_order')) + 1, 'hint' => 'Zum Beispiel „A“ oder „B“.'])
            </details>
        </article>
    @empty
        <x-ui.alert title="Noch keine Regale">Lege unten zuerst eine Bereichsgruppe an, dann Bereich, Regal und Regalbretter. Regalbretter mit altem Standort-Code („I. A 1 a“) kannst du auch automatisch zuordnen lassen.</x-ui.alert>
    @endforelse

    <section class="bc-content-section" aria-labelledby="new-group-heading">
        <div class="bc-section-heading"><h2 id="new-group-heading">Neue Bereichsgruppe</h2></div>
        <details class="bc-loc__add" @if ($groups->isEmpty()) open @endif>
            <summary>Bereichsgruppe hinzufügen</summary>
            @include('pages.surfaces.administration.shelves._section-form', ['section' => null, 'kind' => \App\Modules\Catalog\Enums\ShelfSectionKind::Group, 'parentId' => null, 'nextOrder' => ((int) $groups->max('sort_order')) + 1, 'hint' => 'Zum Beispiel „I“ oder „II“.'])
        </details>
    </section>

    @if ($unassigned->isNotEmpty() || $shelfTotal === 0)
        <section class="bc-loc bc-loc--loose" aria-labelledby="loose-heading">
            <header class="bc-loc__head">
                <span class="bc-loc__kind">Ohne Regal</span>
                <h2 id="loose-heading">Regalbretter ohne Regal</h2>
                <span class="bc-loc__count">{{ $unassigned->count() }}</span>
            </header>
            <p class="bc-loc__note">Diese Regalbretter haben einen freien Standort-Code und liegen in keinem Regal. Wähle ein Regal, damit sie in den Aufbau passen. Codes der Form „I. A 1 a“ lassen sich auch automatisch zuordnen.</p>
            @if ($unassigned->isNotEmpty())
                <form method="post" action="{{ route('administration.sections.assign') }}">
                    @csrf
                    <x-ui.button type="submit" variant="secondary">Anhand des Standort-Codes zuordnen</x-ui.button>
                </form>
                <ul class="bc-board-list">
                    @foreach ($unassigned as $shelf)
                        @include('pages.surfaces.administration.shelves._shelf-row', ['shelf' => $shelf])
                    @endforeach
                </ul>
            @endif
            <details class="bc-loc__add">
                <summary>Regalbrett ohne Regal hinzufügen</summary>
                @include('pages.surfaces.administration.shelves._shelf-form', ['shelf' => null, 'rackId' => null, 'nextOrder' => 0])
            </details>
        </section>
    @endif
</x-app-shell>
