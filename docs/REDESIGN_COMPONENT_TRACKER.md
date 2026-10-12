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

**Gestalterische Entscheidung nach Review:** Die Status-Pills von Verfügbarkeit und Fälligkeit dürfen farbkräftig sein: Verfügbar nutzt gesättigtes VDBS-Grün mit Ink-Text, Vorgemerkt VDBS-Lila mit Weiß, Fälligkeit innerhalb von sieben Tagen kräftiges semantisches Orange mit Ink-Text und Überfällig das VDBS-Fehlerrot mit Weiß plus Ausrufezeichen. Normale Ausleihen und unbekannte Zustände bleiben bewusst zurückhaltend. Die 7-Tage-Grenze bleibt unverändert. Die Scanner-Eingabe färbt **nur ihren bestehenden äußeren Rahmen beim Fokus lila** (2 px); doppelte Außen-Outlines und Innen-Rahmen werden unterbunden, auch gegen die generische Designlab-Fokusregel. Beide Bausteine erhalten weiterhin verständliche Textlabels; die VDBS-Farbpalette und Schriftfamilien bleiben unverändert. Die Farbentscheidung entspricht dem Wunsch „Wir wollen bunt!“; kräftige Statusfarben sind gezielte semantische Signale, keine beliebige Verwendung der Gruppenfarben als Dekoration.

**Noch offen:** Serverseitige Auswahl über mehrere Ergebnisseiten, sortierte/paginierte Controller-Queries, Filter-Sidebar, Scanner-Hardware und Scanbestätigung, barrierefrei nutzbare mobile Tabellen-/Listenansicht sowie vollständige Responsive- und Screenreader-Abnahme. Die Komponenten sind nicht produktiv freigegeben.

### Komponentenrunde K6b – Exemplar, Personenauswahl und Scanfeedback

Drei weitere echte Blade-Komponenten sind in `/design-lab/komponenten` als **Entwurf/Review** verfügbar:

| Komponente | Zweck und API | Stand |
|---|---|---|
| `x-library.copy-row` | Barcodedaten, Exemplarstandort und Status; `title`, `barcode`, `location`, `availability`, `dueDays`, `density`, optional `href` und Actions-Slot | Comfortable/Compact und lange Titel; verwendet die bestehenden Verfügbarkeits-/Frist-Komponenten |
| `x-library.patron-choice` | Native Radioauswahl einer Person; `name`, `value`, `displayName`, `identifier`, `detail`, `checked`, `disabled`, `density` | Selectable mit Tastatur, zwei Dichten, keine automatische Fachsuche |
| `x-library.scan-feedback` | Scannerstatus nach Fachsystemantwort; `title`, `message`, `barcode`, `status=info\|success\|warning\|error`, `compact` | Kräftige VDBS-Farbsymbole, Rollen `status`/`alert`, ohne längliche Statusleisten |

Vorführdatei: `resources/views/pages/partials/design-lab-components-v6.blade.php`; Komponenten-CSS: `resources/css/patterns/library-data-components-v2.css`; Tests: `tests/Feature/ComponentLibraryV6Test.php`. Die Daten bleiben ausdrücklich fiktiv, es gibt keine Ausleih- oder Scan-Nebenwirkungen.

**Scanner-Fokus ohne Layoutsprung (nach Review korrigiert):** Der ruhende `x-library.scan-input` reserviert je **1px Margin** bei 1px Border. Auf **Hover/Fokus** entfallen die Margins, der **bestehende** Rahmen wird 2px lila. Die Mindesthöhe kompensiert den Wechsel (Ruhezustand `calc(3.25rem - 2px)`, Hover/Fokus `3.25rem`), das innere Eingabefeld bleibt rahmenlos. Dadurch sollen die nachfolgenden Formularelemente ihre Position behalten. CSS und Regressionstests in `library-data-components.css`/`ComponentLibraryV5Test.php` sowie `ComponentLibraryV6Test.php`.

**Noch offen:** Reale Personensuche mit serverseitiger Berechtigung, Personendaten-Privatsphäre, Scannerhardware, mehrfach gefundene Exemplare, Bestätigungsdialoge nach echten Serverantworten, mobile-/Dark-Mode-/Zoom-/Tastatur-Abnahme sowie visuelle Freigabe. Die echten Seiten werden weiterhin nicht migriert.

### Komponentenrunde K7a – Covervarianten und universelle Verwaltungspatterns

Die vorhandene `x-library.media-cover` wurde um **opt-in Größenstile für Thumbnail und Small** ergänzt. Bereits vorhandene Varianten `plum`, `mint`, `ink`, `sand`, `normal` und `hero` bleiben erhalten; echte Coverdateien haben weiterhin Vorrang. Das vollständige Titel-Feld bleibt semantisch außerhalb des ggf. gekürzten Ersatzcovers. **Keine zweite Cover-Komponente.**

Fünf neue fachneutrale Präsentationsbausteine (plus ein internes rekursives Tree-Node-Partial) werden unter `/design-lab/komponenten` gezeigt:

| Komponente | Zweck | Review-Status |
|---|---|---|
| `x-ui.inline-edit` | Einzelnes Feld per nativen `details/summary` zum Bearbeiten aufklappen, bestehendes `x-ui.input` wiederverwenden | Entwurf; kein eigener Save-Handler, optionaler Actions-Slot |
| `x-ui.hierarchy-tree` + internes `x-ui.hierarchy-node` | Verschachtelte Listen, aufklappbare Bereiche, Linkziele und aktive Bereiche | Entwurf; keine Drag-and-drop- oder Mutationslogik |
| `x-ui.week-schedule` | Öffnungszeiten und wiederkehrende Zeitfenster aus Daten anzeigen, Comfortable/Compact | Entwurf; keine Ferien-/Feiertags- oder Speicherlogik |
| `x-library.shelf-label-preview` | Regal- und Rückenschilder für den Designvergleich | Entwurf; **keine** alternative produktive Druckroutine/echter Barcode |
| `x-ui.message-preview` | Empfänger, Betreff und Nachrichtenkörper für die Abnahme visualisieren | Entwurf; keine Versand-/Empfängerberechtigungslogik |

Die bestehenden Druckvorlagen unter `resources/views/pages/surfaces/administration/shelves/labels*.blade.php` und `resources/views/pages/surfaces/pos/labels` bleiben unberührt. Dasselbe gilt für reale Kalender, Dokumentenversand, Benutzerkonten und produktive Medienansichten.

CSS: `resources/css/patterns/library-admin-components.css` und ergänzte `media-cover.css`. Galerie: `resources/views/pages/partials/design-lab-components-v7.blade.php`. Tests: `tests/Feature/ComponentLibraryV7Test.php`.

**Noch offen:** echte Bearbeitung mit Rechteprüfung/Formvalidierung, Hierarchiesortierung, Kalendereditor und Ausnahmeregeln, Druckmaße/Scanqualität, sichere dynamische E-Mail-Templates und mobile/Zoom/Dark-Mode-Abnahme. Erst nach Abnahme in produktive Seiten integrieren.

### Komponentenrunde K8a – Navigation, Filter, Daten, Ladezustände und Review

**Größere Components-first-Runde:** Sieben neue wiederverwendbare Blade-Bausteine, eine rückwärtskompatible Erweiterung an der vorhandenen Textarea, bessere Galerie-Navigation und eine sichtbare Review-Matrix:

| Baustein | API / Zustand | Abgrenzung |
|---|---|---|
| `x-ui.page-toolbar` | `title`, `description`, Actions-Slot, `density=comfortable/compact` | Für öffentlich, Portal und intern kombinierbar |
| `x-ui.mobile-navigation` | `label`, `items[]` inkl. `href/active/disabled` | Native `details/summary` + gemeinsames Escape-/Außenklick-Handling; nur Links |
| `x-ui.filter-panel` | `title`, `count`, `open`, `clearHref`, beliebiger Filter-Slot | Ohne Submit-/Fachlogik; Filter zählen durch aufrufenden Fachbereich |
| `x-ui.activity-timeline` | `title`, `items[]`, `compact`, optionales `datetime` und Status | Semantisches `ol` und `time`; Textstatus unabhängig von Punktfarben |
| `x-ui.metric-bars` | `title`, `items[]` mit `label/value/tone`, optional `max` | Farbige Balken **plus ständig sichtbare echte HTML-Datentabelle**, kein Canvas-Only-Chart |
| `x-ui.busy-panel` | `title`, `description`, `busy`, `compact`, Slot für fertigen Zustand | `aria-busy`, wiederverwendeter Spinner/Skeleton; keine erfundenen Prozentwerte |
| `x-ui.file-drop-zone` | `name`, `label`, `hint`, `accept`, `multiple` | Native Dateiauswahl; optionales Drag-and-drop zeigt Dateinamen, **kein Upload** |

**Bestehende Textarea erweitert:** `x-ui.textarea` kann optional `showCount` mit `maxlength` anzeigen. Der Anfangszähler funktioniert serverseitig; über `data-ui-char-count` folgt die aktuelle Länge bei Eingabe. Ohne `showCount` bleibt die bisherige Ausgabe unverändert.

Die bisher sehr lange Sprungnavigation der Komponentenbibliothek wurde in **sieben Kapitel** aufgeteilt: Grundlagen, Navigation, Formulare, Daten und Status, Bibliothek, Verwaltung, Qualität. Die Kapitel nutzen native `details`, schließen per Escape/Außenklick und sind auf schmalen Bildschirmen responsiv.

**Aktiver Reviewbereich:** `/design-lab/komponenten`; neue Sektionen 32–38 einschließlich einer nicht gespeicherten **manuellen Prüfliste** für 320/375/768/1280px, Zoom, Dunkelmodus, Tastatur und Screenreader. Häkchen sind ausdrücklich **keine** Abnahme oder verifizierter Testlauf.

CSS: `resources/css/components/foundation-components-v8.css`, `resources/css/patterns/design-lab-core-components.css`. Interaktions-JS: `resources/js/ui-foundations-interactions.js`; Tests: `tests/Feature/ComponentLibraryV8Test.php`. Beispiele: `resources/views/pages/partials/design-lab-components-v8.blade.php`. Produktive Seiten und Geschäftslogik bleiben unverändert, VDBS-Farben und Schriftfamilien fest.

**Offene Qualitätspunkte:** Tatsächliche Browser-/Screenreaderprüfung aller neuen Komponenten in hell/dunkel, 320–1280px und 200–400% Zoom. Echte Filterparameter, Sortier-/Paging-Queries, Server-Uploadvalidierung, optional asynchrone Combobox, Tastaturdetails komplexer Overlays und dynamische Live-Notifications bleiben separate Integrations- oder Komponentenaufgaben. Die Statuswerte im Tracker bezeichnen **Entwürfe**, keine finale Freigabe.

### Komponentenrunde K9 – Vier Ergänzungen, Statussprache und Barrierefreiheit

Vier zusätzliche, allgemeingültige Blade-Komponenten sind als **Entwurf/Review** in `/design-lab/komponenten` eingebaut:

| Komponente | Verwendung | Abgrenzung |
|---|---|---|
| `x-ui.date-range` | Native Datumsfelder „Von“/„Bis“ unter einem `fieldset/legend`, mit `name`, `label`, `from/to`, `min/max`, `hint/error` und `density` | Hinweise **unter** beiden gleich hohen Feldern, Gruppenfehler verlinkt; spätere fachliche Datums-/Zeit-/Berechtigungsvalidierung bleibt separat |
| `x-ui.segmented-choice` | Eine aus wenigen Ansichts-/Dichte-Optionen auswählen, `options[]`, `selected`, `density` | Native Radiofelder statt künstlichem ARIA-Tab-Pattern; Auswahl allein verändert nicht ungefragt andere Inhalte |
| `x-ui.record-card` | Langer Datensatztitel, Metadaten als echtes `dl/dt/dd`, Status-/Actions-Slots, Comfortable/Compact | Mobile, zugängliche **Darstellungsalternative** zur breiten Fachdatentabelle; die Daten kommen später aus dem Fachbereich |
| `x-ui.status-legend` | Kräftige VDBS-Farben mit expliziter Textbedeutung in Bibliothekslisten und Diagrammen | `green/purple/orange/red/blue/neutral`, unbekannte Farben fallen auf neutral zurück; keine Bedeutung allein durch Farbe |

Zusätzlich wurden vorhandene `x-ui.empty-state` und `x-ui.pagination` im Designlab zu einer Mustersektion kombiniert; **keine zweiten Versionen dieser Komponenten**. Neue Abschnitte **39–43** und navigierbare Kapitelverweise. CSS: `resources/css/components/foundation-components-v9.css` und Layoutregeln in `resources/css/patterns/design-lab-core-components.css`. Blade-Tests: `tests/Feature/ComponentLibraryV9Test.php`.

**Konkrete Accessibility-Korrektur:** Axe meldete am gemeinsamen Footer ein Kontrastverhältnis von ca. **3,98:1** für `.bc-footer-brand small`. Der frühere `opacity:.8`-Stil ist entfernt, der Text auf volles vorhandenes VDBS-Weiß gesetzt (`shell-redesign.css`), ohne Änderung der Palette oder Schriftfamilien. Das repariert einen gemeldeten konkreten Fehler, **ersetzt aber nicht den erneut erforderlichen kompletten Axe-/Layout-Check**.

**Noch offen:** Mobile-/Dunkelmodus-/Zoom-/Screenreader-Sichtprüfung der K9-Komponenten, Fachlogik für Filterzeitraum und Paginierung, Datenanbindung, ggf. weitere Kontrast-/Overflow-Funde und vollständige Accessibility-Abnahme. Die Änderungen betreffen zunächst nur Komponenten und Designlab; produktive Seiten bleiben unverändert.

### Komponentenrunde K10 – Suchergebnisse, Paging, Schrittnavigation und Detailkontext

**Vier neue, fachneutral wiederverwendbare Blade-Komponenten**, keine zweite Implementierung von `empty-state`, `pagination`, `process-stepper` oder `page-toolbar`:

| Komponente | API und Verhalten | Abgrenzung |
|---|---|---|
| `x-ui.result-summary` | `count`, `query`, `label`, `description`, `density`, optionale `filters`-/`actions`-Slots | Ergebniszusammenfassung mit 0/1/n Treffern; für tatsächlichen Leerzustand bestehendes `x-ui.empty-state` verwenden |
| `x-ui.page-size-selector` | `name`, `value`, `options`, `label`, `hint`, `density` | Echtes gelabeltes `select` ohne eigenes GET-Formular oder Autosubmit. Mit vorhandener `x-ui.pagination` kombinieren |
| `x-ui.step-actions` | `previous`, `next`, `cancel`, konfigurierbare Beschriftungen, `label`, `density` | Vor-/Zurück-/Abbruchlinks getrennt vom vorhandenen `x-library.process-stepper`. Ohne URL nicht anklickbar, kein Pseudo-Link |
| `x-ui.context-strip` | `title`, `items[]` (`label/value`), `backHref`, `backLabel`, `label`, `density`, `status`-/`actions`-Slots | Schmale Detailkontextleiste mit echtem `dl/dt/dd`, kein zweiter allgemeiner Pageheader |

**Review im lokalen Designlab:** `/design-lab/komponenten`, Abschnitte **44–47**, im Menü „Navigation“ und „Daten und Status“. CSS: `resources/css/components/foundation-components-v10.css`; Beispiele: `resources/views/pages/partials/design-lab-components-v10.blade.php`; Tests: `tests/Feature/ComponentLibraryV10Test.php`.

**Noch nicht produktiv:** Die Ergebniszahl ist Demo, Seitengrößenauswahl speichert nicht und wechselt keine Ergebnisseite, Demo-Links führen nur zu anderen Komponenten; der Schrittwechsel führt keine Geschäftsfunktion aus. Für die echte Integration sind Controller-seitige Parameter-/Rechteprüfung, stabile Ergebnis-URLs, anwendungsseitige Formularzustände und Navigation erforderlich. Auch für diese Komponenten sind visuelles Review, Dunkelmodus, 320/375/768/1280px, Tastatur und Screenreader **noch nicht vollständig abgenommen**.

### Komponentenrunde K11 – hilfreiche Ergebniszustände, Seitensprünge, Prüfstände und Vergleich

**Vier weitere echte Blade-Komponenten im bestehenden Draft-PR**, weiterhin ausschließlich **Entwurf/Review**. K10 enthielt bereits Ergebnisübersicht, Seitengröße, Schrittaktionen und Detailleiste. K11 setzt deshalb bewusst andere Aufgaben um:

| Komponente | API / Zustände | Abgrenzung und Interaktion |
|---|---|---|
| x-ui.result-assistance | title, description, query, state=empty/error/unavailable, suggestions[] mit href/disabled, actions-Slot, density | Hilfen für fehlende, fehlerhafte oder gesperrte Ergebnismengen. **Komponiert** x-ui.empty-state und x-ui.alert statt beides zu duplizieren; nur echte Alternativlinks, keine erfundene Retry-Abfrage. |
| x-ui.section-index | title, label, items[] mit label/href/disabled, current, density | In-Page-Ankernavigation für lange Detailansichten. Native Fragmentlinks, aria-current=location aktualisiert sich bei Fragmentwechsel, gesperrte Einträge sind keine Links. Nicht x-ui.tabs oder x-ui.mobile-navigation. |
| x-ui.review-overview | title, label, items[] (title/description/status/href/disabled), actions-Slot, density | **Nichtlinearer** Prüfstand mit complete/current/attention/pending und textlicher Statusangabe statt weiterer Prozess-Stepper. Zahl geprüfter Bereiche wird rein aus Props berechnet, keine Speicherung. |
| x-ui.change-review | title, label, items[] (label/before/after/status), beforeLabel, afterLabel, emptyLabel, actions, density | Zugänglicher Vorher-/Nachher-Vergleich mit echten dl/dt/dd-Werten, Langtext, hinzugefügt/entfernt/gleich und leeren Daten. Kein Duplikat von x-ui.description-list oder x-ui.context-strip: Fokus auf **Differenzen**. |

**Designlab:** /design-lab/komponenten, neue Abschnitte **48–51** unter den Kapiteln Daten und Status, Navigation sowie Verwaltung. Comfortable/Compact, schmale Ansichten, Langtext, Fehler, Disabled und leere Daten sind als Beispiele vorhanden. Die neuen CSS-Klassen stehen opt-in in resources/css/components/foundation-components-v11.css; JS für aktuelle Fragmentposition in resources/js/ui-section-index.js. VDBS-Tokens, Schriftfamilien, Markenfarben, produktive Controller/Seiten und Datenbankschema sind unverändert.

**Automatisierte Regressionen:** tests/Feature/ComponentLibraryV11Test.php prüft Designlab-Rendering, Alert-/Empty-State-Komposition, HTML-Semantik, echte/gesperrte Links, Statuszustände, sichtbaren Text und strukturierten Vergleich. **CI je Commit gesondert auswerten**: bekannter OperationsCommandsTest.php-Cronfehler ist keine allgemeine Freigabe; bisherige a11y/Layout-Befunde werden nicht stillschweigend als behoben ausgewiesen.

**Manuelle K11-Abnahme offen:** Fokus/Fragmentwechsel, 320/375/768/1280px, 200/400%-Zoom, Light/Dark, Kontrast der kräftigen Statusflächen, langer deutscher Titel, nativ vorgelesene Abschnitts- und Vergleichssemantik. Keine automatisierte Fachsuche, keine Speicherung und keine Seitenfreigabe.

## 2. Noch notwendige Komponentenfamilien und Patterns

| Batch | Gegenstand | Konkrete Unterelemente | Status |
|---|---|---|---|
| K2b Navigation | Aktionsmenü, Tooltip, Popover sowie **mobile Navigation und Seitentoolbar** als Entwurf; offen: adaptive Overlay-Positionierung und echte mobile Integration | Schließen per Escape, Fokus und Außenklick; zugängliche Bedienung | Teilweise umgesetzt · Review |
| K3b Formulare | Textarea mit **opt-in Zeichenzähler**, native Datums-/Zahleneingaben, `datalist`, Upload, **File-Drop-Zone**, FormSection und ErrorSummary als Entwurf; offen: asynchrone Combobox und serverseitige Uploadvalidierung | Browser-Autofill, Hilfetexte, Error-IDs, Drag/Drop-Fallback | Teilweise umgesetzt · Review |
| K4b Feedback/Overlay | Dialog, Toast, Spinner, **Busy-Panel** und vorhandener Confirm als Entwurf/Bestand; offen: echte dynamische Live-Meldungen, Read-only-/Bulk-Prozesse und komplexe Dialoge | Dialogfokus, Escape/Close, Async-Feedback | Teilweise umgesetzt · Review |
| K5 Daten | Filter-Chips, Toolbar, **Filterpanel**, SortHeader, BulkActionBar, Listenzeilen, **Timeline und Balkendiagramme mit Datentabelle** als Entwurf; offen: produktive Datenanbindung, Sortierung/Pagination und komplexe Diagramme | Longtext, 320px und datengetriebene Status | Teilweise umgesetzt · Review |
| K6 Bibliothek | MediaCard, Verfügbarkeit, Fälligkeitsstatus, ScanInput, **Exemplarzeile, Personenauswahl und Scannerfeedback als Entwurf**; noch offen: Cover-Spezialvarianten, echte Personensuche und Scannerintegration | Fachinformationen strikt vom Präsentations-API trennen | Teilweise umgesetzt · Review |
| K7 Spezial / Ausgabe | Inline-Edit, Hierarchie, Wochenplan, Etiketten- und Nachrichtenvorschau als Entwurf; **offen**: echte Editoren, sichere Fachaktionen, Druckmaße/Barcodes, Versand und Integrationen | Druck/Theme/Fokus | Teilweise umgesetzt · Review |
| K8 Qualität | **Manuelle Review-Matrix und Featuretests vorhanden**; offen: echte Browser-/Responsive-/Dark-Mode-/Screenreader-/Zoom-/Fokusabnahme aller relevanten Zustände | Freigabe erst nach sauberem Review | Teilweise vorbereitet · keine Freigabe |

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

### Komponentenrunde K12 – Auswahlkarten, Anlagenliste und Kriterienprüfung

Drei neue, fachneutrale **Entwurfskomponenten** in `/design-lab/komponenten` (Abschnitte 52–54):

| Komponente | Schnittstelle | Abgrenzung |
|---|---|---|
| `x-ui.choice-cards` | `name`, `label`, `options[]`, `selected`, `multiple`, `hint/error`, `density` | Native Radio-/Checkbox-Felder, große berührbare Optionskarten; im Gegensatz zu `segmented-choice` mit längeren Erklärungstexten. Keine Speicherung. |
| `x-ui.attachment-list` | `title`, `items[]` mit Name, Typ, Größe, Beschreibung, URL/Disabled; `density`, Actions-Slot | Metadaten bereits vorhandener Dateien, keine zweite Uploadkomponente, keine Dateiprüfung. Links nur bei echtem Ziel. |
| `x-ui.criteria-checklist` | `items[]`, `state=met/unmet/pending/not-applicable`, `density`, Actions-Slot | Textliche Kriterienerfüllung statt Bearbeitungsfortschritt; keine automatische Validierung. |

Styles: `resources/css/components/foundation-components-v12.css`; Vorschau: `resources/views/pages/partials/design-lab-components-v12.blade.php`; Tests: `tests/Feature/ComponentLibraryV12Test.php`. VDBS-Tokens bleiben unverändert, Fachseiten sind nicht betroffen. **Keine Freigabe ohne manuellen Review.** Die schon vor K12 bekannte Layoutprüfung auf mobilen Viewports ist weiterhin offen.
