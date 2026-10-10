@php
    use App\Modules\Catalog\Enums\ShelfSectionKind;

    $oneGroup = $groups->count() === 1;
    $filtering = $match !== null;
    $visible = static fn ($shelf): bool => $match === null || in_array((string) $shelf->getKey(), $match, true);
    $shown = static function ($shelves) use ($visible) {
        return $shelves->filter($visible)->values();
    };
@endphp
<x-app-shell surface="administration" title="Regale und Regalbretter">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Regale und Regalbretter"
        lead="Jedes Regalbrett liegt in einem Regal, das Regal in einem Bereich und der Bereich in einer Bereichsgruppe. Beim Einsortieren schlägt das System anhand des Themas die passenden Bretter vor."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        <a href="{{ route('administration.topics.index') }}">Themenbereiche</a>
        <a href="{{ route('administration.shelves.dashboard') }}">Regal-Dashboard</a>
        <a href="{{ route('administration.classification-import.index') }}">Themen und Regalbretter importieren</a>
        <a href="{{ route('administration.shelves.labels') }}"><strong>Etiketten für Regalbretter drucken</strong></a>
    </div>

    @if (session('shelf_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('shelf_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <p class="bc-loc-legend">
        <span class="bc-loc-legend__step"><i class="bc-dot bc-dot--group"></i>Bereichsgruppe <small>I</small></span>
        <span aria-hidden="true">›</span>
        <span class="bc-loc-legend__step"><i class="bc-dot bc-dot--area"></i>Bereich <small>A</small></span>
        <span aria-hidden="true">›</span>
        <span class="bc-loc-legend__step"><i class="bc-dot bc-dot--rack"></i>Regal <small>1</small></span>
        <span aria-hidden="true">›</span>
        <span class="bc-loc-legend__step"><i class="bc-dot bc-dot--board"></i>Regalbrett <small>a</small></span>
        <span class="bc-loc-legend__result">= Standort <span class="bc-loc-code">I. A 1 a</span></span>
    </p>

    <form method="get" action="{{ route('administration.shelves.index') }}" class="bc-audit-filter" role="search">
        <x-ui.input label="Suchen (Standort, Beschriftung, Thema, Name von Regal oder Bereich)" name="q" id="shelf-search" :value="$term" />
        <label class="bc-checkbox-line"><input type="checkbox" name="ohne_thema" value="1" @checked($withoutTopic)> nur Regalbretter ohne Thema</label>
        <x-ui.button type="submit" variant="secondary">Suchen</x-ui.button>
        @if ($filtering)
            <a href="{{ route('administration.shelves.index') }}">Suche zurücksetzen</a>
        @endif
    </form>
    @if ($filtering)
        <p class="bc-section-copy"><strong>{{ count($match) }}</strong> {{ count($match) === 1 ? 'Regalbrett passt' : 'Regalbretter passen' }} von {{ $shelfTotal }}.</p>
    @endif

    <div class="bc-acc-tools">
        <button type="button" class="bc-intake-linkbutton" data-acc-toggle="open">Alles aufklappen</button>
        <button type="button" class="bc-intake-linkbutton" data-acc-toggle="close">Alles zuklappen</button>
    </div>

    @forelse ($groups as $group)
        @php
            $boardTotal = $group->children->sum(fn ($area) => $area->children->sum(fn ($rack) => $shown($rack->shelves)->count()));
        @endphp
        @continue($filtering && $boardTotal === 0)
        <details class="bc-acc bc-acc--group" data-acc @if ($oneGroup || $filtering) open @endif>
            <summary>
                <i class="bc-dot bc-dot--group"></i>
                <span class="bc-acc__kind">Bereichsgruppe</span>
                <h2 class="bc-acc__title">{{ $group->code }}<span class="bc-acc__name">{{ $group->name ?: 'noch ohne Namen' }}</span></h2>
                <span class="bc-acc__meta">{{ $group->children->count() }} {{ $group->children->count() === 1 ? 'Bereich' : 'Bereiche' }} · {{ $boardTotal }} Regalbretter</span>
            </summary>
            <div class="bc-acc__body">
                @if ($group->description)<p class="bc-loc__note">{{ $group->description }}</p>@endif
                <details class="bc-loc__edit">
                    <summary>Bereichsgruppe bearbeiten</summary>
                    @include('pages.surfaces.administration.shelves._section-form', ['section' => $group, 'kind' => ShelfSectionKind::Group, 'parentId' => null])
                    <form method="post" action="{{ route('administration.sections.destroy', ['sectionId' => $group->getKey()]) }}">
                        @csrf @method('DELETE')
                        @if ($group->children->isEmpty())
                            <button type="submit" class="bc-intake-linkbutton" data-confirm="Bereichsgruppe „{{ $group->code }}“ wirklich löschen?" data-confirm-label="Löschen">Bereichsgruppe löschen</button>
                        @else
                            <input type="hidden" name="cascade" value="1">
                            <input type="hidden" name="release_copies" value="1">
                            <button type="submit" class="bc-intake-linkbutton" data-confirm="Bereichsgruppe „{{ $group->code }}“ mit ALLEM darunter löschen (Bereiche, Regale und Regalbretter)? Exemplare auf diesen Regalbrettern verlieren ihren Standort und stehen wieder zum Einsortieren an. Die Medien bleiben." data-confirm-label="Alles löschen">Bereichsgruppe mit allem darunter löschen</button>
                        @endif
                    </form>
                </details>

                @foreach ($group->children as $area)
                    @php
                        $areaBoards = $area->children->sum(fn ($rack) => $shown($rack->shelves)->count());
                    @endphp
                    @continue($filtering && $areaBoards === 0)
                    <details class="bc-acc bc-acc--area" data-acc open>
                        <summary>
                            <i class="bc-dot bc-dot--area"></i>
                            <span class="bc-acc__kind">Bereich</span>
                            <h3 class="bc-acc__title">{{ $group->code }} › {{ $area->code }}<span class="bc-acc__name">{{ $area->name ?: 'noch ohne Namen' }}</span></h3>
                            <span class="bc-acc__meta">{{ $area->children->count() }} {{ $area->children->count() === 1 ? 'Regal' : 'Regale' }} · {{ $areaBoards }} Regalbretter</span>
                        </summary>
                        <div class="bc-acc__body">
                            @if ($area->description)<p class="bc-loc__note">{{ $area->description }}</p>@endif
                            <details class="bc-loc__edit">
                                <summary>Bereich bearbeiten</summary>
                                @include('pages.surfaces.administration.shelves._section-form', ['section' => $area, 'kind' => ShelfSectionKind::Area, 'parentId' => $group->getKey()])
                                <form method="post" action="{{ route('administration.sections.destroy', ['sectionId' => $area->getKey()]) }}">
                                    @csrf @method('DELETE')
                                    @if ($area->children->isEmpty())
                                        <button type="submit" class="bc-intake-linkbutton" data-confirm="Bereich „{{ $area->code }}“ wirklich löschen?" data-confirm-label="Löschen">Bereich löschen</button>
                                    @else
                                        <input type="hidden" name="cascade" value="1">
                                        <input type="hidden" name="release_copies" value="1">
                                        <button type="submit" class="bc-intake-linkbutton" data-confirm="Bereich „{{ $area->code }}“ mit ALLEM darunter löschen (Regale und Regalbretter)? Exemplare auf diesen Regalbrettern verlieren ihren Standort und stehen wieder zum Einsortieren an. Die Medien bleiben." data-confirm-label="Alles löschen">Bereich mit allem darunter löschen</button>
                                    @endif
                                </form>
                            </details>

                            @foreach ($area->children as $rack)
                                @php
                                    $rackShelves = $shown($rack->shelves);
                                    $rackUsed = $rackShelves->sum(fn ($shelf) => (int) ($counts[$shelf->code] ?? 0));
                                    $rackCapacity = $rackShelves->sum(fn ($shelf) => (int) $shelf->capacity);
                                    $rackSummary = $rackShelves->count().' '.($rackShelves->count() === 1 ? 'Regalbrett' : 'Regalbretter').' · '.$rackUsed.' '.($rackUsed === 1 ? 'Buch' : 'Bücher').($rackCapacity > 0 ? ' von '.$rackCapacity.' Plätzen' : '');
                                @endphp
                                @continue($filtering && $rackShelves->isEmpty())
                                <details class="bc-acc bc-acc--rack" data-acc @if ($filtering) open @endif>
                                    <summary>
                                        <i class="bc-dot bc-dot--rack"></i>
                                        <span class="bc-acc__kind">Regal</span>
                                        <h4 class="bc-acc__title">{{ $group->code }} › {{ $area->code }} › {{ $rack->code }}<span class="bc-acc__name">{{ $rack->name ?: 'noch ohne Namen' }}</span></h4>
                                        <span class="bc-acc__meta">{{ $rackSummary }}</span>
                                    </summary>
                                    <div class="bc-acc__body">
                                        @if ($rack->description)<p class="bc-loc__note">{{ $rack->description }}</p>@endif
                                        <details class="bc-loc__edit">
                                            <summary>Regal bearbeiten</summary>
                                            @include('pages.surfaces.administration.shelves._section-form', ['section' => $rack, 'kind' => ShelfSectionKind::Rack, 'parentId' => $area->getKey()])
                                            <form method="post" action="{{ route('administration.sections.destroy', ['sectionId' => $rack->getKey()]) }}">
                                                @csrf @method('DELETE')
                                                @if ($rack->shelves->isEmpty())
                                                    <button type="submit" class="bc-intake-linkbutton" data-confirm="Regal „{{ $rack->code }}“ wirklich löschen?" data-confirm-label="Löschen">Regal löschen</button>
                                                @else
                                                    <input type="hidden" name="cascade" value="1">
                                                    <input type="hidden" name="release_copies" value="1">
                                                    <button type="submit" class="bc-intake-linkbutton" data-confirm="Regal „{{ $rack->code }}“ mit allen {{ $rack->shelves->count() }} Regalbrettern löschen? Exemplare auf diesen Regalbrettern verlieren ihren Standort und stehen wieder zum Einsortieren an. Die Medien bleiben." data-confirm-label="Alles löschen">Regal mit allen {{ $rack->shelves->count() }} Regalbrettern löschen</button>
                                                @endif
                                            </form>
                                        </details>

                                        @if ($rackShelves->isNotEmpty())
                                            <ul class="bc-board-list">
                                                @foreach ($rackShelves as $shelf)
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
                                    </div>
                                </details>
                            @endforeach

                            <details class="bc-loc__add">
                                <summary>Regal in Bereich {{ $area->code }} hinzufügen</summary>
                                @include('pages.surfaces.administration.shelves._section-form', ['section' => null, 'kind' => ShelfSectionKind::Rack, 'parentId' => $area->getKey(), 'nextOrder' => ((int) $area->children->max('sort_order')) + 1, 'hint' => 'Zum Beispiel „1“ oder „2“.'])
                            </details>
                        </div>
                    </details>
                @endforeach

                <details class="bc-loc__add">
                    <summary>Bereich in Bereichsgruppe {{ $group->code }} hinzufügen</summary>
                    @include('pages.surfaces.administration.shelves._section-form', ['section' => null, 'kind' => ShelfSectionKind::Area, 'parentId' => $group->getKey(), 'nextOrder' => ((int) $group->children->max('sort_order')) + 1, 'hint' => 'Zum Beispiel „A“ oder „B“.'])
                </details>
            </div>
        </details>
    @empty
        <x-ui.alert title="Noch keine Regale">Lege unten zuerst eine Bereichsgruppe an, dann Bereich, Regal und Regalbretter. Regalbretter mit altem Standort-Code („I. A 1 a“) kannst du auch automatisch zuordnen lassen.</x-ui.alert>
    @endforelse

    <details class="bc-loc__add bc-loc__add--top" @if ($groups->isEmpty()) open @endif>
        <summary>Neue Bereichsgruppe hinzufügen</summary>
        @include('pages.surfaces.administration.shelves._section-form', ['section' => null, 'kind' => ShelfSectionKind::Group, 'parentId' => null, 'nextOrder' => ((int) $groups->max('sort_order')) + 1, 'hint' => 'Zum Beispiel „I“ oder „II“.'])
    </details>

    @php
        $looseShown = $shown($unassigned);
    @endphp
    @if ((! $filtering && ($unassigned->isNotEmpty() || $shelfTotal === 0)) || ($filtering && $looseShown->isNotEmpty()))
        <details class="bc-acc bc-acc--loose" data-acc open>
            <summary>
                <i class="bc-dot bc-dot--board"></i>
                <span class="bc-acc__kind">Ohne Regal</span>
                <h2 class="bc-acc__title">Regalbretter ohne Regal<span class="bc-acc__name">noch keinem Regal zugeordnet</span></h2>
                <span class="bc-acc__meta">{{ $looseShown->count() }}</span>
            </summary>
            <div class="bc-acc__body">
                <p class="bc-loc__note">Diese Regalbretter haben einen freien Standort-Code. Wähle bei „bearbeiten“ ein Regal, damit sie in den Aufbau passen. Codes der Form „I. A 1 a“ lassen sich auch automatisch zuordnen.</p>
                @if ($looseShown->isNotEmpty())
                    <form method="post" action="{{ route('administration.sections.assign') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary">Anhand des Standort-Codes zuordnen</x-ui.button>
                    </form>
                    <ul class="bc-board-list">
                        @foreach ($looseShown as $shelf)
                            @include('pages.surfaces.administration.shelves._shelf-row', ['shelf' => $shelf])
                        @endforeach
                    </ul>
                @endif
                <details class="bc-loc__add">
                    <summary>Regalbrett ohne Regal hinzufügen</summary>
                    @include('pages.surfaces.administration.shelves._shelf-form', ['shelf' => null, 'rackId' => null, 'nextOrder' => 0])
                </details>
            </div>
        </details>
    @endif
</x-app-shell>
