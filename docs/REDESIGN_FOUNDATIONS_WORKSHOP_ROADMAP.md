# BiblioCollect – Foundations-Workshop und Designlab-Plan

> **Status:** Gestaltungs- und Implementierungsplanung zur gemeinsamen Abnahme. Noch keine Änderungen an produktiven Komponenten oder Tokenwerten.  
> **Referenz:** [Foundations Hybrid v1](REDESIGN_FOUNDATIONS_HYBRID_V1.md), [Designsystem-Masterplan](REDESIGN_DESIGN_SYSTEM_MASTERPLAN.md), `resources/css/tokens.css`, `docs/BRANDING.md`.  
> **Fixiert:** VDBS-Farben und Schriftfamilien (Lato/Neuland/Source Serif 4).  
> **Beschlossen:** zwei kontrollierte Gestaltungsprofile – öffentlich großzügig/organischer, intern klar/kompakt.  
> **Noch offen:** Portal-Dichte, exakte Radien, typografischer Endabgleich und Inhalt der mobilen Navigation.

## 1. Nächste Designlab-Erweiterung – „Foundations Playground“

Zielroute ausschließlich in `local` und `testing`: `/design-lab/foundations`. Nicht auf Produktivsystemen bereitstellen.

Der Playground bekommt eine eigene Unter-Navigation und dieselben VDBS-Farben und Schriftfamilien wie die reale Anwendung. Hauptansicht: **nebeneinander vergleichbare Beispielmodule** mit identischen Daten, nicht unabhängige Kunst-Designs.

### A. Übersicht & Entscheidungen

- Ansicht der beiden Profile `comfortable` und `compact` nebeneinander.
- Eine unveränderliche Vorbemerkung, was fix ist (Farben/Fonts), was vorgeschlagen und was freigegeben ist.
- Vergleich „vorhandene Tokens“ ↔ „vorgeschlagene Tokens“ ↔ „freigegebene Tokens“; solange keine Abnahme erfolgt, kein Freigabezeichen.
- Klare Kennzeichnung rein demonstrativer Daten; keine produktiven Aktionen.

### B. Typografie – alle Schriftrollen live vergleichen

- Spalten Desktop/Smartphone für Display/H1/H2/H3/H4, Body, Label, Meta, KPI.
- Kurze und lange Textproben mit Umlauten, langen deutschen Substantiven und echten Beispielen aus dem Bibliothekskontext.
- Testbeispiele: „Die erstaunliche Geschichte von der verschwundenen Bibliothek“, lange Autor:innen-Namen, eine sehr lange ISBN-/Signaturzeile.
- Schaltbare Varianten `comfortable` / `compact`; Textgrößen und tatsächliche Tokenwerte unmittelbar sichtbar.
- Lato normal/bold/black, Neuland ausschließlich Akzent und Source Serif 4 ausschließlich Editorial nebeneinander testen.

### C. Abstände & Dichte

- Visualisierung der Tokenleiter (4/8/12/16/20/24/32/40/48/64/80/96), stets mit realen CSS-Abständen.
- Dieselbe Medienkarte, Form und Tabellenzeile in `comfortable` und `compact`.
- Kontrollpunkte: Padding, Gap, Section-Spacing, Textdichte, Tabellenhöhe, Mobilreflow.
- Varianten werden als **semantische Dichteprofile** verwendet, nicht durch zufällige globale `zoom`- oder `transform:scale`-Werte.

### D. Ecken, Rahmen, Schatten & Flächen

- Kandidaten-Raster für 0/4/8/12/20/28/999px, mit denselben Karten- und Formularbausteinen.
- Flächenvergleich: ohne Rahmen; 1px-Linie; erhöhte Karte; Brand-soft; Stage – unter Beachtung des Kontrasts.
- Schatten `none`, `soft`, `medium`, `overlay` mit Dark-Mode-Kontrolle.
- Regeln: Arbeitslisten eher offen/flach; öffentliche Bühne darf deutlicher schichten; kein automatischer „Rahmen + Schatten + Farbkasten“-Stack.
- VDBS-Grün wird nicht für unzureichend kontrastierenden kleinen Text auf Weiß verwendet.

### E. Größen & Interaktionen

- Primär-/Sekundärbutton, Suchfeld, Select, Icon-Button, Scannerfeld, Tabellenzeile mit Zielhöhen 44/48/56px.
- Varianten `default`, `hover`, `focus-visible`, `disabled`, `loading`, `error` (sofern sinnvoll).
- Manuelle Tastaturkontrolle; keine allein farbliche Zustandsvermittlung.
- Klick-/Touchziele 44px als Produktziel; normativer WCAG-2.2-AA-Mindestwert davon unterscheiden.

### F. Layout, Responsivität und Inhaltsbreiten

- Maximalbreiten `reading:720`, `form:800`, `standard:1200`, `wide:1440` px als Vorschläge.
- Derselbe Formular-, Medien- und Dashboardinhalt bei 320/375/768/1024/1280/1440px.
- Responsive Regeln anhand von Inhalten statt künstlicher Gerätetypen festlegen.
- Stepper bei zu wenig Platz vertikal; Bento von 4 zu 2 zu 1 Spalte, ohne logische DOM-Reihenfolge zu verändern.
- Katalog-Facetten bei enger Breite in ein klar bezeichnetes Filterpanel statt unlesbar engen Sidebar-Spalten.

### G. Muster/Patterns und Inhaltsbeispiele

- **Process Stepper**: Schrittnummer und Titel auf derselben Höhe; Linie ausschließlich **zwischen** den Inline-Schrittgruppen, Titel unterbricht die Linie. Status `done/current/upcoming/error`, optional Untertitel. Bei mobilen Breiten vertikal, Text rechts.
- **Feature Overlap**: öffentlich 55/45 Split und 48–80px Überlagerung, mobil gestapelt und maximal 24px versetzt.
- **Bento Grid**: 1–2 dominantere Kacheln, echte Hierarchie, keine erfundenen Trends.
- **Notice Panel**: Info/Erfolg/Warnung/Fehler, optisch ruhig, mit echter Aussage und Handlung nur wo sinnvoll.
- **MediaCover**: vier Markenvarianten, lange Titel, fehlende Autor:innen, echte Cover und generierte SVG-Cover getrennt zeigen.

## 2. Soll-Tokens und Profilzuordnung – Entscheidungsmatrix

| Gegenstand | Öffentlicher Bereich (Comfortable) | Betrieb / Verwaltung (Compact) | Portal (Vorschlag, offen) | Freigabe |
|---|---|---|---|---|
| Grundschrift | 16px | 16px | 16px | Vorschlag |
| Label | 16px | 16px | 16px | Vorschlag |
| Zusatz-/Tabellentext | 14px | 14px | 14px | Vorschlag |
| H1 Desktop | 44px | 32px | 36px | Vorschlag |
| H2 Desktop | 32px | 24px | 28px | Vorschlag |
| Buttonhöhe | 48px | 44px | 48px | Vorschlag |
| Formularfeld | 48px | 44px | 48px | Vorschlag |
| Kartenradius | 12–20px | 8px | 12px | Vorschlag |
| Karteninnenabstand | 24–32px | 16–20px | 20–24px | Vorschlag |
| Sektionsabstand Desktop | 80–96px | 32–48px | 48–64px | Vorschlag |
| Seitenbreite | i.d.R. 1200px, Bühne 100% | bis 1440px | 1200px | Vorschlag |
| Große Overlap-Bühnen | möglich | nur mit Fachgrund | sparsam | Vorschlag |
| Bento | Highlights | Aufgaben und KPIs | Kontostatus | Vorschlag |

Diese Matrix ist der Startpunkt, **kein finaler Beschluss über einzelne Pixelwerte**. Portal wird als ruhiger Mittelweg vorgeschlagen; Nachfrage beim Projektverantwortlichen erforderlich.

## 3. Repräsentative „Goldene Referenzseiten“

Die Foundation-Entscheidungen werden anhand von **sechs vollständigen Vergleichsszenen** getroffen:

1. **Öffentliche Startseite** – große Typografie, Hero, Suchfeld, Cover, Feature Overlap.
2. **Öffentlicher Katalog** – Facetten, lange Titel, Trefferkarten, Verfügbarkeitsstatus, Mobil-Filter.
3. **Mein Konto / Meine Ausleihen** – Fristen, persönlicher Status, Medienliste, Vormerkungen.
4. **Bibliotheksbetrieb / Scanner** – Scannerfokus, Personenauswahl, Rückmeldung und Fehlermeldung.
5. **Medienverwaltung / Formular** – viele Eingaben, Hilfen, Validierung, Speicheraktionen.
6. **Verwaltungsdashboard** – KPI-Bento, Aufgaben, Tabelle, Statusmeldungen.

Jede Szene in normalem/fehlendem/fehlerhaftem Zustand, bei Bedarf auch in `compact`, und zusätzlich in Dark Mode und auf schmalen Bildschirmen prüfen.

## 4. Token- und Komponentenarchitektur im Projekt

Die bestehenden `--vdbs-*`-Farben und `--font-family-*`-Variablen werden **nicht verändert**.

**Vorschlag für Umsetzung nach Freigabe:**
1. Neue semantische Aliase für Inhalte und Dichte additive in der Designlab-Scope definieren.
2. In der echten `x-app-shell` nicht mehrere parallele Stylesysteme aufbauen, sondern ein klares `surface`-/`density`-Konzept verwenden; Oberfläche legt Default fest, einzelne dichte Arbeitskomponenten dürfen begründet überschreiben.
3. Die 4px-Spacingtokens zentral verfügbar machen; nicht gleichzeitig überall individuelle Margins definieren.
4. Alte CSS-Ausnahmen anhand der neuen Tokens zurückbauen, inklusive `bc-button` mit bislang eigener Rundung `.3rem`.
5. Components (`button`, `form-field`, `alert`, `badge`, `table`) migrieren, anschließend `library`-Patterns (`process-stepper`, `feature-overlap`, `stat-tile`, `notice-panel`).
6. Pro Schritt nur eine überschaubare Anzahl produktiver Seiten anfassen. Visuelle Regression und Feature-Tests vor Merge.

**Keine** produktiven Geschäftsregeln, Rollenrechte, Cover-Caching-Prozesse oder Datenzugriffe im Rahmen einer reinen Foundations-Änderung umbauen.

## 5. Prüfstrategie und Freigabestufen

**Statusmodell:** `idea` → `draft` → `review` → `approved` → `implemented` → `verified`. Ein Wert ist nur dann `approved`, wenn die konkrete Variante besprochen wurde. Ein freigegebener Designwert ist nicht automatisch produktiv implementiert.

### Visuelle Freigabe
- Desktop- und Mobilansicht; lange deutsche Titel; Hell/Dunkel; Vergleich beider Dichteprofile.
- „Gleiches Problem, gleiche Gestaltung“: Jeder Token und jede Komponente nur einmal definiert.
- Öffentliche Bühne lebendig, Arbeitsbereich kompakt, ohne getrennte visuelle Markenwelten.

### Technische Freigabe
- Tastaturbedienung, semantische HTML-Auszeichnung, Zoom/Reflow, Tooltips/Dialogs, Textkontrast.
- `prefers-reduced-motion`, manuelles Theme, Browser-Autofill, Druck.
- Funktionstests der echten Fachseiten sowie `composer quality` und `npm run build` (nach Implementierung).
- Komponentenprüfungen im Designlab plus visuelle Browserabnahme bei tatsächlichen Screenshots.

## 6. Konkrete Arbeitsfolge der nächsten Designrunde

| Etappe | Aufgabe | Abnahmematerial |
|---|---|---|
| W1 | Foundation-Playground als lokale Route anlegen | Vergleichsübersicht mit beiden Dichteprofilen |
| W2 | Typografie und Textlängen prüfen | Skalen und sechs realistische Textproben |
| W3 | Abstände und Geometrie prüfen | 4px-Skala, Card-/Form-Demos, drei Radius-Richtungen |
| W4 | Größen, States und Layout prüfen | Buttons/Inputs/Tabellen, Desktop-/Mobilansicht |
| W5 | Stepper/Overlap/Bento/Notice refaktorisieren | Vier Standard-Patterns mit Varianten |
| W6 | Sechs Goldene Referenzseiten gegenprüfen | Gestaltungsabnahme und dokumentierte Ausnahmen |
| W7 | Tokens und Basiskomponenten integrieren | Technisch getesteter Pilot; keine Big-Bang-Migration |

Diese Abfolge beschreibt **Arbeitsblöcke, keine Kalenderwochen oder Liefertermine**.

## 7. Punkte, über die wir noch entscheiden müssen

**Frage 1 – persönliche Oberfläche:** Soll das Portal der Schülerinnen und Schüler vor allem `comfortable` bleiben oder wie interne Anwendungen bewusst `compact` sein? Empfehlung: **komfortabler Portalrahmen mit kompakten Medien-/Ausleihlisten**.

**Frage 2 – öffentliche Überlappungen:** Sollen größere Overlap-Bereiche ausdrücklich auch ohne echte Fotos (mit VDBS-Illustrationen oder Buchcovern) verwendet werden? Empfehlung: **ja**, weil Schulbibliotheken oft keine konsistenten oder rechtlich freigegebenen Fotos haben.

**Frage 3 – Verdichtung:** Soll die Verwaltung standardmäßig kompakt und bei Bedarf auf „komfortabel“ umschaltbar sein? Empfehlung: **zunächst nur automatische Profile nach Surface**, eine Nutzerpräferenz erst nach Bedarfstest.

**Frage 4 – Rundungen:** Freigabe der Hybridzuordnung (8px für Inputs/Buttons, 8px Arbeitspanels, 12–20px Public-Karten, max. 28px Bühne) oder gezielt kantigere Workbenches?

Fragen in dieser Reihenfolge klären; die gewählten Antworten dann im Foundations-Masterdokument versioniert dokumentieren.
