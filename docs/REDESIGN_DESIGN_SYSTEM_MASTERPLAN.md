# BiblioCollect – Masterplan des Designsystems

> **Status:** Planung / Soll-Katalog, noch keine Freigabe oder automatische Umsetzung aller Bausteine.  
> **Grundlage:** bestehendes Repository auf `feature/public-home-designsystem-redesign`, `docs/DESIGN_SYSTEM.md`, `docs/BRANDING.md`, Blade-Views und die vier Surfaces.  
> **Leitbild:** VDBS-Marke verbindlich; Oodi-artige Overlap-Editorials, reduzierte Navigation, Stepper für Prozesse, Bento dort, wo Kennzahlen gewichtet sind.  
> **Struktur:** Token → Component → Pattern → Template → Page.

## 1. Bereits vorhanden (beobachtet)

- Die kanonische Hierarchie Token → Component → Pattern → Template → Page steht bereits in docs/DESIGN_SYSTEM.md.
- resources/css/tokens.css enthält VDBS-Farben (Grün #2DC08E, Lila #58275A, Ink #0F172A), semantische Tokens, Lato/Neuland/Source Serif 4 und einen manuell gesteuerten Dunkelmodus.
- Der Branch enthält 23 Blade-Dateien unter resources/views/components/, darunter app-shell, Buttons, Input, Select, Tabelle, Badge, Alert, MediaCover, FeatureOverlap, ProcessStepper, StatTile, NoticePanel.
- Es gibt vier reale Oberflächen: Public, Portal, Pos (Betrieb), Administration. Auth/Identity, Fehlerseiten, Mail und Druck sind Querschnittsbereiche.
- resources/views/pages/ enthält rund 112 Seitendateien inkl. Teilansichten und Demo-Seiten. Nicht jede davon benötigt ein eigenes Template.
- Die Designvorschauen /design-review und /design-lab sind Entwürfe und ersetzen keine produktiven Fachseiten.

**Status-Codes im Plan:** P0 = unverzichtbare Grundlagen, P1 = Haupt-Workflows, P2 = Spezial-/Folgeelemente. Die Priorität ist eine Empfehlung für die Design-Reihenfolge, **keine Aussage**, dass das Element fehlt oder schon implementiert ist.

## 2. Vollständiger Soll-Katalog für Tokens, Komponenten und Patterns

### F01 · Fundament / Tokens (P0; 24 Bausteine)

1. Markenfarben und semantische Farben
2. Hell- und manuell geschalteter Dunkelmodus
3. Schriftfamilien und Schriftschnitte
4. Typografieskala mit responsiver Überschriftenhierarchie
5. Zeilenhöhe und Textbreiten
6. Abstands- und Layoutskala
7. Responsive Containerbreiten
8. Grid-Spalten und Gutter
9. Breakpoint-Strategie
10. Eckradien
11. Grenzlinien und Trennlinien
12. Höhenstaffelung/Schatten
13. Transparenzen und Überlagerungen
14. Animationsdauer und Easing
15. Reduzierte Bewegung
16. Touch-Zielgrößen
17. Tastaturfokus
18. Layer-/z-index-Skala
19. Icons und Strichstärken
20. Lade- und Leerlauf-Zustände
21. Kontrast- und Textregeln
22. Druck- und E-Mail-Tokens
23. Dichtevarianten komfortabel/kompakt
24. Foto- und Bildsprache

### C01 · Layout und globale Navigation (P0; 20 Bausteine)

1. AppShell je Surface
2. zweizeiliger Header
3. VDBS-Logo/Brand-Lockup
4. globaler und lokaler Footer
5. Hauptnavigation
6. Oberflächenumschalter
7. Seitenleiste/Untermenü
8. mobile Navigation
9. Breadcrumbs
10. Seitentitel mit Untertitel
11. Seitenaktionen rechts
12. Skip-Link
13. Theme-Umschalter
14. Nutzermenü/Avatar
15. aktive Navigation
16. Sticky-Toolbar
17. Kontextleiste/Unterkopfnavigation
18. zweispaltiger Seitenaufbau
19. Inhaltscontainer und Full-Bleed-Sektion
20. responsiver Split-View

### C02 · Buttons und Interaktionen (P0; 17 Bausteine)

1. Primary/Secondary/Tertiary Button
2. Textlink und Inline-Link
3. Icon-Button
4. Danger-/Destructive Button
5. Button-Gruppe
6. Split-Button
7. Dropdown-Aktionen
8. Overflow/Kebab-Menü
9. Floating Action nur bei echtem Bedarf
10. Copy-to-Clipboard
11. Zurück/Abbrechen/Fertig
12. Speichern/Entwurf übernehmen
13. Ladezustand
14. Disabled mit Begründung
15. Bestätigung vor destruktiver Aktion
16. Tastatur-Shortcut-Hinweis
17. Tooltip und kontextbezogene Aktion

### C03 · Formulare und Eingabe (P0; 30 Bausteine)

1. Textfeld
2. mehrzeiliges Textfeld
3. Passwortfeld/Anzeigen
4. Suchfeld
5. Nummernfeld
6. Barcode-/ISBN-Eingabe
7. Datumsfeld
8. Datumsbereich
9. Zeitfeld und Zeitspanne
10. Select
11. Autocomplete/Combobox
12. Radio-Gruppe
13. Checkbox
14. Switch
15. Chip-Auswahl
16. Mehrfachauswahl
17. Dateiupload/Dropzone
18. Cover-Upload/Vorschau
19. Inline-Edit
20. Formularabschnitt/Fieldset
21. Hilfetext
22. Pflichtfeldanzeige
23. Validierung direkt am Feld
24. Formular-Fehlerzusammenfassung
25. Formular-Aktionsleiste
26. Formular mit mehrspaltigem Raster
27. abhängige Felder
28. Zeichen-/Längenanzeige
29. Sicherheits-/Berechtigungshinweis
30. Änderung-unerledigt-Hinweis

### C04 · Status, Feedback und Hilfe (P0; 23 Bausteine)

1. Badge/Lozenge
2. NoticePanel info/success/warning/error
3. Inline-Fehler
4. globaler Hinweisbanner
5. Toast/Statusnachricht
6. System- und Verbindungsstatus
7. Fortschrittsbalken
8. Skeleton
9. Spinner
10. Loading/Bitte warten
11. Empty State
12. No-Results State
13. Error State
14. Forbidden State
15. Offline-/Netzwerkausfallhinweis
16. Erfolgsbestätigung
17. Warnung vor Datenverlust
18. Bestätigungsdialog
19. Hilfetext mit Beispiel
20. Tooltip
21. Popover
22. Akkordeon/FAQ
23. Schritt-Hilfe und Assistenztext

### C05 · Medien, Inhalte und Redaktion (P1; 24 Bausteine)

1. MediaCover mit echtem Bild oder SVG-Fallback
2. kurzer/mittlerer/langer Cover-Titel
3. Covergrößen mini/small/regular/hero
4. Autoren-/Beitragszeile
5. Medientyp-Piktogramm
6. Verfügbarkeitszeile
7. Standort/Signaturanzeige
8. Buch-/Titelkarte
9. Edition-/Exemplarkarte
10. FeatureOverlap normal/reverse
11. Text/Bild-Editorialsektion
12. Hero-Bühne
13. Kurations-/Empfehlungskarte
14. Neuerscheinungssektion
15. Themenkarte
16. Buchreihe/Serienkarte
17. Leselistenkarte
18. horizontale Medienliste
19. Bildergalerie
20. Bildcaption/Quelle
21. Textseiten-Typografie
22. Lesebreite für Artikel
23. Callout/Zitat
24. VDBS-Illustration und Bildrichtlinien

### C06 · Suche, Filter und Entdecken (P1; 24 Bausteine)

1. globale Katalogsuche
2. Autovervollständigung
3. erweiterte Suchmaske
4. Suchvorschläge
5. zuletzt/eigenständig gesuchte Begriffe (nur sofern Datenschutzkonzept)
6. Suchkriterien-Chips
7. Filterpanel
8. Filtergruppen
9. facettierte Filter mit Treffermengen
10. Filter-zurücksetzen
11. Sortierung
12. Ergebnisanzahl
13. Suche ohne Treffer
14. Suche mit einem Treffer
15. Trefferzeile
16. Trefferraster
17. Raster-/Listenumschalter
18. Paginierung
19. Ladeanzeige für Nachladen
20. Aktive-Filter-Zusammenfassung
21. Einzeltitel-Auswahl
22. Exemplar-/Verfügbarkeitsfilter
23. Themen- und Reihenexploration
24. Regal-/Signatur-Navigation

### C07 · Listen, Tabellen und Massendaten (P1; 22 Bausteine)

1. DataTable
2. kompakte Arbeitsliste
3. Tabellenspalten-/Zeilenköpfe
4. sortierbare Spalte
5. Tabellensuche
6. Filtertoolbar
7. Spaltenbreite/Responsivität
8. Tabellen-Seitenwechsel
9. Zeilenauswahl
10. Bulk-Aktionsleiste
11. Zeilenaktion
12. aufklappbare Detailzeile
13. Statuszelle
14. Datum und Frist
15. Trunkierung mit vollständiger Detailansicht
16. Null-/Leerzelle
17. zeilenbezogene Validierung
18. CSV-Export-Rückmeldung
19. Baum-/Hierarchieliste
20. Drag-and-drop nur mit Tastaturalternative
21. Audit-Protokollzeile
22. Vergleich vorher/nachher

### C08 · Kennzahlen und Datenvisualisierung (P1; 17 Bausteine)

1. StatTile klein/breit/hoch
2. BentoGrid
3. KPI-Zahl mit Einheit
4. KPI-Beschreibung
5. Trendangabe nur bei realer Vergleichsdatenquelle
6. Verteilung/Donut
7. Zeitreihendiagramm
8. Balkendiagramm
9. Tabellenalternative für Diagramme
10. Legende
11. Zeitraumwahl
12. Segmentierung/Filter
13. Warnschwellen/Schwellwertbeschreibung
14. Aufgabenpriorisierung
15. Systemzustandskarte
16. Datenherkunft/Stand-Label
17. Exportaktion

### C09 · Schrittprozesse und Aufgaben (P1; 19 Bausteine)

1. ProcessStepper horizontal/vertikal
2. Schrittstatus done/current/upcoming/error
3. Fortschritt bei unbekannter Schrittzahl
4. Wizard-Seitenkopf
5. Schritt-Formular
6. persistierte Zwischenschrittzusammenfassung (nur wenn Domäne vorhanden)
7. Zurück/Weiter
8. Prüfen-Vorschau-Bestätigen
9. Änderungsdiff
10. Importfeld-Mapping
11. Import-Fehlerzeilen
12. Stapelvorgang-Vorschau
13. Stapelvorgang-Ergebnis
14. Vorsicht-/Bestätigungsstufe
15. Zwischenzustand für Serverbearbeitung
16. Abbruch mit Rückkehr
17. Wiederaufnahme nur bei unterstütztem Workflow
18. Konfliktauflösung
19. Auditierbare Erfolgsmeldung

### C10 · Scanner, Umlauf und Schule (P1; 22 Bausteine)

1. Scannerfokus-Feld
2. Scan-Bereitschaft
3. erfolgreicher Scan
4. Doppel-Scan
5. unbekannter Barcode
6. Ausweis-/Personenauswahl
7. Ausleihkorb
8. Rückgabekorb
9. Exemplarstatus
10. Rückgabefrist
11. Sperrgrund-Hinweis
12. Vormerkungseintrag
13. Verlängerungsergebnis
14. Inventur-Fortschritt
15. Inventur-Differenz
16. Standortwechsel
17. Aussonderungsprüfung
18. Personen-/Klassenliste
19. Ausweis-Sperren/Ersetzen
20. Scan-Ergebnisliste
21. Arbeitsplatztaugliches Kompaktlayout
22. Scanner-Tastaturbedienung

### C11 · Identität, Konto, Datenschutz (P1; 18 Bausteine)

1. Anmeldung
2. Passwort vergessen
3. Passwort zurücksetzen
4. E-Mail bestätigen
5. Konto aktivieren/beanspruchen
6. Konto-Profilkopf
7. Personenkarte
8. Rolle/Berechtigung
9. Sperrstatus
10. Datenschutzhinweis
11. Meine Daten
12. Änderung von Kontoeinstellungen
13. sichere Offenlegung sensibler Angaben
14. personenbezogene Fehlertexte ohne Datenleck
15. Löschung/Anonymisierung bestätigen
16. Ausleihfristen und Warnstatus
17. Reservierungsaktion mit Begründung
18. Leihhistorienanzeige nur sofern vorgesehen

### C12 · Administration und Einstellungen (P2; 21 Bausteine)

1. Einstellungsgruppe
2. Einstellung mit Erklärung
3. Schul-/Schuljahr-Auswahl
4. Klassenverwaltung
5. Öffnungszeiten-Editor
6. Kalender für Schließtage
7. Regalstrukturbaum
8. Reihenfolge/Zuordnung
9. Rollenmatrix
10. Nutzereinladung
11. Freigabe-/Veröffentlichungsstatus
12. Protokollfilter
13. Status-Checkliste
14. System-/Job-Health
15. Job-Run-Feedback
16. Versions-/Updatekarte
17. Datensicherungskarte
18. Warnung bei irreversibler Änderung
19. Content-Block-Editor
20. Inhaltsseiten-Editor
21. Audit-Änderungsvorschau

### C13 · Druck, Export, Kommunikation (P2; 21 Bausteine)

1. Drucklayout-Container
2. Belegkopf
3. Barcode/QR-Code-Komponente
4. A4/Etikettenbogen
5. kleiner Kassenzettel/Ausleihbeleg
6. Ausweis-Vorderseite
7. Ausweis-Rückseite
8. Regal-/Bestandsetikett
9. Inventurbericht
10. Klassenleihliste
11. Buchwunschliste
12. CSV-Exporthinweis
13. PDF-/Print-Vorschau
14. Druck-Only und Screen-Only
15. Seitenumbruchsregeln
16. Druck mit Schwarzweißkontrast
17. E-Mail-Header/Footer
18. Transaktionsmail
19. Erinnerungsmail
20. Statusmail
21. Fallback ohne Bilder

### C14 · Qualität, Edge Cases und Bedienbarkeit (P0; 21 Bausteine)

1. Fokus-Indikator
2. Tastaturnavigation
3. Screenreader-Bezeichnung
4. 200% und 400% Zoom
5. 320px-Reflow
6. Lange deutsche Wörter
7. Sehr langer Buchtitel
8. Langer Personennamen
9. Leere bzw. fehlende Metadaten
10. Hoher Kontrast
11. Reduzierte Bewegung
12. Touchziele
13. Rollen-/Berechtigungsvarianten
14. Loading/Success/Warning/Error/Empty
15. langsame Netzwerkantwort
16. Nicht-Berechtigt-Ansicht
17. Mehrsprachigkeit nur bei späterer Produktanforderung
18. Print-Ansicht
19. JS-unabhängiger Grundzugang
20. Browser-Autofill
21. Fehlertoleranz bei Scannerbedienung

## 3. Die 22 tatsächlich wiederverwendbaren Seitentemplates (Layout-Baupläne)

Diese Ebene ist besonders wichtig: **Wir entwickeln nicht 67 voneinander unabhängige Layouts.** Wir entwickeln die folgenden **22 Grundtemplates**, die von den fachlichen Seiten wiederverwendet werden. Je nach Ergebnis der Designreviews lassen sich später Templates zusammenlegen.

| Code | Template | Grundstruktur | Beispiele |
|---|---|---|---|
| L01 | PublicLanding | Hero + Suchfunktion + kuratierte Module + Service | Public Startseite |
| L02 | EditorialStory | FeatureOverlap + Bilder/Quelle + Artikel + CTA | Themen und Bibliotheksinformationen |
| L03 | SearchResults | Suchtoolbar + Facetten + Treffer + Pagination | öffentlicher/interner Katalog |
| L04 | AdvancedSearch | mehrere Eingaben + Validierung + Suchkriterien | erweiterte Suche |
| L05 | RecordDetail | Titelbild + Primärinfo + Metadaten + Kontextaktionen | Mediendetail, Personendetail |
| L06 | CollectionBrowser | Sammlungsheader + Coverraster/Liste + Filter | Leselisten, Buchreihen, Merklisten |
| L07 | RecordEditor | Formularabschnitte + Hilfen + Aktionen | Medium, Exemplar, Nutzer, CMS |
| L08 | PersonalOverview | Fristen + Ausleihlisten + nächste Aktionen | persönliches Portal |
| L09 | OperationsDashboard | Bento-KPIs + Tasks + wichtige Zustände | Betrieb, Verwaltung, Statistik |
| L10 | ScanWorkbench | Scannerfokus + Person/Objekt + Arbeitskorb + Ergebnis | Ausleihe, Rückgabe, Einsortieren |
| L11 | DataList | Such-/Filtertoolbar + Tabelle + Zeilen- und Bulk-Aktionen | Personen, Vormerkungen, Audit |
| L12 | WorkflowWizard | ProcessStepper + Schrittinhalt + Review + Commit | Erfassung, Import, Schuljahreswechsel |
| L13 | DataReview | Diff + Fehlerliste + Auswahl + Bestätigung | Qualitätsprüfung, Inventurbericht |
| L14 | SettingsPanel | Einstellungsgruppen + Erläuterungen + Speichern | Regeln, Konto, Schule |
| L15 | HierarchyManager | Baum/strukturierte Liste + Zuordnung + Editor | Regale, Klassifikation, Standorte |
| L16 | CalendarEditor | Wochenschema + Ausnahmen + Kalender | Öffnungszeiten, Schließtage |
| L17 | ArticleHelp | Lesespalte + Überschriften + FAQ + Kontextlinks | Hilfeseiten, Datenschutz, Artikel |
| L18 | IdentityForm | sicheres Auth-Formular + Hilfe + Rückmeldung | Login, Aktivierung, Passwort |
| L19 | SystemStatus | Diagnosen + Jobs + Warnungen + sichere Aktionen | Systemzustand, Updates, Backup |
| L20 | PrintDocument | druckgerechte Header, Layouts, Umbrüche | Belege, Etiketten, Berichte, Ausweise |
| L21 | MailMessage | text- und bildrobuste Mail-Struktur | Beleg-/Erinnerungsmails |
| L22 | ErrorPage | Fehlercode + Erklärung + sichere Rückwege | 403,404,419,429,500,503 |

## 4. Fachliches Seiteninventar: 67 Anwendungen der Templates

Die folgenden **67 Einträge sind Seiten-/Workflow-Szenarien**, keine 67 neu zu implementierenden Komponenten. Die „Bauplan“-Spalte ordnet die reale Seite einem oder mehreren Grundtemplates zu.

| Bereich | ID | Seitenszenario | Bausteine und Zweck | Repository-Bezug | Bauplan |
|---|---|---|---|---|---|
| Öffentlich / Identität | T01 | Öffentliche Startseite | Hero, Suche, Kurzangebote, aktuelle Medien, Editorial, Öffnungszeiten | bestehende Startseite im Redesign | L01 |
| Öffentlich / Identität | T02 | Katalog-Ergebnisliste | Suchleiste, Facetten, Liste/Grid, Sortierung, Paginierung | öffentlich vorhanden | L03 |
| Öffentlich / Identität | T03 | Erweiterte Suche | Suchfelder, Filtergruppen, Kriterien-Zusammenfassung | öffentlich vorhanden | L04 |
| Öffentlich / Identität | T04 | Mediendetail | Cover, Metadaten, Verfügbarkeit, Exemplare, Vormerken | öffentlich vorhanden | L05 |
| Öffentlich / Identität | T05 | Buchreihenübersicht | Reihenkarten, A–Z, Katalogzugang | öffentlich vorhanden | L06 |
| Öffentlich / Identität | T06 | Buchreihendetail | Serienkopf, zugehörige Titel und Reihenfolge | öffentlich vorhanden | L06 |
| Öffentlich / Identität | T07 | Themen-/Regalansicht | Themenintro, Filter, zugeordnete Medien | öffentlich verlinkt | L03 |
| Öffentlich / Identität | T08 | Öffentliche geteilte Leseliste | Listenkopf, Medienübersicht, Freigabehinweis | öffentlich vorhanden | L06 |
| Öffentlich / Identität | T09 | Öffentliches Buchwunschformular | Medienwunsch, Validierung, Bestätigung | öffentlich vorhanden | L07 |
| Öffentlich / Identität | T10 | Informations-/Contentseite | Artikel, Bilder, Info, FAQ, Kontakt | öffentlich vorhanden | L17 |
| Öffentlich / Identität | T11 | Login / Kontoaktivierung | klarer Login-Formularrahmen, Hinweis und Fehler | Identity-Views vorhanden | L18 |
| Öffentlich / Identität | T12 | Passwort & Verifizierung | E-Mail-Bestätigung, Reset, Erfolg/Fehler | Identity-Views vorhanden | L18 |
| Öffentlich / Identität | T13 | Standard-Fehlerseite | 403, 404, 419, 429, 500, 503, Rückwege | Error-Views vorhanden | L22 |
| Portal | T14 | Persönliches Dashboard | Fristen, Ausleihen, Vormerkungen, Schnellaktionen | Portal vorhanden | L08 |
| Portal | T15 | Meine Ausleihen | Medien-/Fristenliste, Verlängerung, Zustände | Portal vorhanden | L11 |
| Portal | T16 | Meine Vormerkungen | Vormerkungen, Rang/Status soweit vorhanden, Storno | Portal vorhanden | L11 |
| Portal | T17 | Merkliste | Coverraster, entfernen, zu Leseliste | Portal vorhanden | L06 |
| Portal | T18 | Leselistenübersicht | private Listen, Erstellen, Teilen | Portal vorhanden | L06 |
| Portal | T19 | Leselistendetail/Editor | Titel hinzunehmen, sortieren, Freigabelink | Portal vorhanden | L06 |
| Portal | T20 | Meine Buchwünsche | Wunschstatus, Rücknahme, Verlauf | Portal vorhanden | L11 |
| Portal | T21 | Meine Daten / Einstellungen | Profil-/Datenschutz-/Benachrichtigungsoptionen | Portal vorhanden | L14 |
| Betrieb | T22 | Betriebsdashboard | Aktuelle Aufgaben, Systemstatus, Schnellzugriffe, KPIs | POS vorhanden | L09 |
| Betrieb | T23 | Ausleihe – Person wählen | Scanner, Identifikation, Sperrgründe | POS vorhanden | L10 |
| Betrieb | T24 | Ausleihe – Scan / Warenkorb | Scanfeld, Medium, Konto, Aktionen | POS vorhanden | L10 |
| Betrieb | T25 | Ausleihe – Bestätigung / Beleg | Zusammenfassung, Bestätigung, Ausgabe | POS vorhanden | L20 |
| Betrieb | T26 | Rückgabe / Verlängerung | Scanner, Fristen, Rückmeldungen | POS vorhanden | L10 |
| Betrieb | T27 | Vormerkungen / Überfällige | Datentabellen, Status, Aktionen | POS vorhanden | L11 |
| Betrieb | T28 | Interner Katalog | dichte Trefferliste, Arbeitsaktionen, Filter | POS vorhanden | L03 |
| Betrieb | T29 | Titel / Edition / Exemplar bearbeiten | Stammdaten, Beziehungen, Standort, Status | POS vorhanden | L05+L07 |
| Betrieb | T30 | Medienerfassung – Identifizieren | Barcode/ISBN, externe Abfrage und Fehler | POS vorhanden | L12 |
| Betrieb | T31 | Medienerfassung – Treffer wählen | Kandidatenvergleich und Dublettenprüfung | POS vorhanden | L12 |
| Betrieb | T32 | Medienerfassung – Angaben/Exemplar | mehrstufige Formulare und Standorte | POS vorhanden | L12 |
| Betrieb | T33 | Medienerfassung – Prüfen & Speichern | Diff, Zusammenfassung, Bestätigung | POS vorhanden | L12 |
| Betrieb | T34 | Katalogimport – Datei/Mapping | Upload, Feldzuordnung, Validierungsreport | POS vorhanden | L12 |
| Betrieb | T35 | Katalogimport – Vorschau/Übernahme | Korrekturen, Prüffehler, Bestätigung | POS vorhanden | L13 |
| Betrieb | T36 | Datenqualität – Liste/Detail | Metadatenprüfung, Korrekturvorschläge, Diff | POS vorhanden | L13 |
| Betrieb | T37 | Personenübersicht / Personendetail | Ausleihkonto, Kontakt, Sperren, Aktionen | POS vorhanden | L05+L11 |
| Betrieb | T38 | Personenformular / Personenimport | Stammdaten, Klassen, Upload, Zuordnung | POS vorhanden | L07 |
| Betrieb | T39 | Inventur – Start & Scannen | Fortschritt, Scan-Rückmeldung, Differenzen | POS vorhanden | L10 |
| Betrieb | T40 | Inventur – Bericht & Korrektur | Statistik, Abweichungen, Export, Freigabe | POS vorhanden | L13 |
| Betrieb | T41 | Regalordnung & Standortwechsel | Scanner und Standort-Zuordnung | POS vorhanden | L10 |
| Betrieb | T42 | Aussonderung – Scan/Prüfung/Ergebnis | Stapel, Gründe, Rücknahme | POS vorhanden | L12+L13 |
| Betrieb | T43 | Bestandsetiketten / Ausweisdruck | Vorlagenwahl, Charge, Vorschau, Druck | POS vorhanden | L20 |
| Betrieb | T44 | Buchwunsch- und Reihenverwaltung | Listen, Status, Aktionen | POS vorhanden | L11 |
| Betrieb | T45 | Statistiken & Klassenberichte | KPI, Diagramme, Berichtstabelle, Export | POS vorhanden | L09 |
| Betrieb | T46 | Betriebshilfe / Prozesse | Anleitungsartikel, FAQ, Handlungsübersicht | POS vorhanden | L17 |
| Administration | T47 | Verwaltungsdashboard | Bento, wichtige Aufgaben, aktuelle Systeme | Admin vorhanden | L09 |
| Administration | T48 | Benutzer & Rollen | Liste, Edit, Einladen, Sperren und Berechtigungen | Admin vorhanden | L11 |
| Administration | T49 | Schule, Schuljahre & Klassen | Stammdaten, Auswahl und Klassenlisten | Admin vorhanden | L11+L14 |
| Administration | T50 | Schuljahreswechsel-Assistent | Folgen, Prüfung, Vorschau, Commit | Admin vorhanden | L12 |
| Administration | T51 | Ausleihregeln | Einstellungen, Validierung, Reset | Admin vorhanden | L14 |
| Administration | T52 | Öffnungszeiten & Schließtage | Wochenplan, Ausnahmen, Bearbeitung | Admin vorhanden | L16 |
| Administration | T53 | Regale / Standorte / Themen | Hierarchie, Zuordnung, Bearbeitung | Admin vorhanden | L15 |
| Administration | T54 | Klassifikationsimport | Upload, Vorschau, Freigabe | Admin vorhanden | L12 |
| Administration | T55 | Mediennummerierung / Regal-Labels | Prüfung, Anpassung, Druck | Admin vorhanden | L12+L20 |
| Administration | T56 | CMS: Seiten / Textbausteine | Contenteditierung, Veröffentlichungsstatus | Admin vorhanden | L07+L17 |
| Administration | T57 | Systemzustand / Hintergrundaufgaben | Job-Status, Diagnose, Neustart, Logs | Admin vorhanden | L19 |
| Administration | T58 | Update / Backup | Release, Pakete, Sicherung, Sicherheitshinweise | Admin vorhanden | L12+L19 |
| Administration | T59 | Audit-Protokoll | Zeitlinie, Filter, Export, Änderungsdetails | Admin vorhanden | L11 |
| Administration | T60 | Mailvorschau und Testversand | Templates, Vorschau, Testaktion | Admin vorhanden | L21 |
| Druck, E-Mail & System | T61 | Ausleihbeleg / Rückgabeauszug | Kompakter Beleg, Barcodes, Termine | Druckansichten vorhanden | L20 |
| Druck, E-Mail & System | T62 | Bibliotheksausweis | Vorder-/Rückseite, Motive, Druck | Druckansichten vorhanden | L20 |
| Druck, E-Mail & System | T63 | Barcode- und Regal-Etiketten | Papierformate, Positionierung, Druckkalibrierung | Druckansichten vorhanden | L20 |
| Druck, E-Mail & System | T64 | Listen- und Berichtsdruck | Klassen, Inventur, Wünsche | Druckansichten vorhanden | L20 |
| Druck, E-Mail & System | T65 | E-Mail-Transaktionsbeleg | Header, Inhaltsblock, handlungsrelevante Daten | Mail-Views vorhanden | L21 |
| Druck, E-Mail & System | T66 | Erinnerung / Statusmail | Status, Hilfe, Optik ohne Bilder | Mail-Views vorhanden | L21 |
| Druck, E-Mail & System | T67 | Designreview & Pattern-Galerie | alle Varianten und Zustände, responsive Gegenprobe | Review-Seiten vorhanden | L17 |

## 5. Designvarianten, Zustände und Responsivität

Jedes wiederverwendbare Element wird nicht nur als Idealzustand gezeigt. Der Designatlas soll je nach Relevanz dokumentieren:

- Varianten: default, kompakt, breit, auffällig, neutral, danger; nur wenn fachlich erforderlich.
- Zustände: default, hover, focus-visible, pressed, checked/selected, disabled, loading, success, warning, error, empty, readonly.
- Inhalte: kurz, lang, extrem lang, fehlend, große Anzahl, lange zusammengesetzte deutsche Wörter, lokalisiertes Datum.
- Viewports: 320/375/390, 768, 1024, 1280, 1440 Pixel; zusätzlich 200% und 400% Zoom.
- Darstellung: Hell- und manuell aktivierter Dunkelmodus, Druck, E-Mail, reduzierte Bewegung.
- Eingabe: Maus, Tastatur, Touch, Scanner; Screenreader-Labels, live regions nur bei echtem Feedback.
- Berechtigungen: anonym, angemeldet, Portal, Betrieb, Verwaltung; fehlende Rechte auch serverseitig erzwungen.
- Tabellen/Listen: leere, wenige, sehr viele Ergebnisse, lange Einträge, Filter ohne Treffer, Ladezustand.
- MediaCover: Originalbild, fehlendes Bild, SVG-Cache vorhanden/neu, sehr langer Titel, keine Autor:innen, Ersatzgrafik ohne Storage-Schreibrecht.

## 6. Abhängigkeiten – was zuerst gestaltet sein muss

| Erst freigeben | Darauf aufbauende Templates |
|---|---|
| Tokens, Shell, Navigation, Textstil | alle Seiten |
| FormFields, Alerts, Dialoge | Anmeldung, Nutzerverwaltung, Regeln, Content und alle Formulare |
| MediaCover, Search, Filter, Trefferzeile | Startseite, Katalog, Titeldetail, Merkliste, Leseliste |
| Table, Toolbar, BulkActions | Betrieb, Personen, Inventur, Verwaltung |
| ProcessStepper, ReviewDiff, Confirm | Erfassung, Import, Inventur, Schuljahreswechsel |
| ScanInput, CheckoutBasket, PatronSummary | Ausleihe, Rückgabe, Etiketten, Regal |
| StatTile, BentoGrid, Charts, StatusPanel | Portal, Betrieb, Verwaltung, Statistik |
| PrintTokens, ReceiptLayout, LabelLayout | Belege, Ausweise, Etiketten, Berichte |

## 7. Etappenplan für Design, Pilot und Rollout

| Phase | Inhalt | Ergebnis |
|---|---|---|
| 0 · Bestandsaufnahme | Alle vorhandenen Views, CSS-Klassen, UI-Komponenten, Formulare und Designabweichungen erfassen; Dubletten und Berechtigungsgrenzen markieren. | Inventar + Migrationsmatrix: Seite → Template → Patterns → Komponenten |
| 1 · Foundations | Tokens, visuelle Sprache, Farbkontraste, Typografie, Abstände, Raster, Breakpoints, Fokus, Dark Mode und Dichte abstimmen. | verbindlicher Foundations-Katalog |
| 2 · Pflichtkomponenten | Buttons, Eingaben, Search, FormField, Status, Notice, Dialog, Tabs, Navigation, Tabellen-Grundgerüst und Ladezustände fertig definieren. | kontrollierte Komponentenbibliothek mit Zuständen |
| 3 · Bibliothekskomponenten | Cover, Medienkarten, Verfügbarkeit, Leser-/Ausleihstatus, Facetten, Filter, Stepper, Barcodefeld, Personenauswahl, Bento, Overlap. | BiblioCollect-spezifische Pattern-Bibliothek |
| 4 · Templates & echte Daten | Jeden Template-Typ mindestens einmal mit realistischen langen Texten und echten Zuständen gestalten; Demo-Fachlogik strikt getrennt halten. | abgenommene Tablet-/Mobil-/Desktop-Templates |
| 5 · Pilot auf allen vier Surfaces | Pro Surface zunächst 1 repräsentativen, echten Flow migrieren: Public Katalog, Portal Meine Ausleihen, POS Ausleihe, Admin Regeln. | funktionale End-to-End-Piloten und Rückmeldungen |
| 6 · Breiter Rollout | Verwandte Seiten in kleinen PRs migrieren, doppelte CSS-Regeln entfernen, Print/Mail/Fehlerseiten angleichen. | Migrationsmatrix je Route, vollständige Abnahme |
| 7 · Pflege & Governance | Visuelle Regression, Accessibility-Checks, Komponentenversionierung, dokumentierte Ausnahmen und Änderungsprozess einführen. | lebendes Designsystem als Single Source of Truth |

## 8. Qualitäts-/Freigabekriterien

- Jede Komponente besitzt Zweck, API/Props, Varianten, Größe, Inhaltsgrenzen, mobile Darstellung, Dark Mode, Fehler- und Disabled-Verhalten.
- Zu jeder relevanten interaktiven Komponente gibt es Fokus-, Hover-, Keyboard-, Screenreader- und Zoom-Prüfung.
- Komponenten übernehmen nur Darstellung; Rollenrechte, CSRF, Datenvalidierung und Geschäftslogik bleiben in bestehenden Laravel-Controllern/Policies/Actions.
- Ein Benutzer kann einen mehrstufigen Vorgang ohne Maus durchlaufen; Scanner-Layouts behalten den Fokus und zeigen eindeutig Text + Status.
- Für jedes Template existiert eine echte leere, ladende, erfolgreiche und fehlerhafte Variante, sofern im Arbeitsablauf sinnvoll.
- Keine erfundenen Live-Kennzahlen, Trenddaten oder Bestandstitel in produktiven Ansichten; Demo-Inhalte sind sichtbar gekennzeichnet.
- Öffentliche redaktionelle Seiten dürfen großzügige Overlap-Bereiche zeigen; Arbeitsplätze bevorzugen Dichte und Geschwindigkeit statt Dekoration.
- Alle Standard-Patterns und Templates werden im Designatlas geprüft, einschließlich 320px/375px/768px/1280px/1440px, 200%/400%-Zoom und manuellem Dunkelmodus.
- CSS-Regeln erhalten definierte Zuständigkeit; nicht beliebig neue Farben/Radien/Schlagschatten in einzelnen Seiten einführen.
- Barrierefreie Alternativen und Print-/E-Mail-Kompatibilität sind Voraussetzung für die jeweilige Freigabe.
- Die Einführung erfolgt pro Bereich in Review-PRs mit CI, Fachtests und ggf. Browser-/Visual-Regression; kein Big-Bang-Austausch.

## 9. Verbindliche Designentscheidung für die aktuelle Runde

Wir gestalten **zunächst** einen ausführlichen Komponentenatlas und **pro Template-Familie eine ideale Musterseite**. Das ist kein sofortiger Ersatz aller produktiven Views. Bereits sichtbare Lösungen aus Designstudio 04 (`feature-overlap`, `process-stepper`, `stat-tile`, `notice-panel`) sind Kandidaten für Standardisierung und müssen vor dem flächendeckenden Einsatz auf Accessibility, Konsistenz und reale Fachzustände geprüft werden.

Für jede Arbeitseinheit wird dokumentiert: **Zweck → Varianten → Zustände → Responsivität → Barrierefreiheit → Blade-API → reale Einsatzorte → Tests → Freigabestand.**

Weitere Funktionen wie echte Raumreservierungen, Zahlungsabwicklung oder ein Wunschlisten-Workflow über den heutigen Stand hinaus werden **nicht** aus dem Designplan abgeleitet; sie wären eigene Produktanforderungen und sind hier nicht als bestehend angenommen.

