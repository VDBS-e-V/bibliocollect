<x-app-shell surface="administration" :title="$page->title">
    <x-ui.page-header kicker="Informationsseite" :title="$page->title" />

    <div class="bc-context-actions">
        <a href="{{ route('administration.pages.index') }}">← Zurück zu den Seiten</a>
        <a href="/{{ $page->slug }}">Seite ansehen</a>
    </div>

    @if (session('school_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('school_success') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="page-edit-heading">
        <div class="bc-section-heading"><h2 id="page-edit-heading">Text bearbeiten</h2></div>
        <form method="post" action="{{ route('administration.pages.update', ['slug' => $page->slug]) }}" class="bc-calendar-form">
            @csrf
            @method('PATCH')
            <x-ui.input label="Überschrift" name="title" :value="old('title', $page->title)" required />
            <div class="bc-field">
                <label class="bc-field__label" for="body">Text</label>
                <p class="bc-field__hint" id="body-hint">Absätze durch eine Leerzeile trennen. <code>## Überschrift</code> macht eine Zwischenüberschrift, Zeilen mit <code>- </code> am Anfang werden zur Liste. Internetadressen und E-Mail-Adressen werden zu Links, HTML wird nicht ausgeführt.</p>
                <textarea id="body" name="body" rows="22" class="bc-field__control" aria-describedby="body-hint" required>{{ old('body', $page->body) }}</textarea>
            </div>
            <x-ui.button type="submit">Speichern</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="page-preview-heading">
        <div class="bc-section-heading"><h2 id="page-preview-heading">So sieht der gespeicherte Text aus</h2></div>
        <div class="bc-content-page__body">{!! $preview !!}</div>
    </section>
</x-app-shell>
