{{-- K12 components: previews only, no uploads, no application-side validation and no data writes. --}}
<section class="bc-core-gallery__section" id="bc-core-choice-cards" aria-labelledby="bc-core-choice-cards-heading">
    <x-ui.section-heading eyebrow="52 · Auswahlkarten" title="Größere Entscheidungen als echte Radiogruppen und Checkboxen" description="Eine fachneutrale Alternative zu kurzen segmentierten Optionen. Vollflächig anklickbare Beschriftungen, native Tastaturbedienung und nachvollziehbare Disabled-Zustände." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-choice-cards-heading">Einfachauswahl · Comfortable</strong><span>Radio</span></div>
            <x-ui.choice-cards name="bc-k12-layout" label="Darstellung auswählen" hint="Die Auswahl dient nur der Vorschau und speichert nichts." selected="list" :options="[
                ['value'=>'list','title'=>'Liste mit Details','description'=>'Viel Text und zusätzliche Angaben je Datensatz.'],
                ['value'=>'cards','title'=>'Kartenansicht','description'=>'Größere Vorschaukacheln mit gut lesbaren Titeln.'],
                ['value'=>'closed','title'=>'Nicht verfügbar','description'=>'Diese Option ist derzeit gesperrt.','disabled'=>true],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Mehrfachauswahl · Compact, Fehlertext</strong><span>Checkbox</span></div>
            <x-ui.choice-cards name="bc-k12-topics" label="Angezeigte Informationsblöcke" multiple density="compact" error="Beispiel für einen Validierungshinweis – keine echte Prüfung." :selected="['author']" :options="[
                ['value'=>'author','title'=>'Autorinnen und Autoren'],
                ['value'=>'year','title'=>'Erscheinungsjahr'],
                ['value'=>'notes','title'=>'Zusätzliche Anmerkungen mit ausführlicher Beschreibung'],
            ]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.choice-cards&gt;</code><span>name, label, options[] mit title/description/value/disabled, selected, multiple, hint, error, density. Native Inputs, kein eigenes JavaScript.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-attachment-list" aria-labelledby="bc-core-attachment-list-heading">
    <x-ui.section-heading eyebrow="53 · Dateianlagen" title="Bereits vorhandene Dokumente ohne Uploadlogik anzeigen" description="Die Liste zeigt lesbare Dateinamen, Typen und übergebene Größen. Nur bereitgestellte echte URLs werden verlinkt; Upload und Berechtigungen bleiben unabhängig." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-attachment-list-heading">Dateiliste · Comfortable</strong><span>3 Zustände</span></div>
            <x-ui.attachment-list title="Dokumente im Muster" :items="[
                ['name'=>'Ausführliche Dokumentation zur Mediensammlung und zu ihrer Herkunft.pdf','type'=>'PDF','size'=>'1,8 MB','href'=>'#bc-core-attachment-list','actionLabel'=>'Beispielanker öffnen'],
                ['name'=>'Notizen.txt','type'=>'Textdatei','size'=>'4 KB','description'=>'Nur Metadaten, keine Aktion'],
                ['name'=>'Gesperrtes Dokument.docx','type'=>'Word','disabled'=>true],
            ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Leere Dateiliste · Compact</strong><span>0 Dateien</span></div>
            <x-ui.attachment-list title="Noch keine Dokumente" density="compact" :items="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.attachment-list&gt;</code><span>title, items[] mit name/type/size/description/href/disabled/download/actionLabel, emptyLabel, actions, density. Keine eigenen Dateidaten-Abfragen.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-criteria-checklist" aria-labelledby="bc-core-criteria-checklist-heading">
    <x-ui.section-heading eyebrow="54 · Prüfkriterien" title="Erfüllt, nicht erfüllt, offen und nicht anwendbar unterscheiden" description="Ein nicht-interaktives Kriterienprotokoll, das Status aus übergebenen Daten zeigt. Es ist weder ein Formular noch ein Ersatz für den nichtlinearen Bearbeitungsüberblick." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-criteria-checklist-heading">Kriterien · Comfortable</strong><span>Vier Textstatus</span></div>
            <x-ui.criteria-checklist title="Prüfung der Beispielangaben" :items="[
                ['title'=>'Titel und Urheberschaft angegeben','description'=>'Alle Pflichtinformationen sind vorhanden.','state'=>'met'],
                ['title'=>'Standort eindeutig','description'=>'Der Standort muss noch präzisiert werden.','state'=>'unmet'],
                ['title'=>'Lizenzangaben kontrolliert','state'=>'pending'],
                ['title'=>'Sonderformat berücksichtigt','description'=>'Für dieses Medium nicht erforderlich.','state'=>'not-applicable'],
            ]">
                <x-slot:actions><a href="#bc-core-review-overview" class="bc-button bc-button--secondary bc-button--sm">Bearbeitungsübersicht vergleichen</a></x-slot:actions>
            </x-ui.criteria-checklist>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Ohne Kriterien · Compact</strong><span>Leerzustand</span></div>
            <x-ui.criteria-checklist title="Noch nicht geprüft" density="compact" :items="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.criteria-checklist&gt;</code><span>items[] mit title/description/state: met/unmet/pending/not-applicable; title, actions, density, emptyLabel. Read-only ohne Prüfautomatik.</span></div>
</section>
