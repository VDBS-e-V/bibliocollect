@php
    use App\Surfaces\Pos\Support\CatalogIntakeVocabulary;

    $roles = CatalogIntakeVocabulary::roles();
    $mediaTypes = CatalogIntakeVocabulary::mediaTypes();

    $contributorRows = old('contributors', $details->contributors);
    $contributorRows = is_array($contributorRows) ? array_values($contributorRows) : [];
    // Mindestens drei Zeilen und immer eine freie Zeile hinter den befüllten, damit weitere Personen ohne Skript ergänzt werden können.
    $contributorRowTarget = min(12, max(3, count($contributorRows) + 1));
    while (count($contributorRows) < $contributorRowTarget) {
        $contributorRows[] = ['name' => '', 'role' => 'author', 'gnd_id' => null];
    }

    $currentMediaType = old('media_type', $details->mediaType);
    $backRoute = $hasHits ? route('pos.catalog.intake.matches') : route('pos.catalog.intake.identify');
@endphp

<x-app-shell surface="pos" title="Titel und Ausgabe">
    <x-ui.page-header
        kicker="Medium erfassen"
        title="Titel & Ausgabe"
        :lead="'Inventarnummer '.$barcode"
    />

    <x-catalog.intake-steps :current="4" />

    @if (session('intake_notice'))
        <x-ui.alert :variant="session('intake_notice_variant', 'info')" title="Hinweis">{{ session('intake_notice') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">Bitte prüfe die markierten Eingaben.</x-ui.alert>
    @endif

    @if ($provenance)
        <p class="bc-intake-note">
            Datenquelle: <strong>{{ \App\Modules\Catalog\DTOs\BibliographicRecord::label($provenance->source) }}</strong>
            @if ($provenance->permalink)
                · <a href="{{ $provenance->permalink }}" rel="noopener noreferrer" target="_blank">Datensatz {{ $provenance->recordId }}</a>
            @endif
            – bitte prüfen und bei Bedarf ändern.
        </p>
        @if ($provenance->source !== 'dnb')
            <x-ui.alert title="Angaben nicht aus der DNB">Diese Angaben stammen aus {{ \App\Modules\Catalog\DTOs\BibliographicRecord::label($provenance->source) }} und sind weniger verlässlich als die der Deutschen Nationalbibliothek. Bitte Titel, Verlag, Jahr und Namen besonders genau prüfen.</x-ui.alert>
        @endif
    @endif

    <form method="post" action="{{ route('pos.catalog.intake.details.store') }}" class="bc-intake-form">
        @csrf

        <fieldset class="bc-intake-fieldset">
            <legend>Titel</legend>
            <x-ui.input
                label="Haupttitel"
                name="preferred_title"
                :value="old('preferred_title', $details->preferredTitle)"
                :error="$errors->first('preferred_title')"
                required
                maxlength="500"
            />
            <x-ui.input
                label="Untertitel"
                name="subtitle"
                :value="old('subtitle', $details->subtitle)"
                :error="$errors->first('subtitle')"
                maxlength="500"
            />
            <x-ui.input
                label="Verantwortlichkeitsangabe"
                name="responsibility_statement"
                :value="old('responsibility_statement', $details->responsibilityStatement)"
                hint="Wie auf dem Titelblatt, z. B. „hrsg. von Hans-Joachim Gelberg“."
                :error="$errors->first('responsibility_statement')"
            />
        </fieldset>

        <fieldset class="bc-intake-fieldset">
            <legend>Verantwortliche</legend>
            <p class="bc-intake-note">
                Name als „Nachname, Vorname“. Leere Zeilen werden ignoriert. Personen mit GND-ID werden im Katalog nur einmal geführt.
            </p>

            <div class="bc-intake-contributors">
                @foreach ($contributorRows as $index => $row)
                    @php
                        $rowRole = (string) ($row['role'] ?? 'author');
                        $nameError = $errors->first('contributors.'.$index.'.name');
                        $roleError = $errors->first('contributors.'.$index.'.role');
                    @endphp
                    <div class="bc-intake-contributor">
                        <x-ui.input
                            :label="'Person '.($index + 1)"
                            :id="'contributor-name-'.$index"
                            :name="'contributors['.$index.'][name]'"
                            :value="$row['name'] ?? ''"
                            :error="$nameError"
                            maxlength="200"
                        />
                        <x-ui.select
                            label="Rolle"
                            :id="'contributor-role-'.$index"
                            :name="'contributors['.$index.'][role]'"
                            :error="$roleError"
                        >
                            @foreach ($roles as $key => $label)
                                <option value="{{ $key }}" @selected($rowRole === $key)>{{ $label }}</option>
                            @endforeach
                            @if (! isset($roles[$rowRole]))
                                <option value="{{ $rowRole }}" selected>{{ $rowRole }}</option>
                            @endif
                        </x-ui.select>
                        <input type="hidden" name="contributors[{{ $index }}][gnd_id]" value="{{ $row['gnd_id'] ?? '' }}">
                    </div>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="bc-intake-fieldset">
            <legend>Veröffentlichung</legend>
            <div class="bc-intake-fieldset__grid">
                <x-ui.input
                    label="Verlag"
                    name="publisher_name"
                    :value="old('publisher_name', $details->publisherName)"
                    :error="$errors->first('publisher_name')"
                    maxlength="255"
                />
                <x-ui.input
                    label="Verlagsort"
                    name="publication_place"
                    :value="old('publication_place', $details->publicationPlace)"
                    :error="$errors->first('publication_place')"
                    maxlength="255"
                />
                <x-ui.input
                    label="Erscheinungsjahr"
                    name="publication_year"
                    type="number"
                    min="1000"
                    max="2100"
                    :value="old('publication_year', $details->publicationYear)"
                    :error="$errors->first('publication_year')"
                />
                <x-ui.input
                    label="Auflage / Ausgabe"
                    name="edition_statement"
                    :value="old('edition_statement', $details->editionStatement)"
                    hint="z. B. „2. Auflage“ oder „Studienausgabe“."
                    :error="$errors->first('edition_statement')"
                    maxlength="255"
                />
                <x-ui.input
                    label="ISBN"
                    name="isbn"
                    :value="old('isbn', $details->isbn)"
                    :error="$errors->first('isbn')"
                    maxlength="32"
                />
                <x-ui.input
                    label="Umfang"
                    name="physical_extent"
                    :value="old('physical_extent', $details->physicalExtent)"
                    hint="z. B. „351 Seiten“."
                    :error="$errors->first('physical_extent')"
                    maxlength="255"
                />
            </div>
        </fieldset>

        <fieldset class="bc-intake-fieldset">
            <legend>Einordnung</legend>
            <div class="bc-intake-fieldset__grid bc-intake-fieldset__grid--three">
                <x-ui.select label="Medientyp" name="media_type" :error="$errors->first('media_type')">
                    <option value="">– nicht angegeben –</option>
                    @foreach ($mediaTypes as $key => $label)
                        <option value="{{ $key }}" @selected($currentMediaType === $key)>{{ $label }}</option>
                    @endforeach
                    @if ($currentMediaType && ! isset($mediaTypes[$currentMediaType]))
                        <option value="{{ $currentMediaType }}" selected>{{ $currentMediaType }}</option>
                    @endif
                </x-ui.select>
                <x-ui.input
                    label="Sprache"
                    name="language_code"
                    list="intake-language-options"
                    :value="old('language_code', $details->languageCode)"
                    hint="Sprachcode, z. B. de, en, fr."
                    :error="$errors->first('language_code')"
                    maxlength="16"
                />
                <x-ui.input
                    label="Originalsprache"
                    name="original_language_code"
                    list="intake-language-options"
                    :value="old('original_language_code', $details->originalLanguageCode)"
                    :error="$errors->first('original_language_code')"
                    maxlength="16"
                />
            </div>
            <datalist id="intake-language-options">
                @foreach (['de' => 'Deutsch', 'en' => 'Englisch', 'fr' => 'Französisch', 'es' => 'Spanisch', 'it' => 'Italienisch', 'tr' => 'Türkisch', 'ar' => 'Arabisch', 'ru' => 'Russisch', 'pl' => 'Polnisch'] as $code => $name)
                    <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
            </datalist>

            <div class="bc-intake-fieldset__grid">
                <x-ui.input
                    label="Reihe"
                    name="series_statement"
                    :value="old('series_statement', $details->seriesStatement)"
                    :error="$errors->first('series_statement')"
                    maxlength="500"
                />
                @php
                    $topicOptions = app(\App\Modules\Catalog\Services\CatalogTopicOptions::class)->forSelect();
                    $topicGroups = app(\App\Modules\Catalog\Services\CatalogTopicOptions::class)->grouped();
                    $currentTopic = (string) old('local_classification', $details->localClassification ?? '');
                @endphp
                <x-ui.select label="Themenbereich" name="local_classification" :error="$errors->first('local_classification')">
                    <option value="">– nicht angegeben –</option>
                    @foreach ($topicGroups as $group)
                        <optgroup label="{{ $group['root'] }}">
                            @foreach ($group['options'] as $name => $display)
                                <option value="{{ $name }}" @selected($currentTopic === $name)>{{ $display }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                    @if ($currentTopic !== '' && ! isset($topicOptions[$currentTopic]))
                        <option value="{{ $currentTopic }}" selected>{{ $currentTopic }}</option>
                    @endif
                </x-ui.select>
                <x-ui.input
                    label="Zielgruppe"
                    name="target_audience"
                    :value="old('target_audience', $details->targetAudience)"
                    hint="Freitext, z. B. „ab 13 Jahre“. Dies sperrt keine Ausleihe."
                    :error="$errors->first('target_audience')"
                    maxlength="255"
                />
                <x-ui.input
                    label="Mindestalter für die Ausleihe"
                    name="minimum_age"
                    type="number"
                    min="0"
                    max="18"
                    :value="old('minimum_age', $details->minimumAge)"
                    hint="Nur ausfüllen, wenn die Ausleihe wirklich altersbeschränkt sein soll."
                    :error="$errors->first('minimum_age')"
                />
            </div>

            <div class="bc-field">
                <label class="bc-field__label" for="subject_keywords">Schlagwörter</label>
                <p class="bc-field__hint" id="subject_keywords-hint">Mit Komma getrennt.</p>
                <textarea
                    id="subject_keywords"
                    name="subject_keywords"
                    rows="3"
                    class="bc-field__control @error('subject_keywords') bc-field__control--error @enderror"
                    aria-describedby="subject_keywords-hint"
                >{{ old('subject_keywords', $details->subjectKeywords) }}</textarea>
                @error('subject_keywords')<p class="bc-field__error"><strong>Fehler:</strong> {{ $message }}</p>@enderror
            </div>

            <div class="bc-field">
                <label class="bc-field__label" for="summary">Zusammenfassung</label>
                @if ($summarySource)
                    <p class="bc-field__hint">Automatisch von {{ $summarySource }} vorgeschlagen. Bitte lesen und bei Bedarf kürzen oder löschen.</p>
                @endif
                <textarea
                    id="summary"
                    name="summary"
                    rows="5"
                    class="bc-field__control @error('summary') bc-field__control--error @enderror"
                >{{ old('summary', $details->summary) }}</textarea>
                @error('summary')<p class="bc-field__error"><strong>Fehler:</strong> {{ $message }}</p>@enderror
            </div>
        </fieldset>

        <div class="bc-intake-actions">
            <a href="{{ $backRoute }}">← Zurück</a>
            <x-ui.button type="submit">Weiter zum Exemplar</x-ui.button>
        </div>
    </form>
</x-app-shell>
