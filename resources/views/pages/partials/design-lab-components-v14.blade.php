{{-- K14: opt-in forms, example values only; no backend writes. --}}
<section class="bc-core-gallery__section" id="bc-core-combobox" aria-labelledby="bc-core-combobox-heading">
    <x-ui.section-heading eyebrow="59 · Combobox" title="Auswahl auch aus langen Vorschlagslisten schnell finden" description="Ohne JavaScript ein natives Select; mit JavaScript eine durchsuchbare Listbox. Ein optionaler Same-Origin-Endpunkt kann asynchron Vorschläge liefern." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong id="bc-core-combobox-heading">Comfortable · Vorschläge und Disabled</strong><span>x-ui.combobox</span></div>
            <x-ui.combobox id="bc-k14-combobox" name="bc-k14-book" label="Beispielmedium auswählen" value="3" hint="Pfeiltasten, Enter und Escape ausprobieren." :options="[['value'=>'1','label'=>'Die Welt der Bibliotheken'],['value'=>'2','label'=>'Besonders ausführliche Beispieltitel in deutschen Katalogen'],['value'=>'3','label'=>'Archive und Erinnerungen'],['value'=>'4','label'=>'Gesperrter Eintrag','disabled'=>true]]" />
        </div>
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong>Compact · leer und Fehlermeldung</strong><span>Ohne Serverabfrage</span></div>
            <x-ui.combobox id="bc-k14-combobox-empty" name="bc-k14-empty" label="Kein Datensatz vorhanden" density="compact" error="Bitte wähle einen gültigen Eintrag." :options="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.combobox&gt;</code><span>name, label, options[], value, searchUrl, minChars, hint, error, disabled, density. API für optionale Same-Origin-JSON-Vorschläge, keine produktive Fachsuche.</span></div>
</section>
<section class="bc-core-gallery__section" id="bc-core-date-time" aria-labelledby="bc-core-date-time-heading">
    <x-ui.section-heading eyebrow="60 · Datum und Zeit" title="Termine über die nativen Browserfelder erfassen" description="Klar beschriftetes datetime-local mit Limits und Fehler/Hinweis. Keine Zeitzonen- oder Terminberechnungen im Designsystem." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong id="bc-core-date-time-heading">Comfortable · fiktiver Termin</strong><span>datetime-local</span></div>
            <x-ui.date-time-picker name="bc-k14-appointment" label="Beispieltermin" value="2026-11-14T10:30" hint="Browser zeigt die lokale Datum-/Zeitauswahl an." />
        </div>
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong>Compact · Fehlerzustand</strong><span>aria-describedby</span></div>
            <x-ui.date-time-picker name="bc-k14-invalid-date" label="Anderer Termin" density="compact" error="Das Beispiel hat noch keine gültige Terminangabe." />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.date-time-picker&gt;</code><span>name, label, value, min/max, step, hint/error, required/disabled, density; native Browserbedienung.</span></div>
</section>
<section class="bc-core-gallery__section" id="bc-core-time-range" aria-labelledby="bc-core-time-range-heading">
    <x-ui.section-heading eyebrow="61 · Zeitfenster" title="Beginn und Ende als zusammengehörige Uhrzeiten erfassen" description="Semantisches Fieldset mit zwei nativ bedienbaren Uhrzeitfeldern; fachliche Überprüfung von Endzeiten bleibt beim Server." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong id="bc-core-time-range-heading">Comfortable · zusammengehöriger Zeitraum</strong><span>fieldset / time</span></div>
            <x-ui.time-range name="bc-k14-opening" label="Sonderöffnung" from="09:00" to="13:30" hint="Die Werte sind Beispieldaten." />
        </div>
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong>Compact · ungültiger Zeitraum</strong><span>Fehlerbeispiel</span></div>
            <x-ui.time-range name="bc-k14-error-hours" label="Zeitfenster" from="16:00" to="14:00" error="Das Ende muss nach dem Beginn liegen." density="compact" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.time-range&gt;</code><span>name, label, from/to, min/max, step, hint/error, required/disabled, density; keine JavaScript-Fachvalidierung.</span></div>
</section>
<section class="bc-core-gallery__section" id="bc-core-form-actions" aria-labelledby="bc-core-form-actions-heading">
    <x-ui.section-heading eyebrow="62 · Formularabschluss" title="Speichern und Abbrechen mit einheitlicher Hierarchie anbieten" description="Das Pattern setzt vorhandene Buttons zusammen und nutzt echte Links. Die Galerie führt keinen Speichervorgang aus." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong id="bc-core-form-actions-heading">Comfortable · ohne Formular</strong><span>Reine Präsentation</span></div>
            <x-ui.form-actions submitLabel="Beispiel speichern" cancelHref="#bc-core-form-actions" backHref="#bc-core-date-time" status="Vorschau – kein Formular und keine Datenübertragung." disabled />
        </div>
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong>Compact · flexible Actions</strong><span>Slots</span></div>
            <x-ui.form-actions density="compact" status="Noch nichts gespeichert.">
                <x-slot:primary><x-ui.button disabled>Aktion nicht angebunden</x-ui.button></x-slot:primary>
                <x-slot:secondary><a href="#bc-core-forms" class="bc-button bc-button--tertiary">Formulare vergleichen</a></x-slot:secondary>
            </x-ui.form-actions>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.form-actions&gt;</code><span>submitLabel, cancelHref, backHref, status, disabled, secondary/primary-Slots, density. Echter Submit nur in einem anbietenden Fachformular.</span></div>
</section>
