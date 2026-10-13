{{-- K17: fictional datasets and local previews; never uploads, saves, imports or launches batch jobs. --}}
<section class="bc-core-gallery__section" id="bc-core-audit-entry" aria-labelledby="bc-core-audit-entry-heading">
    <x-ui.section-heading eyebrow="73 · Änderungsprotokoll" title="Änderungen mit Zeitpunkt und Verantwortlichkeit nachvollziehen" description="Ein einzelner Audit-Eintrag ergänzt die bestehende Timeline: Aktionstyp, Zeit, handelnde Person und optional vorhandener Änderungsvergleich. Das Fachsystem liefert diese Daten." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-audit-entry-heading">Comfortable · Änderung mit Vergleich</strong><span>Zeit + Akteur + Before/After</span></div>
            <x-ui.audit-entry title="Katalogangabe aktualisiert" kind="updated" actor="Beispielredaktion"
                occurredAt="2026-11-14T10:20:00+01:00" dateLabel="14.11.2026, 10:20 Uhr" description="Fiktiver Vorgang für den Designvergleich."
                :changes="[['label'=>'Titel','before'=>'Alter Titel','after'=>'Neuer, ausführlicher Medientitel'],['label'=>'Sprache','before'=>'Deutsch','after'=>'Deutsch']]"
                href="#bc-core-change-review" linkLabel="Änderungsvergleich ansehen" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · Entfernung ohne Änderungsdaten</strong><span>Reine Anzeige</span></div>
            <x-ui.audit-entry title="Datensatz aus dem Beispielarchiv entfernt" kind="deleted" actor="Beispielverwaltung" density="compact" dateLabel="Früherer Vorgang" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.audit-entry&gt;</code><span>title, kind, actor, occurredAt/dateLabel, description, changes[], href/linkLabel, density. Verwendet vorhandenes change-review, erzeugt kein Audit-Protokoll.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-key-value-editor" aria-labelledby="bc-core-key-value-editor-heading">
    <x-ui.section-heading eyebrow="74 · Metadateneditor" title="Strukturierte Metadatenpaare hinzufügen und entfernen" description="Der Editor nutzt denselben Repeater wie andere dynamische Feldgruppen. Änderungen existieren ausschließlich im Browser und werden nicht gespeichert." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-key-value-editor-heading">Comfortable · drei Wertepaarplätze</strong><span>Native Textfelder, Add/Remove</span></div>
            <x-ui.key-value-editor name="bc-k17-attributes" label="Zusätzliche Angaben" :max="4"
                hint="Die Vorschau speichert keine Änderungen." :items="[['key'=>'Sprache','value'=>'Deutsch'],['key'=>'Quelle','value'=>'Archivbeispiel']]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · leerer Editor</strong><span>Keine Einträge</span></div>
            <x-ui.key-value-editor name="bc-k17-empty-attributes" label="Noch keine Zusatzangaben" density="compact" :items="[]" :max="2" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.key-value-editor&gt;</code><span>name, label, items[] mit key/value, min/max, hint/error, density. Komposition aus x-ui.repeater-field.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-repeater-field" aria-labelledby="bc-core-repeater-field-heading">
    <x-ui.section-heading eyebrow="75 · Wiederholbare Felder" title="Mehrere gleichartige Eingabegruppen kontrolliert verwalten" description="Strukturierte Inputs mit eindeutigen IDs und indizierten Namen, Mindest-/Maximalanzahl und Tastaturfokus nach Hinzufügen oder Entfernen." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-repeater-field-heading">Comfortable · mehrere Beteiligte</strong><span>min=1, max=4</span></div>
            <x-ui.repeater-field name="bc-k17-contributors" label="Beteiligte Personen" :min="1" :max="4"
                hint="Ein Eintrag bleibt mindestens erhalten."
                :fields="[['key'=>'name','label'=>'Name','required'=>true],['key'=>'role','label'=>'Rolle','placeholder'=>'z. B. Redaktion']]"
                :items="[['name'=>'Musterperson','role'=>'Autor:in'],['name'=>'Weitere Person','role'=>'Herausgabe']]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · optionale Gruppen</strong><span>max=2, leer</span></div>
            <x-ui.repeater-field name="bc-k17-links" label="Zusätzliche Referenzen" density="compact" :max="2"
                :fields="[['key'=>'url','label'=>'Adresse','type'=>'url'],['key'=>'note','label'=>'Beschreibung']]" :items="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.repeater-field&gt;</code><span>name, fields[] mit key/label/type/required/placeholder, items[], min/max, hint/error, addLabel, density. Ohne JS bleiben vorhandene Felder als native Eingaben sichtbar.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-transfer-list" aria-labelledby="bc-core-transfer-list-heading">
    <x-ui.section-heading eyebrow="76 · Transferliste" title="Verfügbare Einträge und ausgewählte Einträge klar trennen" description="Zwei native Mehrfachauswahllisten mit Hinzufügen/Entfernen; die ausgewählten Kennungen werden als versteckte Felder repräsentiert. Keine Datenabfrage oder automatische Speicherung." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-transfer-list-heading">Comfortable · Auswahl ändern</strong><span>Strg-Klick, Pfeiltasten, Aktionen</span></div>
            <x-ui.transfer-list name="bc-k17-permitted-categories" label="Beispielkategorien" :selected="['books']"
                hint="Mit Strg mehrere Einträge auswählen; danach Hinzufügen oder Entfernen betätigen."
                :items="[
                    ['value'=>'books','label'=>'Bücher'],
                    ['value'=>'journals','label'=>'Zeitschriften'],
                    ['value'=>'media','label'=>'Sonstige Medien mit langem Namen'],
                    ['value'=>'blocked','label'=>'Gesperrte Kategorie','disabled'=>true],
                ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · leere Auswahl</strong><span>Kein Formularversand</span></div>
            <x-ui.transfer-list name="bc-k17-empty-transfer" label="Leere Zuordnung" density="compact" :items="[]" :selected="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.transfer-list&gt;</code><span>name, label, items[] mit value/label/disabled, selected[], availableLabel/selectedLabel, hint, density. JS ändert nur lokale Optionen und versteckte Formwerte.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-import-preview" aria-labelledby="bc-core-import-preview-heading">
    <x-ui.section-heading eyebrow="77 · Importvorschau" title="Importzeilen vor dem eigentlichen Import sichtbar prüfen" description="Eine echte HTML-Tabelle mit übertragenen Werten, Zeilennummern, Textstatus und Fehlermeldungen. Keine Datei wird gelesen oder importiert." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-import-preview-heading">Comfortable · gemischte Zeilenstatus</strong><span>Bereit / Prüfen / Fehler / Übersprungen</span></div>
            <x-ui.import-preview title="Beispielhafte CSV-Vorschau"
                :columns="[['key'=>'title','label'=>'Titel'],['key'=>'author','label'=>'Autor:in'],['key'=>'year','label'=>'Jahr']]"
                :rows="[
                    ['number'=>2,'state'=>'valid','values'=>['title'=>'Ein Musterbuch','author'=>'A. Beispiel','year'=>'2024']],
                    ['number'=>3,'state'=>'warning','values'=>['title'=>'Titel mit besonders langen Angaben','author'=>'B. Test','year'=>''],'message'=>'Erscheinungsjahr bitte prüfen'],
                    ['number'=>4,'state'=>'error','values'=>['title'=>'Fehlerhafte Beispielzeile','author'=>'C. Demo','year'=>'unbekannt'],'message'=>'Ungültige Jahresangabe'],
                    ['number'=>5,'state'=>'skipped','values'=>['title'=>'Ausgelassene Zeile','author'=>'','year'=>''],'message'=>'Als Duplikat markiert'],
                ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · leer</strong><span>Kein Import verfügbar</span></div>
            <x-ui.import-preview title="Noch keine Daten" density="compact" :columns="[]" :rows="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.import-preview&gt;</code><span>title, columns[] key/label, rows[] number/state/values/message, emptyLabel, actions, density. Validierung/Import bleiben außerhalb.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-batch-progress" aria-labelledby="bc-core-batch-progress-heading">
    <x-ui.section-heading eyebrow="78 · Stapelfortschritt" title="Verarbeitungsstände mehrerer Arbeitsschritte verständlich zusammenfassen" description="Read-only-Sammelansicht mit Textstatus und optionalem echten Fortschrittswert. Nutzt x-ui.progress; ohne übergebenen Wert wird kein Prozentstand erfunden." />
    <div class="bc-core-gallery-v11__grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong id="bc-core-batch-progress-heading">Comfortable · definierter Fortschritt</strong><span>Prozent und Text</span></div>
            <x-ui.batch-progress title="Fiktive Stapelverarbeitung" :value="2" :max="4"
                :items="[
                    ['title'=>'Datei angenommen','state'=>'done'],
                    ['title'=>'Kennungen geprüft','state'=>'done'],
                    ['title'=>'Datensätze vergleichen','state'=>'running','description'=>'Keine echte Verarbeitung; nur ein Entwurf.'],
                    ['title'=>'Freigabe prüfen','state'=>'pending'],
                ]" />
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head"><strong>Compact · Fehler ohne Prozent</strong><span>Nur Statuswerte</span></div>
            <x-ui.batch-progress title="Unterbrochene Verarbeitung" density="compact"
                :items="[['title'=>'Eingabe gelesen','state'=>'done'],['title'=>'Verarbeitung fehlgeschlagen','state'=>'failed','description'=>'Fiktiver Fehlerzustand']]" />
            <x-ui.batch-progress title="Noch keine Schritte" density="compact" :items="[]" />
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-ui.batch-progress&gt;</code><span>title, items[] title/description/state: done/running/failed/pending/skipped, optional value/max, actions, emptyLabel, density. Keine Queue- oder Jobsteuerung.</span></div>
</section>
