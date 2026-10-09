<x-app-shell surface="pos" title="Ausweismotive">
    <x-ui.page-header
        kicker="Ausleihkonten"
        title="Motive der Ausweise"
        lead="Jedes Motiv besteht aus Vorder- und Rückseite, die immer zusammengehören. Beim Drucken werden die aktiven Motive zufällig und in den gewählten Anteilen auf die Ausweise verteilt; ein Ausweis behält sein Motiv auf beiden Seiten."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.labels.cards') }}">← Zurück zu den Ausweisen</a>
    </div>

    @if (session('status'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('status') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="upload-heading">
        <div class="bc-section-heading"><h2 id="upload-heading">Neues Motiv hochladen</h2></div>
        <p class="bc-section-copy">
            Format PNG oder JPG, Seitenverhältnis 85 : 54 (zum Beispiel 2008 × 1276 Pixel), mindestens 1000 Pixel breit, höchstens 8 MB.
            Das Bild ist die fertige Gestaltung und füllt die ganze Karte bis zum Rand.
            <strong>Vorderseite:</strong> mit weißer Fläche (6 mm Rand) und dem Feld „Name“ oben; gedruckt werden darauf nur Logo, Strichcode und Nummer, und zwar im freien weißen Bereich unter dem Namensfeld (etwa 10 bis 75 mm von links und 24 bis 45 mm von oben).
            <strong>Rückseite:</strong> fertig mit Logo; es wird nichts hinzugefügt.
        </p>
        <form method="post" action="{{ route('pos.labels.cards.designs.store') }}" enctype="multipart/form-data" class="bc-audit-filter">
            @csrf
            <x-ui.input label="Name des Motivs" name="name" id="design-name" :value="old('name')" required />
            <x-ui.input label="Bilddatei Vorderseite" name="front" id="design-front" type="file" accept="image/png,image/jpeg" required />
            <x-ui.input label="Bilddatei Rückseite" name="back" id="design-back" type="file" accept="image/png,image/jpeg" required />
            <x-ui.button type="submit">Hochladen</x-ui.button>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="designs-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="designs-heading">Motive</h2>
            <span>{{ $motifs->where('is_active', true)->count() }} aktiv</span>
        </div>

        @if ($motifs->isEmpty())
            <p class="bc-section-copy">Keine Motive. Ohne Motiv wird die Karte auf weißem Grund gedruckt.</p>
        @else
            <ul class="bc-card-designs">
                @foreach ($motifs as $motif)
                    <li class="bc-card-design {{ $motif->is_active ? '' : 'bc-card-design--off' }}">
                        <div class="bc-card-design__pair">
                            @if ($motif->frontUrl())
                                <img src="{{ $motif->frontUrl() }}" alt="Vorderseite des Motivs {{ $motif->name }}" width="200" loading="lazy">
                            @endif
                            @if ($motif->backUrl())
                                <img src="{{ $motif->backUrl() }}" alt="Rückseite des Motivs {{ $motif->name }}" width="200" loading="lazy">
                            @endif
                        </div>
                        <div>
                            <strong>{{ $motif->name }}</strong>
                            <span>
                                @if (! $motif->isComplete())
                                    Unvollständig: {{ $motif->frontUrl() ? 'Rückseite' : 'Vorderseite' }} fehlt, bitte neu hochladen
                                @else
                                    {{ $motif->is_active ? 'Aktiv' : 'Ausgeschaltet' }}
                                @endif
                            </span>
                        </div>
                        <form method="post" action="{{ route('pos.labels.cards.designs.toggle', ['designId' => $motif->getKey()]) }}">
                            @csrf
                            <button type="submit" class="bc-intake-linkbutton">{{ $motif->is_active ? 'Ausschalten' : 'Einschalten' }}</button>
                        </form>
                        <form method="post" action="{{ route('pos.labels.cards.designs.destroy', ['designId' => $motif->getKey()]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bc-intake-linkbutton" data-confirm="Motiv „{{ $motif->name }}“ wirklich löschen?">Löschen</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-shell>
