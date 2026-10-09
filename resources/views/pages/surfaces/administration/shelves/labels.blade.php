<x-app-shell surface="administration" title="Etiketten für Regalbretter">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Etiketten für Regalbretter"
        :lead="'Etiketten mit dem Thema in großer Schrift, dem Standort klein unten rechts und einem Code, den das Einsortieren scannt. Format 105 × 26 mm, '.$perSheet.' Etiketten je A4-Bogen (2 × 11), weißer Hintergrund.'"
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.shelves.index') }}">← Zurück zu Regale und Regalbretter</a>
    </div>

    @if (! empty($empty))
        <x-ui.alert variant="error" title="Nichts zu drucken">Zu dieser Auswahl gibt es keine Regalbretter. Wähle einen anderen Umfang oder schalte „auch ausgeschaltete“ ein.</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="post" action="{{ route('administration.shelves.labels.print') }}" target="_blank" class="bc-labels-form">
        @csrf

        <section class="bc-content-section" aria-labelledby="scope-heading">
            <div class="bc-section-heading"><h2 id="scope-heading">1. Was soll gedruckt werden?</h2></div>
            <x-ui.select label="Umfang" name="umfang" id="label-scope">
                <option value="alle">Alle Regalbretter ({{ $total }})</option>
                @foreach ($groups as $group)
                    <optgroup label="Bereichsgruppe {{ $group->code }}@if ($group->name) · {{ $group->name }}@endif">
                        <option value="g:{{ $group->getKey() }}">Ganze Bereichsgruppe {{ $group->code }}</option>
                        @foreach ($group->children as $area)
                            <option value="a:{{ $area->getKey() }}">Bereich {{ $group->code }} › {{ $area->code }}@if ($area->name) · {{ $area->name }}@endif</option>
                            @foreach ($area->children as $rack)
                                <option value="r:{{ $rack->getKey() }}">Regal {{ $group->code }} › {{ $area->code }} › {{ $rack->code }}@if ($rack->name) · {{ $rack->name }}@endif ({{ $rack->shelves->count() }})</option>
                            @endforeach
                        @endforeach
                    </optgroup>
                @endforeach
                @if ($loose->isNotEmpty())
                    <option value="lose">Regalbretter ohne Regal ({{ $loose->count() }})</option>
                @endif
            </x-ui.select>

            <details class="bc-loc__add">
                <summary>Oder einzelne Regalbretter wählen (hat Vorrang vor dem Umfang)</summary>
                <div class="bc-topic-checklist">
                    @foreach ($groups as $group)
                        @foreach ($group->children as $area)
                            @foreach ($area->children as $rack)
                                <fieldset>
                                    <legend>{{ $group->code }} › {{ $area->code }} › {{ $rack->code }}@if ($rack->name) · {{ $rack->name }}@endif</legend>
                                    @foreach ($rack->shelves as $shelf)
                                        <label class="bc-checkbox-line"><input type="checkbox" name="bretter[]" value="{{ $shelf->getKey() }}"> {{ $shelf->code }}@if ($shelf->label) <small>· {{ $shelf->label }}</small>@endif</label>
                                    @endforeach
                                </fieldset>
                            @endforeach
                        @endforeach
                    @endforeach
                    @if ($loose->isNotEmpty())
                        <fieldset>
                            <legend>Ohne Regal</legend>
                            @foreach ($loose as $shelf)
                                <label class="bc-checkbox-line"><input type="checkbox" name="bretter[]" value="{{ $shelf->getKey() }}"> {{ $shelf->code }}@if ($shelf->label) <small>· {{ $shelf->label }}</small>@endif</label>
                            @endforeach
                        </fieldset>
                    @endif
                </div>
            </details>
        </section>

        <section class="bc-content-section" aria-labelledby="options-heading">
            <div class="bc-section-heading"><h2 id="options-heading">2. Wie soll gedruckt werden?</h2></div>
            <div class="bc-loc-form">
                <x-ui.input label="Etiketten je Regalbrett" name="anzahl" id="label-copies" type="number" min="1" max="4" value="1" hint="Zum Beispiel 2: eines links und eines rechts am Brett." />
                <x-ui.input label="Erster freier Platz auf dem Bogen" name="startplatz" id="label-start" type="number" min="1" :max="$perSheet" value="1" hint="Von links nach rechts und oben nach unten gezählt, für angebrochene Bögen." />
            </div>
            <x-ui.select label="Code zum Scannen" name="code" id="label-code" hint="Der Scanner am Tresen liest beide. Der QR-Code lässt sich auch mit der Handykamera lesen und braucht weniger Platz.">
                <option value="strich">Strichcode</option>
                <option value="qr">QR-Code</option>
                <option value="keiner">Kein Code (nur Standort)</option>
            </x-ui.select>
            <x-ui.select label="QR-Code zeigt auf" name="ziel" id="label-target" hint="Nur bei QR-Code. „Thema“ führt Nutzer:innen zu allen Regalbrettern und Medien des Themas; für das Einsortieren eignet sich „Regalbrett“.">
                <option value="regalbrett">Dieses Regalbrett</option>
                <option value="thema">Das Thema des Regalbretts (alle Bretter des Themas)</option>
            </x-ui.select>
            <label class="bc-checkbox-line"><input type="checkbox" name="themen" value="1"> Themenbereiche des Bretts klein mit aufdrucken</label>
            <label class="bc-checkbox-line"><input type="checkbox" name="inaktive" value="1"> Auch ausgeschaltete Regalbretter</label>
        </section>

        <x-ui.button type="submit">Druckseite öffnen</x-ui.button>
        <p class="bc-section-copy">Die Druckseite öffnet in einem neuen Fenster. Dort in <strong>tatsächlicher Größe</strong> drucken (ohne „Seite anpassen“). Sitzt der Druck um einen Millimeter daneben, stellst du ihn oben im Feinabgleich nach. Der Druck steht im Protokoll.</p>
    </form>
</x-app-shell>
