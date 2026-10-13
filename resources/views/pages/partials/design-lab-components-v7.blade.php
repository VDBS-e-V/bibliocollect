{{-- K7: Components first; review only – no printing, sending, posting or database writes. --}}
<section class="bc-core-gallery__section" id="bc-core-cover-variants" aria-labelledby="bc-core-cover-variants-heading">
    <x-ui.section-heading eyebrow="26 · Mediencover" title="Ein Cover. Vier Farbwelten. Vier Größen." description="Wir erweitern das vorhandene x-library.media-cover statt ein zweites Coversystem aufzubauen. Originalbilder werden immer bevorzugt, andernfalls erscheint das typografische Ersatzcover." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-cover-variants-heading">VDBS-Farbvarianten</strong><span>x-library.media-cover · plum / mint / ink / sand</span></div>
        <div class="bc-core-gallery-v7__covers">
            <figure>
                <div class="bc-core-gallery-v7__cover-frame bc-core-gallery-v7__cover-frame--normal"><x-library.media-cover title="Zwischen den Zeilen" author="Lena Beispiel" variant="plum" /></div>
                <figcaption>Plum · Standard</figcaption>
            </figure>
            <figure>
                <div class="bc-core-gallery-v7__cover-frame bc-core-gallery-v7__cover-frame--normal"><x-library.media-cover title="Geschichten aus einer anderen Stadt" author="Nora Muster" variant="mint" /></div>
                <figcaption>Mint · Standard</figcaption>
            </figure>
            <figure>
                <div class="bc-core-gallery-v7__cover-frame bc-core-gallery-v7__cover-frame--normal"><x-library.media-cover title="Ein neuer Anfang" author="Alex Beispiel" variant="ink" /></div>
                <figcaption>Ink · Standard</figcaption>
            </figure>
            <figure>
                <div class="bc-core-gallery-v7__cover-frame bc-core-gallery-v7__cover-frame--normal"><x-library.media-cover title="Die Spuren der Zeit" author="Mara Muster" variant="sand" /></div>
                <figcaption>Sand · Standard</figcaption>
            </figure>
        </div>
    </div>
    <div class="bc-core-gallery__example bc-core-gallery-v7__next">
        <div class="bc-core-gallery__example-head"><strong>Größen und lange Titel</strong><span>thumbnail / small / normal / hero</span></div>
        <div class="bc-core-gallery-v7__sizes">
            <figure><div class="bc-core-gallery-v7__cover-frame bc-core-gallery-v7__cover-frame--thumbnail"><x-library.media-cover size="thumbnail" title="Ein kleines Buch" variant="mint" /></div><figcaption>Thumbnail</figcaption></figure>
            <figure><div class="bc-core-gallery-v7__cover-frame bc-core-gallery-v7__cover-frame--small"><x-library.media-cover size="small" title="Zwischen den Zeilen" variant="plum" /></div><figcaption>Small</figcaption></figure>
            <figure><div class="bc-core-gallery-v7__cover-frame bc-core-gallery-v7__cover-frame--normal"><x-library.media-cover title="Eine besonders lange Geschichte zwischen Bibliotheken und Welten" variant="ink" /></div><figcaption>Normal · langer Titel</figcaption></figure>
            <figure><div class="bc-core-gallery-v7__cover-frame bc-core-gallery-v7__cover-frame--hero"><x-library.media-cover size="hero" title="Die außergewöhnlich lange Reise einer Bibliothek durch die Geschichten der Welt" author="Nora Beispiel" variant="sand" /></div><figcaption>Hero · sehr langer Titel</figcaption></figure>
        </div>
        <p class="bc-core-gallery-v7__note">Lange Titel dürfen umbrechen; bei kleinen Ersatzcovern ist der Text dekorativ gekürzt. Der vollständige Titel steht später immer zusätzlich als zugängliche Überschrift neben dem Cover.</p>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.media-cover&gt;</code><span>src, title, author, variant, size. Bilder bevorzugt, vier definierte VDBS-Ersatzillustrationen, zusätzliche Thumbnail-/Small-Geometrie.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-inline-edit" aria-labelledby="bc-core-inline-edit-heading">
    <x-ui.section-heading eyebrow="27 · Inline-Bearbeitung" title="Informationen ansehen und gezielt bearbeiten" description="Ein einheitliches Pattern für einzelne Metadatenfelder. Aufklappen funktioniert ohne JavaScript; Speichern bleibt eine spätere Fachaktion." />
    <div class="bc-core-gallery-v7__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-inline-edit-heading">Comfortable</strong><span>x-ui.inline-edit</span></div>
            <x-ui.inline-edit name="bc-demo-edit-title" label="Medientitel" value="Zwischen den Zeilen" hint="Änderungen werden in dieser Vorschau nicht gespeichert." />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · langer Wert</strong><span>density=compact</span></div>
            <x-ui.inline-edit name="bc-demo-edit-location" label="Standort" value="Regal A / Sachbücher / Fachgruppe Medien- und Bibliotheksgeschichte" compact hint="Die Vorschau ist nur lokal bearbeitbar." />
        </div>
    </div>
    <p class="bc-core-gallery-v7__note">Beide Muster können geöffnet und mit Tastatur bedient werden. Es gibt bewusst keinen funktionslosen Speichern-Button: Ein echter Speicherprozess folgt erst bei der Seitenintegration.</p>
</section>

<section class="bc-core-gallery__section" id="bc-core-hierarchy" aria-labelledby="bc-core-hierarchy-heading">
    <x-ui.section-heading eyebrow="28 · Struktur" title="Hierarchische Navigation durch Regale und Bereiche" description="Aufklappbare Bereiche, klare Einrückungen und einheitliche Links – auch für spätere Kategorien, Themen und Bestandsgruppen verwendbar." />
    <div class="bc-core-gallery-v7__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-hierarchy-heading">Regalhierarchie</strong><span>x-ui.hierarchy-tree</span></div>
            <x-ui.hierarchy-tree label="Beispiel-Regalstruktur" :items="[
                ['label' => 'Bibliothek', 'count' => 132, 'open' => true, 'children' => [
                    ['label' => 'Sachbücher', 'count' => 58, 'open' => true, 'children' => [
                        ['label' => 'Geschichte', 'count' => 19, 'href' => '#bc-core-hierarchy', 'active' => true],
                        ['label' => 'Naturwissenschaften', 'count' => 22, 'href' => '#bc-core-hierarchy'],
                        ['label' => 'Medien- und Bibliotheksgeschichte', 'count' => 17, 'href' => '#bc-core-hierarchy'],
                    ]],
                    ['label' => 'Romane', 'count' => 74, 'children' => [
                        ['label' => 'Abenteuer', 'count' => 28, 'href' => '#bc-core-hierarchy'],
                        ['label' => 'Fantasy', 'count' => 46, 'href' => '#bc-core-hierarchy'],
                    ]],
                ]],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Kompakte Arbeitsnavigation</strong><span>Compact</span></div>
            <x-ui.hierarchy-tree label="Beispiel-Verwaltungsstruktur" compact :items="[
                ['label' => 'Bereiche', 'open' => true, 'children' => [
                    ['label' => 'Aktive Medien', 'href' => '#bc-core-hierarchy', 'active' => true],
                    ['label' => 'In Bearbeitung', 'href' => '#bc-core-hierarchy'],
                    ['label' => 'Archiv', 'href' => '#bc-core-hierarchy'],
                ]],
            ]" />
            <p class="bc-core-gallery-v7__note">Ein Ordner ist hier ein aufklappbarer Bereich, kein vorgetäuschter Drag-and-drop-Baum. Die Links führen nur zurück zur Demo.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-week-schedule" aria-labelledby="bc-core-week-schedule-heading">
    <x-ui.section-heading eyebrow="29 · Zeit und Öffnungszeiten" title="Wochenplan in zwei Dichten" description="Ein verlässliches Darstellungspattern für Öffnungszeiten, Veranstaltungsfenster und andere wiederkehrende Zeitpläne." />
    <div class="bc-core-gallery-v7__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-week-schedule-heading">Wochenübersicht</strong><span>x-ui.week-schedule</span></div>
            <x-ui.week-schedule label="Beispiel-Öffnungszeiten" :items="[
                ['day' => 'Montag', 'hours' => '09:00–17:00'],
                ['day' => 'Dienstag', 'hours' => '09:00–18:00'],
                ['day' => 'Mittwoch', 'hours' => '10:00–16:00', 'note' => 'Lesesaal bis 17:00'],
                ['day' => 'Donnerstag', 'hours' => '09:00–18:00'],
                ['day' => 'Freitag', 'hours' => '09:00–14:00'],
                ['day' => 'Samstag', 'closed' => true],
                ['day' => 'Sonntag', 'closed' => true],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Kompakt / Sonderzeiten</strong><span>Compact</span></div>
            <x-ui.week-schedule label="Beispiel-Sonderöffnungszeiten" compact :items="[
                ['day' => 'Dienstag', 'hours' => '13:00–17:00', 'note' => 'Ferienöffnungszeiten'],
                ['day' => 'Mittwoch', 'closed' => true, 'note' => 'Teamfortbildung'],
                ['day' => 'Donnerstag', 'hours' => '08:30–13:30'],
            ]" />
            <p class="bc-core-gallery-v7__note">Dies sind bewusst frei erfundene Beispielzeiten. Der Baustein zeigt Daten an; Feiertags- und Kalenderlogik bleibt im Fachbereich.</p>
        </div>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-print-preview" aria-labelledby="bc-core-print-preview-heading">
    <x-ui.section-heading eyebrow="30 · Etiketten" title="Schilder und Rückenschilder als Druckvorschau" description="Eine Vorschau für die visuelle Abnahme. Bestehende produktive Etiketten- und Barcode-Druckroutinen bleiben unverändert." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-print-preview-heading">Zwei Etikettformen</strong><span>x-library.shelf-label-preview</span></div>
        <div class="bc-core-gallery-v7__labels">
            <x-library.shelf-label-preview type="shelf" signature="SACH / 100" shelf="Geschichte & Gesellschaft" subtitle="Regal 3 · Bibliothek Mitte" />
            <x-library.shelf-label-preview type="spine" signature="B 142" shelf="Regal B" subtitle="Beispiel" />
        </div>
        <p class="bc-core-gallery-v7__note">Keine simulierten scannbaren Barcodes: Die Signatur ist lesbarer Text. Ausgabeabstände und echte Barcodeformate müssen später separat mit den bestehenden Druckvorlagen geprüft werden.</p>
    </div>
</section>

<section class="bc-core-gallery__section" id="bc-core-message-preview" aria-labelledby="bc-core-message-preview-heading">
    <x-ui.section-heading eyebrow="31 · Kommunikation" title="Nachrichten vor dem Versand prüfen" description="Ein neutrales Muster für Betreff, Empfänger und Nachrichtentext. Die Vorschau sendet keine E-Mail und zeigt keine echten Personendaten." />
    <div class="bc-core-gallery-v7__two-col">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-message-preview-heading">Mitteilung · Comfortable</strong><span>x-ui.message-preview</span></div>
            <x-ui.message-preview recipient="beispiel@invalid.example" subject="Information zu deiner Bibliotheksausleihe">
                <p>Guten Tag,</p>
                <p>dies ist ein Beispiel für eine Nachricht aus der Bibliothek. Hier könnten Ausleihen und Rückgabefristen erklärt werden.</p>
                <p>Viele Grüße<br>Dein Bibliotheksteam</p>
            </x-ui.message-preview>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Kompakte Systemnachricht</strong><span>Compact</span></div>
            <x-ui.message-preview recipient="team@invalid.example" subject="Beispiel: Änderung der Öffnungszeiten" compact>
                <p>Die Öffnungszeiten wurden im Testfall aktualisiert. In dieser Vorschau passiert keine Änderung.</p>
            </x-ui.message-preview>
            <p class="bc-core-gallery-v7__note">Echte E-Mails benötigen Freigabe, dynamische Vorlagen, Berechtigungsprüfung und datensparsame Inhalte. Das ist nicht Teil dieser Designstudie.</p>
        </div>
    </div>
</section>
