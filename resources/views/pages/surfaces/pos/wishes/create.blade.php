<x-app-shell surface="pos" title="Buchwunsch erfassen">
    <x-ui.page-header
        kicker="Buchwünsche"
        title="Buchwunsch erfassen"
        lead="Mit der ISBN holt das System Titel und Autor:in. Die Person wählst du über die Namenssuche aus, persönliche Daten tippst du nicht ein."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.wishes.index') }}">← Zurück zu den Buchwünschen</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="post" action="{{ route('pos.wishes.store') }}" class="bc-wish-form" id="wish-create-form">
        @csrf

        <div class="bc-field">
            <label class="bc-field__label" for="wish-isbn">ISBN (wenn bekannt)</label>
            <div class="bc-wish-form__isbn">
                <input id="wish-isbn" class="bc-field__control" data-isbn-field name="isbn" type="text" inputmode="numeric" value="{{ old('isbn', request('isbn')) }}" autocomplete="off" aria-describedby="wish-isbn-status" autofocus>
                <button type="button" class="bc-wish-form__search" data-isbn-lookup="{{ route('public.wishes.lookup') }}" aria-label="ISBN nachschlagen und Titel vorschlagen lassen">
                    <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Zm5.5-2 5 5"/></svg>
                </button>
            </div>
            <p id="wish-isbn-status" data-isbn-status class="bc-wish-form__status" role="status" aria-live="polite"></p>
        </div>

        <x-ui.input label="Titel *" name="title" id="wish-title" :value="old('title', request('title'))" required maxlength="255" />
        <x-ui.input label="Autor:in" name="author" id="wish-author" :value="old('author', request('author'))" maxlength="255" />

        <div class="bc-field">
            <label class="bc-field__label" for="wish-note">Bemerkung</label>
            <textarea id="wish-note" class="bc-field__control" name="note" rows="3" maxlength="500">{{ old('note', request('note')) }}</textarea>
        </div>

        <fieldset class="bc-intake-fieldset">
            <legend>Für wen? (freiwillig)</legend>
            <p class="bc-section-copy">Mit einer Person sieht sie den Stand ihres Wunsches in ihrem Konto und bekommt Mails dazu. Ohne Person wird der Wunsch anonym erfasst.</p>

            <div class="bc-wish-person-search">
                <x-ui.input label="Person suchen (Name, Klasse oder Bibliotheksnummer)" name="person" id="wish-person" :value="old('person', $term)" autocomplete="off" />
                <x-ui.button type="submit" variant="secondary" formmethod="get" formaction="{{ route('pos.wishes.create') }}" formnovalidate>Person suchen</x-ui.button>
            </div>

            @if ($searched)
                @if ($matches->isEmpty())
                    <p class="bc-section-copy">Keine aktive Person gefunden. Prüfe die Schreibweise oder suche mit zwei oder mehr Buchstaben.</p>
                @else
                    <fieldset class="bc-wish-person-results">
                        <legend class="sr-only">Gefundene Personen, bitte eine auswählen</legend>
                        @foreach ($matches as $patron)
                            <label class="bc-wish-person-result" for="patron-{{ $patron->getKey() }}">
                                <input id="patron-{{ $patron->getKey() }}" type="radio" name="patron_id" value="{{ $patron->getKey() }}" @checked(old('patron_id', $selected) === (string) $patron->getKey() || $matches->count() === 1)>
                                <span><strong>{{ $patron->last_name }}, {{ $patron->first_name }}</strong>@if ($patron->schoolClass) · Klasse {{ $patron->schoolClass->name }}@endif <small class="bc-tabular">{{ $patron->library_number }}</small></span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
            @endif
        </fieldset>

        <div class="bc-intake-actions">
            <x-ui.button type="submit">Buchwunsch speichern</x-ui.button>
        </div>
    </form>
</x-app-shell>
