<x-app-shell surface="pos" title="Ausweismotive">
    <x-ui.page-header
        kicker="Ausleihkonten"
        title="Motive der Ausweise"
        lead="Hintergrundbilder für Vorder- und Rückseiten. Beim Drucken werden die aktiven Motive zufällig und in den gewählten Anteilen auf die Ausweise verteilt."
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
            <x-ui.select label="Seite" name="side" id="design-side">
                <option value="front" @selected(old('side') === 'front')>Vorderseite</option>
                <option value="back" @selected(old('side') === 'back')>Rückseite</option>
            </x-ui.select>
            <x-ui.input label="Name des Motivs" name="name" id="design-name" :value="old('name')" required />
            <x-ui.input label="Bilddatei" name="image" id="design-image" type="file" accept="image/png,image/jpeg" required />
            <x-ui.button type="submit">Hochladen</x-ui.button>
        </form>
    </section>

    @foreach (['front' => ['Vorderseite', $front], 'back' => ['Rückseite', $back]] as $key => [$title, $designs])
        <section class="bc-content-section" aria-labelledby="designs-{{ $key }}-heading">
            <div class="bc-section-heading bc-section-heading--with-meta">
                <h2 id="designs-{{ $key }}-heading">Motive {{ $title }}</h2>
                <span>{{ $designs->where('is_active', true)->count() }} aktiv</span>
            </div>

            @if ($designs->isEmpty())
                <p class="bc-section-copy">Keine Motive. Ohne Motiv wird die Karte auf weißem Grund gedruckt.</p>
            @else
                <ul class="bc-card-designs">
                    @foreach ($designs as $design)
                        <li class="bc-card-design {{ $design->is_active ? '' : 'bc-card-design--off' }}">
                            <img src="{{ $design->url() }}" alt="Motiv {{ $design->name }}" width="200" loading="lazy">
                            <div>
                                <strong>{{ $design->name }}</strong>
                                <span>{{ $design->is_active ? 'Aktiv' : 'Ausgeschaltet' }}</span>
                            </div>
                            <form method="post" action="{{ route('pos.labels.cards.designs.toggle', ['designId' => $design->getKey()]) }}">
                                @csrf
                                <button type="submit" class="bc-intake-linkbutton">{{ $design->is_active ? 'Ausschalten' : 'Einschalten' }}</button>
                            </form>
                            <form method="post" action="{{ route('pos.labels.cards.designs.destroy', ['designId' => $design->getKey()]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bc-intake-linkbutton" data-confirm="Motiv „{{ $design->name }}“ wirklich löschen?">Löschen</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach
</x-app-shell>
