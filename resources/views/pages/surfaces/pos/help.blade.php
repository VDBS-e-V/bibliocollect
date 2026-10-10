<x-app-shell surface="pos" title="Hilfe">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Hilfe"
        :lead="'Kurze Anleitungen für den Alltag in der Bibliothek: '.$total.' Artikel. Suche nach einem Stichwort oder wähle einen Bereich.'"
    />

    <form method="get" action="{{ route('pos.help') }}" class="bc-help-search" role="search" aria-label="Hilfe durchsuchen">
        <div class="bc-help-search__row">
            <x-ui.input label="Suche in der Hilfe" name="q" id="help-query" type="search" :value="$query" placeholder="zum Beispiel: Ausweis verloren, Etikett, Vormerkung" autocomplete="off" />
            <x-ui.button type="submit">Suchen</x-ui.button>
        </div>
        <div class="bc-help-search__filters">
            <x-ui.select label="Bereich" name="bereich" id="help-area" data-auto-submit>
                <option value="">Alle Bereiche</option>
                @foreach ($areas as $key => $label)
                    <option value="{{ $key }}" @selected($area === $key)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select label="Für wen" name="rolle" id="help-role" data-auto-submit>
                <option value="">Alle Rollen</option>
                @foreach ($roles as $key => $label)
                    <option value="{{ $key }}" @selected($role === $key)>{{ $label }}</option>
                @endforeach
            </x-ui.select>
            @if ($filtered)
                <a href="{{ route('pos.help') }}" class="bc-help-search__reset">Alles zurücksetzen</a>
            @endif
        </div>
    </form>

    @if ($filtered)
        <section class="bc-content-section" aria-labelledby="help-results-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="help-results-heading">Treffer</h2>
                <span>{{ count($hits) }}</span>
            </div>

            @if ($hits === [])
                <p class="bc-section-copy">Dazu gibt es keinen Artikel. Versuche ein anderes Wort oder entferne einen Filter. Steht etwas Wichtiges nicht in der Hilfe, sag es uns (Artikel „Fehler melden und Wünsche äußern“).</p>
            @else
                <ul class="bc-help-list">
                    @foreach ($hits as $hit)
                        <li class="bc-help-card">
                            <a class="bc-help-card__title" href="{{ route('pos.help.show', ['topic' => $hit['article']['slug'], 'q' => $query ?: null]) }}">{{ $hit['article']['title'] }}</a>
                            <span class="bc-help-card__meta">
                                <x-ui.badge variant="neutral">{{ $areas[$hit['article']['area']] }}</x-ui.badge>
                                @foreach ($hit['article']['roles'] as $roleKey)<x-ui.badge variant="neutral">{{ $roles[$roleKey] }}</x-ui.badge>@endforeach
                            </span>
                            <p>{{ $hit['article']['summary'] }}</p>
                            @if ($hit['snippet'])<p class="bc-help-card__snippet">{{ $hit['snippet'] }}</p>@endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @else
        @foreach ($grouped as $areaKey => $articles)
            <section class="bc-content-section" aria-labelledby="help-area-{{ $areaKey }}">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="help-area-{{ $areaKey }}">{{ $areas[$areaKey] }}</h2>
                    <span>{{ count($articles) }}</span>
                </div>
                <ul class="bc-help-list">
                    @foreach ($articles as $article)
                        <li class="bc-help-card">
                            <a class="bc-help-card__title" href="{{ route('pos.help.show', ['topic' => $article['slug']]) }}">{{ $article['title'] }}</a>
                            <span class="bc-help-card__meta">
                                @foreach ($article['roles'] as $roleKey)<x-ui.badge variant="neutral">{{ $roles[$roleKey] }}</x-ui.badge>@endforeach
                            </span>
                            <p>{{ $article['summary'] }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    @endif
</x-app-shell>
