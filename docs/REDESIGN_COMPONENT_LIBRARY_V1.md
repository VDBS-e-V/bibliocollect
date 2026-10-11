# BiblioCollect – Komponenten zuerst · Bibliothek v1

> **Status:** Visuelle Designstudie und additive Komponenten. Noch keine umfassende Migration der produktiven Fachseiten.  
> **Lokale Vorschau:** \`/design-lab/komponenten\` (\`APP_ENV=local\` oder \`testing\`).  
> **Grundsatz:** Erst jede Komponente als eigenständige Blade-/CSS-Einheit gestalten, testen und freigeben; anschließend echte Seiten aus den freigegebenen Bausteinen zusammensetzen.

## 1. Verbindlicher Umfang der aktuellen Runde

**Nur wiederverwendbare Komponenten** für alle vier Surfaces (Public, Portal, Bibliotheksbetrieb, Administration) und ihre Varianten/Zustände. **Keine** Layoutmigration, Geschäftslogikänderung, Berechtigungsänderung, Konfigurations- oder Datenbankmigration auf produktiven Seiten.

Die festen **VDBS-Farben und Schriftfamilien** sind unverändert. Zwei Dichten bleiben das gemeinsame System: \`comfortable\` (öffentliche/weite Portalbereiche) und \`compact\` (Arbeitsansichten und Portal-Medienlisten). Das persönliche Portal nutzt beide Dichten, nicht eine dritte Tokenwelt.

### Bereits bestehende Komponenten (im Review)

- \`x-ui.button\`: Primary und Secondary; im neuen Review zusätzlich \`tertiary\` und \`danger\`, mit optional \`size="sm|md|lg"\`.
- \`x-ui.card\`: bisher einfache Karte; neu opt-in \`tone="default|muted|brand|stage"\` und \`density="comfortable|compact"\`.
- \`x-ui.input\` / \`x-ui.select\`: Label, Hint, Fehlerzustand und ARIA-Zusammenhang.
  - **Neue verbindliche Reihenfolge:** Label → Eingabefeld → Fehlermeldung (falls vorhanden) → Hilfetext (falls vorhanden). Damit liegen Felder nebeneinander auf gleicher Höhe; aria-describedby verknüpft die Texte weiter mit dem Feld.
- \`x-ui.badge\`, \`x-ui.alert\`, \`x-ui.table\`, \`x-ui.page-header\`: vorhandene Basiskomponenten mit Beispielen.
- \`x-library.feature-overlap\`, \`x-library.process-stepper\`, \`x-library.stat-tile\`, \`x-library.notice-panel\`, \`x-library.media-cover\`: Fach-Patterns. Die Basisgalerie verlinkt die bestehenden Demos darunter.

### Neu angelegte universelle Basiskomponenten

| Komponente | Zweck | Varianten/API | Wichtige Regel |
|---|---|---|---|
| \`x-ui.section-heading\` | Titel einer beliebigen Inhaltssektion | \`title\`, \`eyebrow\`, \`description\`, \`level=2|3|4\`, \`compact\`, Actions-Slot | Kein zweiter H1; Semantik folgt der Seitenstruktur |
| \`x-ui.empty-state\` | Keine Daten, keine Treffer, noch keine Inhalte | \`title\`, \`description\`, \`tone=neutral|brand\`, \`compact\`, Actions-Slot | Klarer nächster Schritt; nicht als Erfolgsmeldung verkleiden |
| \`x-ui.description-list\` | Metadaten, Felder und Werte | \`items=[{label,value}]\`, \`density=comfortable|compact\` | Semantische \`dl/dt/dd\`, lange Werte umbrechen |

Die Styles der neuen Universalbausteine liegen in \`resources/css/components/foundation-components.css\`.

### Der Stepper als Standard-Pattern

Der Stepper wurde auch als **wiederverwendbare Komponente** angepasst: **Zahl/Haken links, Schritttitel rechts auf gleicher Höhe**; das horizontale Segment liegt **zwischen** den Label-Gruppen und wird jeweils sichtbar unterbrochen. Beschreibungen können unter dem Titel stehen, \`aria-current="step"\` markiert den aktuellen Schritt. Mobil wird die Darstellung vertikal; keine Schrittbezeichnungen abschneiden.

- Blade: \`resources/views/components/library/process-stepper.blade.php\`.
- CSS: \`resources/css/patterns/process-stepper-inline.css\`.
- Eingabedaten: \`steps\` mit \`label\`, optional \`description\` und \`status=done|current|upcoming|error\`.
- Der Stepper ändert **keine** Workflow-Logik.

## 2. Komponentenatlas im Designlab

Die bestehende Komponentenroute \`/design-lab/komponenten\` wurde um eine **universelle Basisgalerie** erweitert (ohne die bestehenden größeren Pattern-Beispiele zu entfernen):

1. **Dichtevergleich**: native Radiobuttons (kein JavaScript nötig), identische Demo-Elemente in \`comfortable\`/\`compact\`; CSS-Funktion \`:has()\` mit Comfortable als Fallback.
2. **Aktionen**: Button-Varianten, Größen und Disabled.
3. **Eingaben**: Text/Suche/Select, Hinweise, Validierungsfehler, Disabled.
4. **Flächen**: wiederverwendbare Card mit Tones und Dichten; \`section-heading\`.
5. **Status und Leerzustände**: Badge-/Notice-Varianten, \`empty-state\` mit CTA/ohne CTA.
6. **Metadaten**: \`description-list\` und DataTable-Preview.
7. **Patterns**: vorhandene Feature-Overlap-, Stepper-, Bento- und Cover-Demos.

**Demodaten sind fiktiv** und die Buttons innerhalb der Vorschau führen keine mutationsfähigen Vorgänge aus. Die Formfelder sind nicht Teil eines POST-Formulars. Navigation in der Galerie nutzt nur sichere Anker oder die bestehende Designlab-Route.

## 3. Gestaltungsvertrag für jede Komponente

Bevor wir eine Komponente für produktive Seiten freigeben, benötigt sie:

- **Zweck und Nichtverwendung:** Wofür geeignet, wann stattdessen ein anderes Element?
- **Blade-API:** Props, Slots, Attribute, Standardwerte, erlaubte Varianten.
- **States:** Default, Hover, Focus, Disabled, Loading, Error, Success, Empty – nur wenn sinnvoll.
- **Dichte:** Comfortable und Compact, ohne getrennte CSS-Duplikate.
- **Inhalt:** kurze, lange, fehlende Texte; lange deutsche Wörter; mehrzeilige Buchtitel.
- **Responsive:** 320/375/768/1280px, 200%/400%-Zoom und unterschiedliche Orientierung.
- **Theming:** bestehende semantische Farben und der manuelle Dunkelmodus.
- **Zugänglichkeit:** korrekte HTML-Semantik, Labels, Keyboard, sichtbarer Fokus, kontrastreiche Statuskommunikation, reduzierte Bewegung.
- **Tests:** Blade-/Featuretests und für interaktive Komponenten später Browser-/Accessibility-Prüfung.
- **Status:** \`draft → review → approved → implemented → verified\`. Neue Komponenten sind zunächst \`draft\`, bis ihre visuellen Werte freigegeben sind.

## 4. Erweiterung K2 / K3 / K4 – neue Universalbausteine (Entwurf)

Die Designlab-Galerie enthält nun zusätzlich zehn eigenständige Blade-Komponenten: `breadcrumbs`, `tabs`, `pagination`, `disclosure`, `checkbox`, `radio`, `switch`, `search-field`, `progress` und `skeleton`. Sie nutzen zentrale Styles und bestehende semantische VDBS-Tokens. Tabs sind ausdrücklich **Seitennavigation per Link**, kein unvollständig implementiertes ARIA-Tabpanel; Disclosure verwendet native `details/summary`.

Die älteren Musterseiten werden in Navigation, Übersicht und jeweiliger Musterseite deutlich als **„Älterer Entwurf“** gekennzeichnet. Als aktueller Arbeitsbereich ist auf `/design-lab` nun die Komponentenbibliothek prominent verlinkt.

Für die vollständige Umsetzungs- und Freigabematrix siehe [Komponententracker](REDESIGN_COMPONENT_TRACKER.md). **Die Komponenten sind noch nicht vollständig implementiert und nicht visuell freigegeben – finale Seiten sind ausdrücklich noch nicht an der Reihe.**

## 5. Nächste reine Komponentenrunden

| Runde | Priorität | Universelle Komponenten |
|---|---|---|
| **K1 – jetzt** | Basis | Buttons, Forms, Card/Surface, SectionHeading, EmptyState, DescriptionList, Badge/Notice, Stepper |
| **K2** | Navigation | Tabs, Breadcrumbs, Pagination, Dropdown, Popover, Tooltip, Suchfeld |
| **K3** | Formularsystem | Checkbox/Radio/Switch, Datum, Dateiupload, Autocomplete, FormSection, Fehlerzusammenfassung |
| **K4** | Dialog und States | Modal/Confirm, Toast, Loading, Skeleton, Error, Progress, BulkAction-Toolbar |
| **K5** | Datendarstellung | Table mit Sortierung/Pagination, ListRow, FilterChip, StatTile, DataViz-Grundmuster |
| **K6** | Bibliotheksbausteine | MediaCard, Availability, DueDateStatus, ScanInput, CopyRow, PatronSummary, Bibliothekscover |
| **K7** | Review | Varianten, semantische Qualität, responsive und Dark Mode; konkrete Foundations-Werte freigeben |

**Erst nach K7** beginnen wir mit gezielten echten Fachseiten (zunächst je eine repräsentative Seite pro Surface). Bis dahin bleiben deren Controller, Blade-Templates und Aktionen unangetastet.

## 6. Keine unkontrollierte Component-Duplikation

- \`x-ui.card\` bleibt die allgemeine Flächenkomponente, es gibt keine zweite Universal-\`panel\`-Komponente für dieselbe Aufgabe.
- \`x-ui.page-header\` für H1/Seitenanfang, \`x-ui.section-heading\` für H2–H4.
- \`x-ui.alert\` für einfache Hinweise, \`x-library.notice-panel\` für strukturierte Hinweise mit Icon/Aktion; vor dem Rollout die Verwendung konsolidieren.
- \`x-ui.table\` ist der semantische Tabellenwrapper, komplexe Filter/Sortierung werden als separate Patterns ergänzt.
- \`x-ui.badge\` wird nicht zu einer klickbaren Aktion umgedeutet.
- \`x-library.process-stepper\` bleibt rein visuelle Prozessanzeige, keine indirekte Routen-/Speicherlogik.

## 7. Gezielte Tests in dieser Runde

- \`tests/Feature/ComponentLibraryV1Test.php\`: Gallery, Dichteauswahl, Varianten, Blade-Verträge, sichere nicht schreibende Vorschau.
- \`tests/Feature/DesignLabPageTest.php\`: vorhandene Designlab-Routen und Stepper.
- \`npm run build\` und \`composer quality\` nach Codeänderungen; responsive und Kontrast als zusätzliche visuelle Abnahme.

Die VDBS-Marke, Ausleihfristen-/Erinnerungslogik, vorhandenen Fachrouten und produktive Daten bleiben unverändert.
