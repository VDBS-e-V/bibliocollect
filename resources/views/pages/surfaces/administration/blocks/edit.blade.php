<x-app-shell surface="administration" :title="$definition['title']">
    <x-ui.page-header kicker="Textbaustein" :title="$definition['title']" :lead="$definition['place']" />

    <div class="bc-context-actions">
        <a href="{{ route('administration.blocks.index') }}">← Zurück zu den Textbausteinen</a>
    </div>

    @if (session('school_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('school_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="block-edit-heading">
        <div class="bc-section-heading"><h2 id="block-edit-heading">Text bearbeiten</h2></div>
        <form method="post" action="{{ route('administration.blocks.update', ['key' => $key]) }}" class="bc-calendar-form">
            @csrf
            @method('PATCH')
            <div class="bc-field">
                <label class="bc-field__label" for="body">Text</label>
                <p class="bc-field__hint" id="body-hint">Halte den Hinweis kurz. Erlaubt sind Absätze, fett, kursiv, Listen und Links.</p>
                <textarea id="body" name="body" rows="10" class="bc-field__control" aria-describedby="body-hint" data-rich-text data-license-key="{{ config('content.editor_license_key') }}">{{ old('body', $editorHtml) }}</textarea>
            </div>

            <div class="bc-reading-fields">
                <x-ui.input label="Sichtbar ab" name="visible_from" id="block-from" type="date" :value="old('visible_from', $block?->visible_from?->toDateString())" hint="Leer lassen für sofort." />
                <x-ui.input label="Sichtbar bis (einschließlich)" name="visible_until" id="block-until" type="date" :value="old('visible_until', $block?->visible_until?->toDateString())" hint="Leer lassen für unbefristet." />
            </div>

            <label class="bc-public-catalog-filter__check">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $block?->is_active ?? false))>
                <span>
                    <strong>Baustein einschalten</strong>
                    <small>Ausgeschaltet bleibt der Text gespeichert, wird aber nirgends angezeigt.</small>
                </span>
            </label>

            <x-ui.button type="submit">Speichern</x-ui.button>
        </form>
    </section>
</x-app-shell>
