<x-app-shell surface="public" title="Erweiterte Katalogsuche">
    <div class="bc-context-actions">
        <a href="{{ route('public.catalog.index') }}">← Zurück zum Katalog</a>
    </div>

    <x-ui.page-header
        kicker="Öffentlicher Katalog"
        title="Erweiterte Suche"
        lead="Wenn du schon genauer weißt, was du suchst, kannst du mehrere Angaben miteinander kombinieren. Für eine Schulbibliothek halten wir die Auswahl bewusst übersichtlich."
    />

    <section class="bc-public-advanced-search" aria-labelledby="public-advanced-search-heading">
        <div class="bc-public-advanced-search__intro">
            <p class="bc-eyebrow">Genauer suchen</p>
            <h2 id="public-advanced-search-heading">Suchkriterien</h2>
            <p>Leere Felder werden ignoriert. Mehrere ausgefüllte Felder schränken die Treffer gemeinsam ein.</p>
        </div>

        <form method="get" action="{{ route('public.catalog.index') }}" class="bc-public-advanced-form" role="search">
            <div class="bc-public-advanced-form__wide">
                <x-ui.input
                    label="Freie Suche"
                    name="q"
                    type="search"
                    :value="$criteria->term"
                    placeholder="Zum Beispiel: Weltraum, Momo oder 978…"
                    autocomplete="off"
                />
            </div>

            <x-ui.input label="Titel" name="title" :value="$criteria->title" />
            <x-ui.input label="Autor:in / Verantwortliche" name="contributor" :value="$criteria->contributor" />
            <x-ui.input label="Schlagwort" name="subject" :value="$criteria->subject" placeholder="z. B. Freundschaft" />
            <x-ui.input label="ISBN / Kennung" name="identifier" :value="$criteria->identifier" />
            <x-ui.input label="Verlag" name="publisher" :value="$criteria->publisher" />
            <x-ui.input label="Thema / Klassifikation" name="topic" :value="$criteria->topic" placeholder="z. B. Dystopie" />

            <x-ui.input label="Erscheinungsjahr von" name="year_from" type="number" :value="$criteria->yearFrom" min="1000" max="2100" />
            <x-ui.input label="Erscheinungsjahr bis" name="year_to" type="number" :value="$criteria->yearTo" min="1000" max="2100" />

            <x-ui.select label="Medientyp" name="media_type">
                <option value="">Alle Medientypen</option>
                @foreach ($filterOptions['mediaTypes'] as $mediaType)
                    <option value="{{ $mediaType }}" @selected($criteria->mediaType === $mediaType)>
                        {{ $presenter->mediaTypeLabel($mediaType) }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Sprache" name="language_code">
                <option value="">Alle Sprachen</option>
                @foreach ($filterOptions['languageCodes'] as $languageCode)
                    <option value="{{ $languageCode }}" @selected($criteria->languageCode === $languageCode)>
                        {{ $presenter->languageLabel($languageCode) }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.select label="Sortierung" name="sort">
                <option value="title" @selected($criteria->sort === 'title')>Titel A–Z</option>
                <option value="title_desc" @selected($criteria->sort === 'title_desc')>Titel Z–A</option>
                <option value="year_desc" @selected($criteria->sort === 'year_desc')>Neuere Erscheinungsjahre zuerst</option>
                <option value="year_asc" @selected($criteria->sort === 'year_asc')>Ältere Erscheinungsjahre zuerst</option>
                <option value="recent" @selected($criteria->sort === 'recent')>Zuletzt im Katalog erfasst</option>
            </x-ui.select>

            <label class="bc-public-catalog-filter__check bc-public-advanced-form__wide" for="advanced-active-only">
                <input
                    id="advanced-active-only"
                    name="active_only"
                    type="checkbox"
                    value="1"
                    @checked($criteria->activeCopiesOnly)
                >
                <span>
                    <strong>Nur Titel mit aktiven Exemplaren</strong>
                    <small>Der aktuelle Ausleihstatus wird später zusätzlich durch Circulation berücksichtigt.</small>
                </span>
            </label>

            <div class="bc-action-row bc-public-advanced-form__wide">
                <x-ui.button type="submit">Treffer anzeigen</x-ui.button>
                <x-ui.button href="{{ route('public.catalog.advanced') }}" variant="secondary">Felder leeren</x-ui.button>
            </div>
        </form>
    </section>
</x-app-shell>
