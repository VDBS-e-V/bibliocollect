<x-app-shell surface="administration" title="Regalbretter">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Regalbretter"
        lead="Die Regalbretter, aus denen beim Einsortieren der Standort eines Exemplars gewählt wird. Jedes Brett gehört zu einem oder mehreren Themenbereichen, danach macht das System beim Einsortieren einen Vorschlag."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
    </div>

    @if (session('shelf_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('shelf_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="new-shelf-heading">
        <div class="bc-section-heading"><h2 id="new-shelf-heading">Regalbrett hinzufügen</h2></div>
        <form method="post" action="{{ route('administration.shelves.store') }}" class="bc-audit-filter">
            @csrf
            <x-ui.input label="Bezeichnung" name="code" id="new-shelf-code" :value="old('code')" maxlength="40" required hint="Das steht später am Exemplar, zum Beispiel „R3-B2“." />
            <x-ui.input label="Beschriftung am Regalbrett" name="label" id="new-shelf-label" :value="old('label')" maxlength="120" hint="Was auf dem Brett steht, zum Beispiel „Fantasy ab 10 Jahren“." />
            <x-ui.input label="Reihenfolge" name="sort_order" id="new-shelf-order" type="number" min="0" max="9999" :value="old('sort_order', $nextOrder)" />
            <details class="bc-shelf__topics">
                <summary>Themenbereiche für den Vorschlag beim Einsortieren</summary>
                @include('pages.surfaces.administration.shelves._topic-checklist', ['topicGroups' => $topicGroups, 'selected' => array_map('strval', (array) old('topics', []))])
            </details>
            <x-ui.button type="submit">Hinzufügen</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="shelves-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="shelves-heading">Alle Regalbretter</h2>
            <span>{{ $shelves->count() }}</span>
        </div>

        @if ($shelves->isEmpty())
            <p class="bc-section-copy">Noch kein Regalbrett angelegt. Ohne Regalbrett kann beim Erfassen kein Standort gewählt werden.</p>
        @else
            <ul class="bc-shelf-list">
                @foreach ($shelves as $shelf)
                    @php
                        $copies = (int) ($counts[$shelf->code] ?? 0);
                    @endphp
                    <li class="bc-shelf {{ $shelf->is_active ? '' : 'bc-shelf--off' }}">
                        <form method="post" action="{{ route('administration.shelves.update', ['shelfId' => $shelf->getKey()]) }}" class="bc-shelf__form">
                            @csrf
                            @method('PATCH')
                            <x-ui.input label="Bezeichnung" name="code" id="code-{{ $shelf->getKey() }}" :value="$shelf->code" maxlength="40" required />
                            <x-ui.input label="Beschriftung" name="label" id="label-{{ $shelf->getKey() }}" :value="$shelf->label" maxlength="120" />
                            <x-ui.input label="Reihenfolge" name="sort_order" id="order-{{ $shelf->getKey() }}" type="number" min="0" max="9999" :value="$shelf->sort_order" />
                            <label class="bc-checkbox-line"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked($shelf->is_active)> Auswählbar</label>
                            <details class="bc-shelf__topics">
                                <summary>Themenbereiche ({{ $shelf->topics->count() }})</summary>
                                @include('pages.surfaces.administration.shelves._topic-checklist', ['topicGroups' => $topicGroups, 'selected' => $shelf->topics->map(fn ($topic): string => (string) $topic->getKey())->all()])
                            </details>
                            <x-ui.button type="submit" variant="secondary">Speichern</x-ui.button>
                        </form>
                        <div class="bc-shelf__meta">
                            @if ($shelf->topics->isNotEmpty())
                                <span>Themen: {{ $shelf->topics->pluck('name')->implode(', ') }}</span>
                            @endif
                            <span>{{ $copies }} {{ $copies === 1 ? 'Exemplar' : 'Exemplare' }}</span>
                            @if ($copies === 0)
                                <form method="post" action="{{ route('administration.shelves.destroy', ['shelfId' => $shelf->getKey()]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bc-intake-linkbutton" data-confirm="Regalbrett „{{ $shelf->code }}“ wirklich löschen?">Löschen</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="bc-section-copy">Wird die Bezeichnung geändert, ändert sich der Standort aller Exemplare auf diesem Brett mit. Ausgeschaltete Bretter stehen beim Erfassen nicht mehr zur Auswahl; Regalbretter mit Exemplaren lassen sich nicht löschen.</p>
        @endif
    </section>
</x-app-shell>
