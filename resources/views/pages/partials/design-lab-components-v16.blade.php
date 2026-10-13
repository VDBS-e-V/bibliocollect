{{-- K16: all examples use fictional values and existing visual primitives; no backend operations. --}}
<section class="bc-core-gallery__section" id="bc-core-media-detail-summary" aria-labelledby="bc-core-media-detail-summary-heading">
    <x-ui.section-heading eyebrow="67 · Mediendetails" title="Aus Cover und vorhandenen Datenelementen eine Detailübersicht bauen" description="Mediendetails als Composition Pattern mit dem bestehenden Cover, Beschreibungsliste und Verfügbarkeitsstatus. Kein zweites Medienkarten-Pattern." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-media-detail-summary-heading">Comfortable · langer Titel und Metadaten</strong><span>Cover, Status und Actions-Slot</span></div>
            <x-library.media-detail-summary title="Die außergewöhnliche Geschichte eines sehr langen deutschen Medientitels" subtitle="Eine Beispielsammlung für die Komponentenprüfung" author="Beispielautor:innen" availability="available" :metadata="[
                ['label'=>'Medientyp','value'=>'Buch'],['label'=>'Erscheinung','value'=>'2024'],
                ['label'=>'Umfang','value'=>'276 Seiten'],['label'=>'Sprache','value'=>'Deutsch'],
            ]">
                <x-slot:actions><a href="#bc-core-media-cards" class="bc-button bc-button--secondary bc-button--sm">Medienkarten vergleichen</a></x-slot:actions>
            </x-library.media-detail-summary>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · minimale Angaben</strong><span>Fiktive Daten</span></div>
            <x-library.media-detail-summary title="Ein Titel ohne Zusatzdaten" density="compact" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.media-detail-summary&gt;</code><span>title, author, subtitle, cover, coverVariant, metadata[], availability, availabilityDetail, status/actions-Slots, density.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-hold-status" aria-labelledby="bc-core-hold-status-heading">
    <x-ui.section-heading eyebrow="68 · Vormerkungen" title="Position, Abholbereitschaft und abgelaufene Fristen erkennen" description="Sechs klar bezeichnete Zustände: Warten, bereit, pausiert, abgelaufen, beendet und unbekannt. Keine Berechnung von Vormerkpositionen." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-hold-status-heading">Comfortable und Compact · sechs Zustände</strong><span>Status immer ausgeschrieben</span></div>
        <div class="bc-core-gallery-v11__grid">
            <x-library.hold-status state="waiting" :position="3" detail="Ein fiktiver Warteplatz." />
            <x-library.hold-status state="ready" expiresLabel="18.11.2026" detail="Nur als Testansicht." />
            <x-library.hold-status state="paused" compact />
            <x-library.hold-status state="expired" compact />
            <x-library.hold-status state="cancelled" compact />
            <x-library.hold-status state="unknown" compact />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.hold-status&gt;</code><span>state: waiting/ready/paused/expired/cancelled/unknown, position, expiresLabel, detail, compact. Keine Vormerkautomatik.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-loan-summary" aria-labelledby="bc-core-loan-summary-heading">
    <x-ui.section-heading eyebrow="69 · Ausleihen" title="Aktuelle Ausleihen und Rückgabefristen kompakt überblicken" description="Die Fristanzeige ist die vorhandene Due-Date-Status-Komponente; ihre 7-Tage-Regel bleibt unverändert." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-loan-summary-heading">Comfortable · Frist läuft ab</strong><span>7-Tage-Regel</span></div>
            <x-library.loan-summary title="Medien im Wandel der Zeit" author="Musterautor:in" barcode="BC-000123" :dueDays="3" dueDateLabel="15.11.2026" href="#bc-core-loan-summary">
                <x-slot:actions><a href="#bc-core-library-states" class="bc-button bc-button--secondary bc-button--sm">Friststatus vergleichen</a></x-slot:actions>
            </x-library.loan-summary>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · keine Fälligkeit bekannt</strong><span>Kein erfundener Wert</span></div>
            <x-library.loan-summary title="Ein weiteres Beispiel" density="compact" />
        </div>
    </div>

    {{-- Boundary gallery only: these are explicitly supplied demo day counts, not real dates or reminder rules. --}}
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong>Fristgrenzen im direkten Vergleich</strong><span>Fiktive Tagesdifferenzen · keine Berechnung</span></div>
        <div class="bc-deadline-matrix" role="group" aria-label="Grenzwerte für Rückgabefristen">
            <div class="bc-deadline-matrix__case">
                <span class="bc-deadline-matrix__label">8 Tage · neutral</span>
                <x-library.due-date-status :daysRemaining="8" />
            </div>
            <div class="bc-deadline-matrix__case">
                <span class="bc-deadline-matrix__label">7 Tage · Warnung beginnt</span>
                <x-library.due-date-status :daysRemaining="7" />
            </div>
            <div class="bc-deadline-matrix__case">
                <span class="bc-deadline-matrix__label">1 Tag · bald fällig</span>
                <x-library.due-date-status :daysRemaining="1" />
            </div>
            <div class="bc-deadline-matrix__case">
                <span class="bc-deadline-matrix__label">0 Tage · heute fällig</span>
                <x-library.due-date-status :daysRemaining="0" />
            </div>
            <div class="bc-deadline-matrix__case">
                <span class="bc-deadline-matrix__label">−1 Tag · überfällig</span>
                <x-library.due-date-status :daysRemaining="-1" />
            </div>
            <div class="bc-deadline-matrix__case">
                <span class="bc-deadline-matrix__label">Keine Tagesangabe · unbekannt</span>
                <x-library.due-date-status />
            </div>
        </div>
        <p class="bc-core-gallery-v5__note">Alle sechs Fälle sind reine Anzeigezustände. Orange beginnt einschließlich Tag 7, Rot kennzeichnet Überfälligkeit. Berechnung, Verlängerung und Erinnerungsversand bleiben außerhalb der Komponenten.</p>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.loan-summary&gt;</code><span>title, author, barcode, dueDays, dueDateLabel, href, actions, density; rendert bestehende Due-Date-Status-Regeln.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-identifier-list" aria-labelledby="bc-core-identifier-list-heading">
    <x-ui.section-heading eyebrow="70 · Kennungen" title="ISBN, ISSN und Barcode als Metadaten klar gegenüberstellen" description="Ein eigener wiederverwendbarer Typ-/Wert-Baustein mit optionalen echten Links. Die fachliche Prüfung von ISBN oder Barcode findet nicht in der Ansicht statt." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-identifier-list-heading">Comfortable · lange Kennungen</strong><span>dl, dt und dd</span></div>
            <x-library.identifier-list :items="[
                ['label'=>'ISBN-13','value'=>'978-3-16-148410-0','description'=>'Fiktives Formatbeispiel'],
                ['label'=>'ISSN','value'=>'2049-3630'],
                ['label'=>'Barcode','value'=>'BIB-COLLECT-MUSTER-000000000000123456789'],
                ['label'=>'Interne Kennung','value'=>'ID-42','href'=>'#bc-core-identifier-list'],
                ['label'=>'Gesperrte Kennung','value'=>'X-100','href'=>'#nicht-vorhanden','disabled'=>true],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · leer</strong><span>Keine Kennungen</span></div>
            <x-library.identifier-list density="compact" :items="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.identifier-list&gt;</code><span>title, items[] mit label/value/description/href/disabled, emptyLabel, density. Kein Parser, Scanner oder Validator.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-location-path" aria-labelledby="bc-core-location-path-heading">
    <x-ui.section-heading eyebrow="71 · Standortpfad" title="Vom Bibliotheksbereich bis zur Signatur orientieren" description="Lesbare Standort-Hierarchie mit optionalen echten Bereichslinks; ergänzt Breadcrumbs, ohne die allgemeine Seitennavigation nachzubauen." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-location-path-heading">Comfortable · Standort in vier Ebenen</strong><span>Signatur und Hinweis</span></div>
            <x-library.location-path title="Standort des Beispielmediums" :segments="[
                ['label'=>'Hauptbibliothek','href'=>'#bc-core-location-path'],
                ['label'=>'Obergeschoss'],['label'=>'Freihandbereich'],['label'=>'Regal B-17'],
            ]" signature="LIT 870.18" detail="Linke Seite, drittes Fach" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · unbekannt</strong><span>Kein Pseudopfad</span></div>
            <x-library.location-path density="compact" :segments="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.location-path&gt;</code><span>title, segments[] mit label/href/disabled, signature, detail, emptyLabel, density. Reine Standortdarstellung.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-contact-preferences" aria-labelledby="bc-core-contact-preferences-heading">
    <x-ui.section-heading eyebrow="72 · Kontaktwünsche" title="Benachrichtigungskanäle als eine zugängliche Formulargruppe wählen" description="Fieldset/Legend und bestehende Checkboxen. Die Auswahl verändert weder Zustellregeln noch echte Kontaktinformationen und speichert nichts." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-contact-preferences-heading">Comfortable · Auswahl und Disabled</strong><span>Native Checkboxen</span></div>
            <x-ui.contact-preferences name="bc-demo-contact-channels" label="Bevorzugte Kontaktwege" hint="Beispieldaten; keine Benachrichtigungen werden versendet." :selected="['email']" :options="[
                ['value'=>'email','label'=>'E-Mail','description'=>'Nachrichten an eine hinterlegte E-Mail-Adresse'],
                ['value'=>'portal','label'=>'Nachrichten im Portal','description'=>'Hinweise im persönlichen Bereich'],
                ['value'=>'sms','label'=>'SMS','description'=>'In dieser Demonstration nicht auswählbar','disabled'=>true],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · Fehler und leer</strong><span>Keine Fachvalidierung</span></div>
            <x-ui.contact-preferences name="bc-demo-contact-empty" label="Keine Kontaktwege" error="Beispiel einer späteren Formularvalidierung." density="compact" :options="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.contact-preferences&gt;</code><span>name, label, options[] mit value/label/description/disabled, selected[], hint/error, density. Keine Speicherung oder Zustelllogik.</span></div>
</section>
