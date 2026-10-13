{{-- K13: demonstrations without private real-world data, backend mutations or permission decisions. --}}
<section class="bc-core-gallery__section" id="bc-core-contact-card" aria-labelledby="bc-core-contact-card-heading">
    <x-ui.section-heading eyebrow="55 · Kontakt" title="Kontaktangaben strukturiert, lesbar und erreichbar darstellen" description="Ein universeller Kontaktbaustein für öffentlichen und internen Einsatz, ohne Profilverwaltung." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong id="bc-core-contact-card-heading">Comfortable · vollständige Daten</strong><span>address, mailto, tel</span></div>
            <x-ui.contact-card title="Kontaktstelle" name="Beispielbibliothek" description="Fiktive Angaben ausschließlich für das Designsystem" :address-lines="['Musterstraße 12', '12345 Beispielstadt']" email="kontakt@example.org" phone="+49 123 456789" website="https://example.org" />
        </div>
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong>Compact · nur Name</strong><span>Optionale Felder</span></div><x-ui.contact-card title="Ansprechperson" name="Bibliotheksteam" density="compact" /></div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.contact-card&gt;</code><span>title, name, description, addressLines[], email, phone, website, actions, density. Links nur bei übergebenen Daten.</span></div>
</section>
<section class="bc-core-gallery__section" id="bc-core-permission-matrix" aria-labelledby="bc-core-permission-matrix-heading">
    <x-ui.section-heading eyebrow="56 · Berechtigungsmatrix" title="Rollen und Zugriffsarten als echte Datentabelle vergleichen" description="Semantische Zeilen-/Spaltenköpfe und ausgeschriebene Status. Rein lesende Visualisierung, niemals die tatsächliche Berechtigungsprüfung." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong id="bc-core-permission-matrix-heading">Comfortable · vier Zustände</strong><span>Horizontales Scrollen bei Bedarf</span></div>
            <x-ui.permission-matrix title="Beispielhafte Rollenübersicht" :columns="[['key'=>'reader','label'=>'Lesen'],['key'=>'editor','label'=>'Bearbeiten'],['key'=>'admin','label'=>'Verwalten']]" :rows="[
                ['label'=>'Medien anzeigen','values'=>['reader'=>'allowed','editor'=>'allowed','admin'=>'allowed']],
                ['label'=>'Datensätze ändern','values'=>['reader'=>'denied','editor'=>'conditional','admin'=>'allowed']],
                ['label'=>'Sondervorgänge','values'=>['reader'=>'unknown','editor'=>'denied','admin'=>'conditional']],
            ]" />
        </div>
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong>Compact · leer</strong><span>Keine Rollen</span></div><x-ui.permission-matrix density="compact" :columns="[]" :rows="[]" /></div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.permission-matrix&gt;</code><span>columns[] mit key/label, rows[] mit label/values, title, density, emptyLabel. read-only ohne Autorisierung.</span></div>
</section>
<section class="bc-core-gallery__section" id="bc-core-schedule-exceptions" aria-labelledby="bc-core-schedule-exceptions-heading">
    <x-ui.section-heading eyebrow="57 · Ausnahmen" title="Sonderöffnungszeiten und Schließtage neben Wochenplänen zeigen" description="Datierte Abweichungen sind ein anderes Muster als die vorhandenen regelmäßigen Wochenzeiten." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong id="bc-core-schedule-exceptions-heading">Comfortable · unterschiedliche Tage</strong><span>Offen, geschlossen, unklar</span></div>
            <x-ui.schedule-exceptions title="Abweichungen im Beispiel" :items="[
                ['label'=>'Sonderöffnung','date'=>'14.11.2026','datetime'=>'2026-11-14','hours'=>'10:00–14:00 Uhr','note'=>'Nur als Designmuster'],
                ['label'=>'Schließtag','date'=>'15.11.2026','datetime'=>'2026-11-15','closed'=>true],
                ['label'=>'Termin wird noch geklärt','date'=>'20.11.2026','datetime'=>'2026-11-20'],
            ]" />
        </div>
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong>Compact · keine Ausnahmen</strong><span>Leerzustand</span></div><x-ui.schedule-exceptions density="compact" :items="[]" /></div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.schedule-exceptions&gt;</code><span>items[] mit label, date, datetime, hours, closed, note; title, emptyLabel, density. Keine Kalender-/Buchungslogik.</span></div>
</section>
<section class="bc-core-gallery__section" id="bc-core-selection-summary" aria-labelledby="bc-core-selection-summary-heading">
    <x-ui.section-heading eyebrow="58 · Auswahlübersicht" title="Mehrere ausgewählte Einträge vor der Weiterverarbeitung kontrollieren" description="Eine lesbare Zusammenfassung – anders als die vorhandene Bulk-Action-Bar mit bloßer Anzahl. Entfernen ist nur bei übergebener echter URL möglich." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong id="bc-core-selection-summary-heading">Comfortable · zwei Einträge</strong><span>Links und Detailtext</span></div>
            <x-ui.selection-summary title="Beispielauswahl" :items="[
                ['title'=>'Ein sehr langer Buchtitel für die Vorschau und einen Zeilenumbruch','detail'=>'Medientyp: Buch','removeHref'=>'#bc-core-selection-summary'],
                ['title'=>'Weiterer Titel','detail'=>'Medientyp: Zeitschrift','disabled'=>true,'removeHref'=>'#ungueltig'],
            ]" />
        </div>
        <div class="bc-core-gallery__example"><div class="bc-core-gallery__example-head"><strong>Compact · leer</strong><span>0 ausgewählt</span></div><x-ui.selection-summary density="compact" :items="[]" /></div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.selection-summary&gt;</code><span>title, items[] mit title/detail/removeHref/disabled, actions, density, emptyLabel. Kein clientseitiger Mutationshandler.</span></div>
</section>
