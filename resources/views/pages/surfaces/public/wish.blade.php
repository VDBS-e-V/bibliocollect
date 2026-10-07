<x-app-shell surface="public" title="Buchwunsch erfassen">
    <div class="bc-wish-form-page">
        <h1 class="bc-wish-form-page__title">Buchwunsch erfassen</h1>
        <p>Dir fehlt ein Buch in der Bibliothek? Trag es hier ein. Pflichtfeld ist der Titel. Das Formular kann jede:r nutzen, eine Anmeldung ist nicht nötig.</p>

        @if (session('wish_success'))
            <x-ui.alert variant="success" title="Erledigt">{{ session('wish_success') }}</x-ui.alert>
        @endif

        @if ($errors->any())
            <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
        @endif

        @if ($patron)
            <x-ui.alert title="Du bist angemeldet">
                Dein Wunsch wird deinem Ausleihkonto zugeordnet. Den Stand siehst du in <a href="{{ route('portal.wishes.index') }}">Mein Konto</a>.
            </x-ui.alert>
        @endif

        <form method="post" action="{{ route('public.wishes.store') }}" class="bc-wish-form">
            @csrf

            <div class="bc-field">
                <label class="bc-field__label" for="wish-isbn">ISBN (wenn bekannt)</label>
                <div class="bc-wish-form__isbn">
                    <input id="wish-isbn" class="bc-field__control" data-isbn-field name="isbn" type="text" inputmode="numeric" value="{{ old('isbn', $prefill['isbn']) }}" autocomplete="off" aria-describedby="wish-isbn-status">
                    <button type="button" class="bc-wish-form__search" data-isbn-lookup="{{ route('public.wishes.lookup') }}" aria-label="ISBN nachschlagen und Titel vorschlagen lassen">
                        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Zm5.5-2 5 5"/></svg>
                    </button>
                </div>
                <p id="wish-isbn-status" data-isbn-status class="bc-wish-form__status" role="status" aria-live="polite"></p>
            </div>

            <x-ui.input label="Titel *" name="title" id="wish-title" :value="old('title', $prefill['title'])" required maxlength="255" />
            <x-ui.input label="Autor:in" name="author" id="wish-author" :value="old('author')" maxlength="255" />

            <div class="bc-field">
                <label class="bc-field__label" for="wish-note">Bemerkung</label>
                <textarea id="wish-note" class="bc-field__control" name="note" rows="4" maxlength="500">{{ old('note') }}</textarea>
            </div>

            @unless ($patron)
                <x-ui.input label="Name (optional)" name="contact_name" id="wish-contact-name" :value="old('contact_name')" maxlength="120" autocomplete="name" />
                <x-ui.input label="E-Mail (optional)" name="contact_email" id="wish-contact-email" type="email" :value="old('contact_email')" maxlength="190" autocomplete="email" hint="Nur wenn du eine Rückmeldung möchtest. Wir nutzen die Angaben nur für diesen Wunsch und löschen sie nach der Aufbewahrungsfrist." />
            @endunless

            {{-- Falle für Programme: Menschen sehen und füllen dieses Feld nicht aus. --}}
            <div class="bc-visually-hidden" aria-hidden="true">
                <label for="wish-website">Bitte leer lassen</label>
                <input id="wish-website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <button type="submit" class="bc-wish-form__submit">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 21s-7.5-4.6-9.6-9.2C.9 8.5 2.6 5 6 5c2 0 3.4 1 4 2.3h4C14.600 6 16 5 18 5c3.400 0 5.100 3.500 3.600 6.800C19.500 16.400 12 21 12 21Z"/></svg>
                Buchwunsch speichern
            </button>
        </form>

        <p class="bc-section-copy"><a href="{{ route('public.catalog.index') }}">Erst im Katalog nachsehen, ob es das Buch schon gibt</a></p>
    </div>
</x-app-shell>
