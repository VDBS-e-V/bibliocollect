{{-- K15: all URLs are real gallery fragments, demo does not submit any mutations. --}}
<section class="bc-core-gallery__section" id="bc-core-command-palette" aria-labelledby="bc-core-command-palette-heading">
    <x-ui.section-heading eyebrow="63 · Schnellzugriff" title="Navigation und Aktionen gezielt durchsuchen" description="Native Dialog-Fokusführung und echte Links; Strg+K öffnet die Vorschau, solange kein Eingabefeld aktiv ist." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-command-palette-heading">Schnellzugriff öffnen</strong><span>Suche, Pfeiltasten, Enter, Escape</span></div>
        <x-ui.command-palette id="bc-k15-palette" title="Schnellzugriff im Designsystem" shortcut :items="[
            ['label'=>'Formularfelder ansehen','group'=>'Formulare','href'=>'#bc-core-forms'],
            ['label'=>'Berechtigungsmatrix ansehen','group'=>'Verwaltung','href'=>'#bc-core-permission-matrix'],
            ['label'=>'Mediendaten vergleichen','group'=>'Bibliothek','href'=>'#bc-core-media-cards'],
            ['label'=>'Noch gesperrt','group'=>'Beispiel','href'=>'#nicht-vorhanden','disabled'=>true],
        ]" />
        <p class="bc-core-gallery-v11__note">Kein globaler Produktions-Shortcut: nur diese Galerie bindet Strg+K. Die Ziele sind vorhandene Abschnitte.</p>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.command-palette&gt;</code><span>id, title, label, triggerLabel, items[], shortcut, density. Öffnet vorhandenes x-ui.dialog; nur echte Linkziele.</span></div>
</section>
<section class="bc-core-gallery__section" id="bc-core-data-grid" aria-labelledby="bc-core-data-grid-heading">
    <x-ui.section-heading eyebrow="64 · Datengitter" title="Sortierbare Datensätze und Auswahl als zusammengesetzte Tabelle" description="Setzt x-ui.table und x-ui.sort-header ein; Sortierlinks müssen von der Anwendung geliefert werden. Auswahl und Status bleiben lokal im dargestellten Gitter." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-data-grid-heading">Comfortable · Auswahl mit Sortierlink</strong><span>3 Datensätze</span></div>
            <x-ui.data-grid title="Medienauswahl als Muster" selectable :columns="[
                ['key'=>'title','label'=>'Titel','sortHref'=>'#bc-core-data-grid','direction'=>'ascending'],
                ['key'=>'type','label'=>'Typ'],['key'=>'status','label'=>'Status'],
            ]" :rows="[
                ['id'=>'m1','label'=>'Erinnerungen und Bibliotheken','cells'=>['title'=>'Erinnerungen und Bibliotheken','type'=>'Buch','status'=>'Verfügbar']],
                ['id'=>'m2','label'=>'Ein langer Beispieltitel mit vielen Worten','cells'=>['title'=>'Ein langer Beispieltitel mit vielen Worten','type'=>'Zeitschrift','status'=>'In Prüfung']],
                ['id'=>'m3','label'=>'Gespert','disabled'=>true,'cells'=>['title'=>'Gesperrter Datensatz','type'=>'Buch','status'=>'Nicht verfügbar']],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · keine Daten</strong><span>Leerzustand</span></div>
            <x-ui.data-grid title="Leere Datentabelle" density="compact" :columns="[]" :rows="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.data-grid&gt;</code><span>title, columns[] mit key/label/sortHref/direction, rows[] mit id/label/cells/disabled, selectable, selectName, density, actions. Keine eigene Datenabfrage.</span></div>
</section>
<section class="bc-core-gallery__section" id="bc-core-side-panel" aria-labelledby="bc-core-side-panel-heading">
    <x-ui.section-heading eyebrow="65 · Seitliches Panel" title="Detailinformationen in einem fokussierten Seitenpanel anzeigen" description="Native Modal-Dialog-Interaktion mit Escape, Fokusbegrenzung und Rückgabe des Fokus an den Auslöser, ohne eigene Overlay-Engine." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-side-panel-heading">Comfortable · Seitenpanel</strong><span>Fokus/Scrollen testen</span></div>
        <x-ui.button variant="secondary" data-ui-dialog-open="bc-k15-side-panel">Seitenpanel öffnen</x-ui.button>
        <x-ui.side-panel id="bc-k15-side-panel" title="Details zum Beispieldatensatz" description="Hier bleibt nur die Darstellung sichtbar; Speichern und Fachaktionen sind nicht angebunden.">
            <x-ui.description-list :items="[]" />
            <p>Dieses Panel kann längere Informationen, Formulare oder Kontextaktionen als Slot übernehmen.</p>
            <x-slot:actions><x-ui.button variant="secondary" data-ui-dialog-close>Zurück zur Übersicht</x-ui.button></x-slot:actions>
        </x-ui.side-panel>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.side-panel&gt;</code><span>id, title, description, closeLabel, actions, density; baut auf nativen dialog und bestehendem Overlay-JS auf.</span></div>
</section>
<section class="bc-core-gallery__section" id="bc-core-confirmation-flow" aria-labelledby="bc-core-confirmation-flow-heading">
    <x-ui.section-heading eyebrow="66 · Bestätigung" title="Komplexe Aktionen erst nach bewusster Prüfung freigeben" description="Warnungsdialog mit Pflichtbestätigung und optionalem eingegebenen Begriff. Ohne action-URL bleibt die Beispielschaltfläche absichtlich deaktiviert." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head"><strong id="bc-core-confirmation-flow-heading">Entscheidung mit zwei Voraussetzungen</strong><span>Keine Demo-Speicherung</span></div>
        <x-ui.button variant="secondary" data-ui-dialog-open="bc-k15-confirm-flow">Bestätigung prüfen</x-ui.button>
        <x-ui.confirmation-flow id="bc-k15-confirm-flow" title="Entscheidung sicher bestätigen" description="Nur als Designvorschau; keine Änderung an Katalog- oder Personendaten." phrase="BESTÄTIGEN" acknowledgeLabel="Ich habe die möglichen Folgen gelesen." />
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.confirmation-flow&gt;</code><span>id, title, description, acknowledgeLabel, phrase, action, submitLabel, density. Optionales POST-Formular nur mit expliziter action; Server muss Berechtigung erneut prüfen.</span></div>
</section>
