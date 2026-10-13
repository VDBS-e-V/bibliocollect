{{-- K18: accessible navigation/reading primitives only. Every demo anchor points to a real existing gallery section. --}}
<section class="bc-core-gallery__section" id="bc-core-skip-links" aria-labelledby="bc-core-skip-links-heading">
    <x-ui.section-heading eyebrow="79 · Sprunglinks" title="Wichtige Seitenbereiche ohne lange Tabreihenfolge erreichen" description="Die Links werden beim Tastaturfokus sichtbar; leere oder gesperrte Ziele werden nicht als Scheinlinks ausgegeben. Im echten Layout gehören sie möglichst an den Seitenanfang." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-skip-links-heading">Tastaturvorschau · mit Tab aufrufen</strong><span>Focus-only</span></div>
            <p>Mit der Tabulatortaste zu den unsichtbar vorbereiteten Sprungzielen wechseln.</p>
            <x-ui.skip-links :items="[
                ['href'=>'#bc-core-top','label'=>'Zum Beginn der Komponentengalerie'],
                ['href'=>'#bc-core-forms','label'=>'Zu den Formularen'],
                ['href'=>'#bc-core-library-states','label'=>'Zu den Bibliotheksstatus'],
                ['label'=>'Ungültiger Eintrag ohne Ziel'],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · ein Fokusziel</strong><span>Mobile und Zoom</span></div>
            <x-ui.skip-links density="compact" :items="[['href'=>'#bc-core-data-grid','label'=>'Zum Datengitter']]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.skip-links&gt;</code><span>items[] mit href/label/disabled, label, density; Links nur mit gültig übergebenem Ziel. Semantische Navigation, kein JavaScript.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-back-to-top" aria-labelledby="bc-core-back-to-top-heading">
    <x-ui.section-heading eyebrow="80 · Seitenanfang" title="Zurück zum Beginn einer langen Seite springen" description="Ein echter Anker statt JavaScript-Scroll-Effekt oder permanent überlagernder Floating-Button. Ohne bekanntes Ziel erscheint keine anklickbare Aktion." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-back-to-top-heading">Comfortable · funktionierender Sprung</strong><span>Link zu Galerieanfang</span></div>
            <x-ui.back-to-top href="#bc-core-top" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · ohne URL</strong><span>Keine Scheininteraktion</span></div>
            <x-ui.back-to-top density="compact" label="Anfang nicht angegeben" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.back-to-top&gt;</code><span>href, label, density. Verwendet tatsächliche Seitenanker; keine Fachlogik und keine zusätzlichen Scrollskripte.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-shortcut-hint" aria-labelledby="bc-core-shortcut-hint-heading">
    <x-ui.section-heading eyebrow="81 · Tastaturhilfe" title="Verfügbare Tastaturkürzel lesbar erklären" description="Semantische kbd-Tastenkappen für tatsächlich dokumentierte Shortcuts. Diese Komponente bindet selbst keine Tastenkombinationen." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-shortcut-hint-heading">Comfortable · Schnellzugriff</strong><span>Strg + K</span></div>
            <x-ui.shortcut-hint label="Schnellzugriff im Designlab" :keys="['Strg','K']" />
            <p>Das Kürzel wird bereits von der K15-Schnellzugriff-Komponente im Designlab verarbeitet.</p>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · weitere gültige Tasten</strong><span>Einfaches Beispiel</span></div>
            <x-ui.shortcut-hint label="Schließen" :keys="['Esc']" density="compact" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.shortcut-hint&gt;</code><span>keys[], label, density; rein präsentativ, kein neuer globaler Tastatur-Handler.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-read-more" aria-labelledby="bc-core-read-more-heading">
    <x-ui.section-heading eyebrow="82 · Langtext" title="Längere Beschreibungen ohne neue JavaScript-Engine ein- und ausklappen" description="Native details/summary-Bedienung. Ein kurzer Exzerpttext wird beim Aufklappen verborgen, damit er nicht doppelt vorgelesen wird; der ganze Text bleibt vollständig in einem Slot erhalten." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-read-more-heading">Comfortable · kurzer Anriss und Volltext</strong><span>details/summary</span></div>
            <x-ui.read-more title="Beschreibung des Beispielmediums" excerpt="Die Sammlung vermittelt einen Überblick über unterschiedliche Medienarten, Katalogisierungswege und ihren Gebrauch.">
                <p>Die Sammlung vermittelt einen Überblick über unterschiedliche Medienarten, Katalogisierungswege und ihren Gebrauch. Darüber hinaus beschreibt sie, wie Bibliotheken lange Bestandsbeschreibungen verständlich aufbereiten können.</p>
                <p>Diese fiktive zweite Textpassage dient der Prüfung längerer Inhalte, der Tastaturnavigation und der mobilen Darstellung.</p>
            </x-ui.read-more>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · bereits aufgeklappt</strong><span>Serverseitiger Anfangszustand</span></div>
            <x-ui.read-more title="Zusatzinformationen" moreLabel="Details anzeigen" density="compact" open>
                <p>Bereits geöffnete Informationen bleiben mit nativen Bedienelementen schließbar.</p>
            </x-ui.read-more>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.read-more&gt;</code><span>title, excerpt, moreLabel, open, density, slot. Ergänzt das universelle Disclosure-Muster um Exzerpt und Langtext.</span></div>
</section>
