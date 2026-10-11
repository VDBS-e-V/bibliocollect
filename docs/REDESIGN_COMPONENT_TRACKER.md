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

## 2. Noch notwendige Komponentenfamilien und Patterns

| Batch | Gegenstand | Konkrete Unterelemente | Status |
|---|---|---|---|
| K2b Navigation | Dropdown-/Overflow-Menü, Tooltip, Popover, mobile Navigation, globale Seitentoolbar | echtes Schließen per Escape, Fokus und Klick außerhalb, zugängliche Menüführung | Offen |
| K3b Formulare | Textarea mit Längenhinweis, Zahlen-/Datums-/Zeiteingabe, Combobox/Autocomplete, Upload, FormSection, Formular-Fehlerzusammenfassung, Feldgruppen | Browser-Autofill, Hilfetexte und Error-IDs, Drag/Drop-Fallback | Offen |
| K4b Feedback/Overlay | Modal/Confirm, Toast/Statusregion, Spinner/Indeterminate, Statusblöcke, Busy/Read-only, Bulk-Aktion | Dialogfokus, Escape/Close, Async-Feedback | Offen |
| K5 Daten | Filter-Chips, Filter-Sidebar, Zeilenlisten, Tabellen-Sortierung/Pagination, Batchtoolbar, Zeitlinie, Diagramme und barrierefreie Datenalternative | Longtext, 320px und datengetriebene Status | Offen |
| K6 Bibliothek | MediaCard, Verfügbarkeit, Fälligkeitsstatus mit 7-Tage-Warnung, ScanInput, Exemplar-/Personenzeile, Cover-Varianten, Personenauswahl | Fachinformationen strikt vom Präsentations-API trennen | Offen |
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
