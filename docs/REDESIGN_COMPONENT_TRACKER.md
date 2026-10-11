# BiblioCollect – Komponententracker vor finalen Seiten

> **Arbeitsmodus:** COMPONENTS FIRST. Öffentlicher Katalog, Portal, Bibliotheksbetrieb und Administration werden vor der Komponenten-Freigabe **nicht** zu finalen Seiten umgebaut.
>
> **Aktives Review:** \`/design-lab/komponenten\` (nur \`local\` / \`testing\`).  
> **Altentwürfe:** \`/design-lab/startseite\`, \`/design-lab/medien\`, \`/design-lab/assistent\`, \`/design-lab/dashboard\`, \`/design-lab/portal\` und \`/design-review\`. Jede alte Vorschau ist sichtbar gekennzeichnet.
>
> **Status:** \`Bestand\` = schon vorher vorhandene Blade-Komponente, nicht zwingend fertig freigegeben; \`Entwurf\` = neu hinzugefügt oder erweitert, noch nicht visuell abgenommen; \`Offen\` = noch zu gestalten; \`Freigegeben\` = von uns nach Review ausdrücklich bestätigt; \`Verifiziert\` = fachlich, technisch und visuell geprüft. Ein grüner CI-Lauf alleine bedeutet nicht „Freigegeben“.

## 1. Was wir bereits als echte Blade-Komponenten besitzen

| Bereich | Komponenten | Status / Hinweise |
|---|---|---|
| Grundaktionen | \`x-ui.button\` (primary/secondary + opt-in tertiary/danger; Größen sm/md/lg) | Bestand + Entwurf; Defaults bleiben kompatibel |
| Grundformulare | \`x-ui.input\`, \`x-ui.select\`; Label → Feld → Fehler → Hilfe | Bestand, Reihenfolge angepasst |
| Flächen | \`x-ui.card\` + Tones/Dichten | Bestand + Entwurf |
| Überschriften | \`x-ui.page-header\`, \`x-ui.section-heading\` | Bestand + Entwurf |
| Feedback | \`x-ui.badge\`, \`x-ui.alert\`, \`x-ui.empty-state\`, \`x-library.notice-panel\` | Bestand + Entwurf |
| Strukturierte Informationen | \`x-ui.table\`, \`x-ui.description-list\` | Bestand + Entwurf |
| Navigation (K2) | \`x-ui.breadcrumbs\`, \`x-ui.tabs\` (Linknavigation), \`x-ui.pagination\` | Entwurf, Review im Designlab |
| Disclosure (K2) | \`x-ui.disclosure\` (native \`details/summary\`) | Entwurf |
| Suche (K2) | \`x-ui.search-field\` | Entwurf, ohne Fachsuche/JS |
| Auswahl (K3) | \`x-ui.checkbox\`, \`x-ui.radio\`, \`x-ui.switch\` | Entwurf, nativ/semantisch |
| Feedback-Status (K4) | \`x-ui.progress\`, \`x-ui.skeleton\` | Entwurf; Screenreadertext zusätzlich erforderlich |
| Bestehende Bibliothek-Patterns | \`x-library.feature-overlap\`, \`x-library.process-stepper\`, \`x-library.stat-tile\`, \`x-library.media-cover\`, \`x-library.catalog-search\`, \`x-library.quick-action\` | Bestand, schrittweise reviewen |

**Zehn neue K2/K3/K4-Komponenten** sind in \`resources/views/components/ui\` hinzugekommen: \`breadcrumbs\`, \`tabs\`, \`pagination\`, \`disclosure\`, \`checkbox\`, \`radio\`, \`switch\`, \`progress\`, \`skeleton\`, \`search-field\`. Gemeinsame Styles in \`resources/css/components/foundation-components-v2.css\`. Keine produktive Seitenmigration.

### Komponentenschritt K3b / K5a – neu im Review

Sieben weitere fachneutral verwendbare Blade-Komponenten sind im Designlab unter `/design-lab/komponenten` sichtbar:

| Baustein | Einsatz | Status |
|---|---|---|
| `x-ui.textarea` | Mehrzeiliger Text mit Rows, Maxlength, Error und Hint | Entwurf |
| `x-ui.file-upload` | Nativer, gelabelter Datei-Input mit Accept, Multiple, Error und Hint | Entwurf; Speicherung/Dateiprüfung bleibt Aufgabe der echten Anwendung |
| `x-ui.autocomplete` | Native `datalist`-Vorschläge für kleine bekannte Listen | Entwurf; **keine** vollständige asynchrone ARIA-Combobox |
| `x-ui.form-section` | Semantisches `fieldset` mit `legend` und Beschreibung | Entwurf |
| `x-ui.error-summary` | Fehlerüberblick mit Links zu Formularfeldern | Entwurf; in echten Formularen mit Servervalidierung und Fokusmanagement verbinden |
| `x-ui.filter-chip` | Aktive/nicht aktive Filter; Link nur bei vorhandenem Ziel | Entwurf |
| `x-ui.list-row` | Universelle Zeile mit Titel, Meta, optionalem Link und Media-/Trailing-Slots | Entwurf |

Gemeinsame Styles: `resources/css/components/foundation-components-v3.css`. Demonstrationsansicht: `resources/views/pages/partials/design-lab-components-v3.blade.php`. Featuretests: `tests/Feature/ComponentLibraryV3Test.php`.

**Nicht automatisch abgeschlossen:** Vollständiger Autocomplete mit asynchroner Suche, Drag-and-drop-Upload, Datums-/Zeit-Picker für Sonderfälle, Fehler-Fokus nach Redirect sowie komplexe Sortier-/Tabellenfilter brauchen eigene Prüfung. Native Datums-/Zahleneingaben sind bereits durch den vorhandenen `x-ui.input`-Typ prop demonstriert.

### Komponentenschritt K2b / K4b – Interaktion und Dialoge

Weitere **sechs** wiederverwendbare Blade-Bausteine sind in der aktiven Komponentenbibliothek sichtbar:

| Baustein | Anwendung | Status |
|---|---|---|
| `x-ui.action-menu` | Aktionslinks über native `details/summary` mit Disabled und Trennlinien | Entwurf |
| `x-ui.popover` | Kontextuelle Informationen mit freiem Slot, nativer `details/summary` | Entwurf |
| `x-ui.tooltip` | Kurzhilfe bei Hover/Fokus, `aria-describedby` mit eindeutiger ID | Entwurf |
| `x-ui.dialog` | Allgemeiner nativer `dialog`, offene API für Inhalte/Actions, fokussierte Modaldarstellung | Entwurf |
| `x-ui.toast` | Kurzstatus/Fehler mit passendem `role=status/alert`, optionaler Dismiss-Aktion | Entwurf |
| `x-ui.spinner` | Indeterminierter Ladezustand mit lesbarem Statuslabel und Reduced-Motion-Fallback | Entwurf |

CSS: `resources/css/components/foundation-components-v4.css`; kleine gemeinsame JS-Steuerung für Menüs, Dialoge und Dismiss in `resources/js/ui-interactions.js`, importiert von `app.js`. Designlab: `resources/views/pages/partials/design-lab-components-v4.blade.php`. Tests: `tests/Feature/ComponentLibraryV4Test.php`.

**Keine Duplikation der produktiven Bestätigungsfunktion:** Der vorhandene `data-confirm`-Handler bleibt für echte Aktionen zuständig; `x-ui.dialog` zeigt hier nur Präsentation und API. Reale Fachaktionen, Bestätigungsfolgen und Erfolgsmeldungen werden erst bei der späteren Seitenintegration angebunden.

**Statusgestaltungs-Review (nach Nutzerfeedback):** Der generische Dialog `x-ui.dialog` nutzt jetzt **10px Radius**; bei `tone=warning` gibt es **keine volle obere Farblinie**, sondern einen kurzen Akzent im Header. Toasts (`x-ui.toast`) nutzen **8px Radius**, dezente Tönung mit bestehenden semantischen Statusfarben und eine kurze 44px-Akzentleiste oben links **anstelle der langen Seitenlinien**. Der Dialogfooter kommt ohne zusätzliche Trennlinie aus. Die Änderungen sind in `foundation-components-v4.css` enthalten und betreffen die Opt-in-Komponenten, nicht die produktive Bestätigungslogik. Tests: `ComponentLibraryV4Test.php`. **Die gestalterische Richtung wurde vom Projektverantwortlichen bestätigt; technische und vollständige visuelle Freigabe aller Zustände bleiben offen.**

**Weitere Reviewpunkte:** Fokusführung in komplexen Dialogen, Tooltip-Touchverhalten, Menüpositionierung in engen Containern, fokussierbare Popover-Inhalte und automatische Live-Feedback-Anbindung. „Entwurf“ bleibt korrekt bis zur interaktiven Browser- und Accessibility-Abnahme.

### Komponentenrunde K5/K6 – Datenbausteine und Bibliotheksstatus

Sieben weitere fachneutral nutzbare Komponenten/Patterns sind als **Entwurf im Review** angelegt und in der aktiven Galerie `/design-lab/komponenten` sichtbar:

| Komponente | Verwendung | Umsetzung |
|---|---|---|
| `x-ui.filter-toolbar` | Trefferzahl, Filter-Slot und Actions-Slot | Comfortable/Compact; reine Struktur, keine Query |
| `x-ui.bulk-action-bar` | Anzahl ausgewählter Einträge mit optionaler Aktionsleiste | Aktions-Slot erst bei `selected > 0`; keine Fachmutation |
| `x-ui.sort-header` | Semantischer Tabellenkopf mit `scope=col`, `aria-sort` und optional echtem Link | Sortierlogik bleibt beim aufrufenden Controller |
| `x-library.media-card` | Cover, lange Titel, Autor:innen und Status-/Actions-Slot | Comfortable/Compact; bestehendes `x-library.media-cover` wiederverwendet |
| `x-library.availability-status` | Verfügbar / ausgeliehen / vorgemerkt / nicht verfügbar / unbekannt | Bedeutung immer als Text, nicht allein als Farbe |
| `x-library.due-date-status` | 8+ neutral, **7–1 Tage bald fällig**, heute, überfällig, unbekannt | Prop `daysRemaining` (ganze Kalendertage); **visuelle 7-Tage-Regel**, keine E-Mail-/Ausleihlogik |
| `x-library.scan-input` | Barcode-Feld mit Label, Hint, Error und Keyboard-Fokus | Hardware-Anbindung bleibt späterem Workflow vorbehalten |

Komponenten-CSS: `resources/css/components/foundation-components-v5.css` und `resources/css/patterns/library-data-components.css`. Designlab: `resources/views/pages/partials/design-lab-components-v5.blade.php`. Demointeraktion für Mehrfachauswahl: `resources/js/ui-data-previews.js` (nur wenn `data-ui-bulk-demo` vorhanden). Tests: `tests/Feature/ComponentLibraryV5Test.php`.

**Die Darstellung ist unabhängig von Geschäftsdaten:** Ein aufrufender Controller muss den Tagesabstand korrekt nach Kalenderdatum und Zeitzone bestimmen; das reine Aussehen sagt nichts über die Verlängerungsberechtigung aus. Echte Datentabellen brauchen sortierte Datensätze und echte Sortierlinks. In der Galerie sind Daten/Links fiktiv; die deaktivierte Sammelaktion schreibt nichts.

**Noch offen:** Serverseitige Auswahl über mehrere Ergebnisseiten, sortierte/paginierte Controller-Queries, Filter-Sidebar, Scanner-Hardware und Scanbestätigung, barrierefrei nutzbare mobile Tabellen-/Listenansicht sowie vollständige Responsive- und Screenreader-Abnahme. Die Komponenten sind nicht produktiv freigegeben.

## 2. Noch notwendige Komponentenfamilien und Patterns

| Batch | Gegenstand | Konkrete Unterelemente | Status |
|---|---|---|---|
| K2b Navigation | Dropdown/Aktionsmenü, Tooltip und Popover als Entwurf; **offen**: mobile Navigation und globale Seitentoolbar, platzadaptive Menüpositionen | Schließen per Escape, Fokus und Klick außerhalb; zugängliche Bedienung | Teilweise umgesetzt · Review |
| K3b Formulare | Textarea, native Datums-/Zahleneingabe, `datalist`, Upload, FormSection, ErrorSummary bereits als Entwurf; **noch offen**: echte asynchrone Combobox, dynamischer Zeichenzähler, erweiterte File-Interaktion und Upload-Validierung | Browser-Autofill, Hilfetexte und Error-IDs, Drag/Drop-Fallback | Teilweise umgesetzt · Review |
| K4b Feedback/Overlay | Allgemeiner Dialog, Toast und Spinner als Entwurf; bestehender Confirm bereits vorhanden; **offen**: live erzeugte Feedback-Region, Busy/Read-only, Bulk-Aktionen, komplexere Dialoge | Dialogfokus, Escape/Close, Async-Feedback | Teilweise umgesetzt · Review |
| K5 Daten | Filter-Chips, Filter-Toolbar, SortHeader, BulkActionBar und Listenzeilen als Entwurf; noch offen: Filter-Sidebar, echte datengetriebene Sortierung/Pagination, Zeitlinie, Diagramme und barrierefreie Datenalternative | Longtext, 320px und datengetriebene Status | Teilweise umgesetzt · Review |
| K6 Bibliothek | MediaCard, Verfügbarkeit, Fälligkeitsstatus mit 7-Tage-Warnung und ScanInput als Entwurf; noch offen: Exemplar-/Personenzeile, Cover-Varianten, Personenauswahl und Scannerintegration | Fachinformationen strikt vom Präsentations-API trennen | Teilweise umgesetzt · Review |
| K7 Spezial / Ausgabe | Inline-Edit, Hierarchien, Kalender-/Öffnungszeiteneditor, Etikett/Druck, Barcodes, Mailkomponenten und Integrationsmuster | Druck/Theme/Fokus | Offen |
| K8 Qualität | Alle Bausteine mit Variants, Dichten, mobilen Layouts, Dark Mode, Tastatur, Screenreader, Zoom, hohem Kontrast und Fehlerfällen prüfen | Freigabe erst nach sauberem Review | Offen |

**Nicht jedes Tabellenfeld, jeder Workflow oder jede Seite wird eine eigene Komponente.** Kleine Komponenten werden kombinierbar; komplexe fachliche Workflows werden erst später aus freigegebenen Bausteinen gebaut.

## 3. Abnahmekriterien – der Übergang zu finalen Seiten

Finale Seiten entstehen **erst**, wenn:
1. Alle benötigten universellen Komponenten der K2–K7-Liste implementiert oder ausdrücklich als nicht benötigt gestrichen sind.
2. Jede verwendete Komponente einen dokumentierten API-/Varianten-/Statusvertrag besitzt.
3. Die Grundlagen – Typografie, Spacing, Radien, Höhen, Dichteprofile, Theme – visuell freigegeben sind; Farben und Schriftfamilien bleiben unverändert.
4. Screenshots und manuelle Vergleiche für 320/375/768/1280px, Dunkelmodus, 200/400%-Zoom, Tastaturfokus und lange deutsche Texte abgenommen sind.
5. Keine unaufgelösten gravierenden Barrierefreiheitsprobleme in den betroffenen Komponenten bestehen.
6. Regressionstests für Blade und die späteren Integrationspunkte bereitstehen; projektweite CI-Funde sind dokumentiert und priorisiert.
7. Der Komponentenatlas die maßgebliche Referenz ist, ältere Entwürfe klar gekennzeichnet sind und der Nutzer die Bibliothek ausdrücklich zur weiteren Seitenentwicklung freigibt.

**Die Abnahme ist eine eigene Entscheidung, kein automatischer Zustand nach dem letzten Commit.**

## 4. Reviewreihenfolge

Zuerst \`/design-lab/komponenten\`: Dichte, Buttons, Formulare, Flächen, Badges, Tabellen; dann die neue Navigation, Auswahl, Ladezustände und Akkordeons. Nach jeder Runde konkrete Reviewfragen beantworten und Änderungen vor Übernahme in die Standard-API prüfen.

Andere Designlab-Musterseiten bleiben Vergleichsmaterial, aber werden während dieser Phase **nicht** als finale Vorlagen oder vollständig auf neue Tokens migrierte Seiten bezeichnet.
