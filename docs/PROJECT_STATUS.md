# Projektstatus — BiblioCollect

Stand: v0.37.0 (Einrichtung für den Testeinsatz: `docs/GO_LIVE.md`, offene Punkte: `docs/OFFENE_PUNKTE.md`). Katalog mit Erfassung, Qualitätsprüfung und Covern; Ausleihe mit Verlängerung, Vormerkung und Abholung; Portal, Erinnerungen, Protokoll, Schuljahreswechsel, Import und Anonymisierung. Die Abschnitte unten sind nach Themen geordnet, neuere Bausteine stehen oben in den Unterabschnitten „v0.5.x/v0.6.x“.

## Abgeschlossen

- T0 Foundation mit Laravel 13, Modul-/Surface-Struktur, CI, Pint, PHPStan und Pest
- T1 mit Permission-/Role-Registry, Navigation, vier Surfaces, VDBS-App-Shell und `Europe/Berlin`
- T2 Identity / Patrons / School:
  - getrennte Onlinekonten (`User`) und Ausleihkonten (`Patron`)
  - E-Mail-Verifikation und einmalige Patron-Verknüpfungscodes
  - persistierte kombinierbare Rollen und Need-to-know-Patronzugriff
  - Patron-Stammdaten, Klassenbezug, Sperren/Entsperren und dauerhafter Austritt
  - Schuljahr-/Klassenverwaltung und Readiness-Prüfung
  - `SchoolCalendarService` als Grundlage für Öffnungstage

## Nachgelagerte Verwaltungsworkflows

Diese Punkte sind bewusst nicht Teil des T2-Abschluss-Gates und können später ergänzt werden:

- vollständiger Massenworkflow für Schuljahreswechsel mit Vorschau, Mapping und Konfliktbehandlung
- produktiver Schulimport
- Öffnungszeiten-/Schließtage-Verwaltungsmaske

## T3 Catalog

v0.4.0 hat das reine Catalog-Domänenfundament eingeführt:

- `Title` beschreibt den titelbezogenen bibliografischen Kern.
- `Edition` beschreibt eine konkrete Ausgabe und trägt die technische Vorbereitung für Altersfreigaben.
- `Copy` beschreibt das physische Exemplar; sichtbare Barcodes sind eindeutig, aber niemals Primärschlüssel.
- interne IDs bleiben ULIDs.
- Lieferanten-, Preis- und Budgetlogik gehört ausdrücklich nicht in Catalog.

v0.4.1 ergänzt die bibliografische Arbeitsbasis:

- Verantwortliche werden als eigene `Contributor`-Datensätze geführt und über geordnete `TitleContribution`-Einträge mit flexiblen Rollen an einen Titel gebunden.
- `Edition` erhält offene Felder für Medientyp und Sprachcode, ohne Importformate vorwegzunehmen.
- `SearchCatalogTitlesQuery` sucht titelbasiert über Titelangaben, Verantwortliche und ausgewählte Editionsdaten.
- Suche und spätere Reservierungen bleiben titelbezogen; exemplarbezogene Vorgänge bleiben bei `Copy`.

v0.4.2 macht den ersten Teil der internen Katalogpflege bedienbar:

- `catalog.manage` ist ein eigenes Fachrecht im Bibliotheksbetrieb.
- Schüler-AG Erweitert, Mitarbeiter:innen und Verwaltung dürfen Titel und Editionen anlegen und bearbeiten.
- Schüler-AG Basis und technische Administration erhalten dieses Recht nicht.
- Titel- und Editionsänderungen laufen über explizite Actions und validierte DTOs.
- Verantwortliche, Exemplare und Importe bleiben bewusst separate Folgeschritte.

v0.4.3 macht die strukturierten Verantwortlichkeiten bedienbar:

- Verantwortliche können direkt an einem Titel angelegt, bearbeitet und vom Titel gelöst werden.
- `role_key` bleibt bewusst offen und importfreundlich; die Reihenfolge wird über `position` gepflegt.
- Namensänderungen bearbeiten den zugrunde liegenden `Contributor` und wirken deshalb bei gemeinsam genutzten Datensätzen auf alle verknüpften Titel.
- Eine identische Kombination aus Verantwortlichem und Rolle wird am selben Titel verhindert.
- Beim Lösen einer Verknüpfung wird ein Contributor nur dann automatisch gelöscht, wenn er anschließend nirgends mehr verwendet wird.

v0.4.4 ergänzt die physische Exemplarpflege:

- Exemplare werden innerhalb einer Ausgabe angelegt und bearbeitet.
- Barcode, Regalstandort und `CopyStatus` werden über eigene Actions und ein validiertes DTO gepflegt.
- Barcode-Eindeutigkeit wird in der Catalog-Domäne abgesichert und als feldbezogener Fehler in der POS-Oberfläche zurückgegeben.
- Ein Exemplar bleibt an seine Ausgabe gebunden; ein versehentliches Verschieben über manipulierte Routen wird verhindert.
- Es gibt bewusst keine Hard-Delete-Funktion. Dauerhaft entfernte Bestände werden als `withdrawn` markiert.
- Die vorhandenen Zustände `active`, `damaged`, `lost` und `withdrawn` sind in Oberfläche, Seed und Regressionstests abgedeckt.
- Der Development-Seed wurde parallel erweitert und sein zuvor in SQLite sichtbarer Idempotenzfehler bei Schließtagen behoben.

v0.4.5 öffnet den Katalog für die anonyme Recherche:

- `/katalog` ist ohne Onlinekonto erreichbar und bietet titelbezogene Suche, Browsing und Pagination.
- Die bestehende `SearchCatalogTitlesQuery` bleibt für bisherige Aufrufer kompatibel und erhält zusätzlich einen paginierten Kriterienpfad.
- Öffentliche Filter werden aus den offenen Editionswerten für Medientyp und Sprache abgeleitet; es wird kein starres Vokabular in die Domäne gezwungen.
- Der Filter „Nur Titel mit aktiven Exemplaren“ arbeitet bewusst nur mit `CopyStatus::Active`.
- Seit v0.5.2 zeigt die öffentliche Oberfläche zusätzlich die Verfügbarkeit aus offenen Ausleihen (siehe T4 Circulation); der Katalogfilter bleibt „aktiv“, nicht „verfügbar“.
- `/katalog/titel/{titleId}` zeigt Titel, Verantwortliche, Ausgaben, Altersangaben, aktive Regalstandorte und aggregierte Bestandszustände.
- Copy-Barcodes und interne ULIDs werden in der öffentlichen Ausgabe nicht angezeigt.
- `CatalogHoldingService` bündelt die wiederverwendbare Bestandsaggregation für Titel und Ausgaben.
- `PublicCatalogPresenter` übersetzt offene technische Werte ausschließlich für die Public-Surface; unbekannte Werte bleiben darstellbar.
- Der Development-Seed enthält zusätzlich einen englischsprachigen Titel mit aktivem Bestand und einen Titel ohne physische Exemplare.
- HTTP-, Query-, Filter-, Pagination-, Datenschutz- und Seed-Regressionen sind automatisiert abgedeckt.

v0.4.6 ergänzt die Import-Infrastruktur:

- `catalog.import` ist ein eigenes Fachrecht und liegt ausschließlich bei Mitarbeiter:innen und Verwaltung; Schüler-AG Erweitert behält `catalog.manage`, erhält aber kein Massenimportrecht. Technische Administration erhält weder fachlichen Katalogzugriff noch Importrecht.
- `CatalogImportSource` trennt die Eingangsquelle von der Importpipeline. CSV ist der erste Adapter; MARC21 ist noch nicht implementiert und kann später dieselbe Preview-/Commit-Pipeline speisen.
- `CatalogImportBatch` und `CatalogImportRow` speichern Upload-Metadaten, Header, Mapping, Rohzeilen, normalisierte Werte, Pläne, Warnungen, Konflikte und Status persistent mit ULIDs.
- Upload und Preview schreiben keine bibliografischen oder physischen Katalogdatensätze. Erst die ausdrücklich bestätigte Übernahme führt Writes aus.
- Die Preview erkennt fehlende Pflichtwerte, ungültige Alters-/Jahreswerte, doppelte Barcodes innerhalb der Datei, bereits katalogisierte Barcodes, widersprüchliche ISBN-Gruppen sowie nicht eindeutige Matches.
- ISBN wird normalisiert. Bekannte Sprach-, Medientyp-, Rollen- und CopyStatus-Werte werden sinnvoll normalisiert; offene Sprach-/Medientyp-/Rollenwerte bleiben erhalten, soweit sie technisch gültig sind.
- Ein eindeutiger, strukturell plausibler ISBN-10/ISBN-13-Match darf eine vorhandene Edition wiederverwenden, überschreibt aber keine bestehenden Stammdaten; erhaltene nicht standardisierte ISBN-Freitextwerte werden nicht als Merge-Schlüssel verwendet. Widerspricht der importierte Haupttitel dem ISBN-Ziel, blockiert die Zeile.
- Mehrere konfliktfreie CSV-Zeilen mit derselben ISBN teilen sich einen Editionsplan und erzeugen bei der Übernahme mehrere Exemplare.
- Der Commit berechnet die Preview unmittelbar vor dem Schreiben erneut und läuft vollständig in einer Datenbanktransaktion. Ein später Fehler rollt alle Katalog-Writes dieses Batches zurück.
- Der POS-Workflow zeigt Mapping, persistente zeilenweise Vorschau, Zählwerte, Konflikte, Warnungen und nach erfolgreicher Übernahme einen Importbericht.
- Der Development-Seed enthält eine reproduzierbare CSV-Fixture und einen konfliktfreien Preview-Batch, ohne die Demo-Katalogzählwerte durch einen automatischen Commit zu verändern.
- Die Details sind in `docs/T3_CATALOG_IMPORT.md` dokumentiert.

## Eingeschobener Legacy-Katalogmigrationsblock

Auf Basis von T4 v0.5.0 erweitert ein eigener Zwischenblock den Catalog für den realen Altbestand:

- `Edition` nähert sich dem Informationsumfang des früheren DNB-basierten Katalogs mit Verantwortlichkeitsangabe, Reihe, Erscheinungsort, Auflagenangabe, weiteren Identifikatoren, ISSN/DOI, Originalsprache, Umfang, Inhaltsangabe, Schlagwörtern, Zielgruppe, Altersangaben und Quellenprovenienz an.
- `Contributor` erhält eine optionale GND-ID.
- `CatalogTopic` bildet die alte hierarchische `mediaTopicList` mit ULIDs und Legacy-Referenzen ab.
- `CatalogSignature` übernimmt die Signaturstruktur und ihre geordnete Topic-Zuordnung; Exemplare können strukturiert auf eine Signatur verweisen.
- `Copy` behält zusätzlich umfangreiche Legacy-/Bestandsmetadaten wie Schul-ID, Zugangsstatus, Kaufdatum/-preis, Aufnahme-/Coverreferenz, Zustand, interne Notizen, Aussonderungsangaben und historische Ausleihstatistik.
- `inventory_number` wird beim Legacy-Import zum sichtbaren Barcode; die alte `media_id` bleibt reine Legacy-Referenz.
- `catalog:legacy:analyze` liest phpMyAdmin-JSON ohne Katalog-Writes und meldet Warnungen/Konflikte.
- `catalog:legacy:import` wiederholt dieselbe Analyse und schreibt nur konfliktfreie Daten als Gesamttransaktion.
- alte `is_available`-, `loan_counter`-, `last_loan_date`- und `in_transition`-Werte werden nie als aktuelle Circulation-Wahrheit interpretiert.
- ungültige Legacy-Daten wie `0000`, `0000-00-00` oder nicht standardisierte ISBN-Freitexte werden schonend erhalten/ignoriert und als Warnungen nachvollziehbar gemacht.
- die öffentliche Titelansicht kann die erweiterten bibliografischen Angaben und die aus Signaturen abgeleiteten Themen anzeigen, ohne interne Exemplaridentitäten oder interne Legacy-Felder offenzulegen.
- die Details stehen in `docs/LEGACY_CATALOG_IMPORT.md`.

## Eingeschobener Katalogrecherche-/Coverblock

Der öffentliche Katalog erhält eine stärker coverorientierte Trefferliste und eine bewusst einfache erweiterte Suche für den Schulbetrieb. Die interne Katalogpflege erhält parallel eine wesentlich detailliertere bibliografische Recherche, bleibt aber unverändert hinter `catalog.manage`.

Cover werden nicht live aus externen APIs geladen. `Edition` erhält einen lokalen Cover-Cache-Zustand, `CatalogCoverProvider` bildet die spätere externe Quelle ab und Queue-Jobs übernehmen das Herunterladen im Hintergrund. Öffentliche Views rendern ausschließlich lokal gespeicherte Cover oder den gebündelten Platzhalter.

Der Block umfasst im Einzelnen:

- `/katalog/erweiterte-suche` als reduzierte, schulgerechte Advanced Search ohne Login; aktive erweiterte Kriterien bleiben in der Trefferliste sichtbar und nachbearbeitbar.
- eine deutlich ausführlichere interne Suchmaske unter `/betrieb/katalog`, die unverändert hinter `catalog.manage` bleibt und kein neues Recht einführt.
- `catalog_editions` trägt `cover_path`, `cover_source`, `cover_source_reference`, `cover_status`, `cover_checked_at` und `cover_fetched_at`; `cover_source_reference` wird öffentlich nicht ausgegeben.
- `catalog:covers:queue` stellt Editionen mit ISBN oder Quell-ID als `RefreshEditionCoverJob` in die Queue; der Job akzeptiert nur JPEG/PNG/WebP innerhalb des Größenlimits.
- Gebunden ist eine Provider-Kette aus Open Library (ohne Key) und Google Books (nur mit Key); Cover werden ausschließlich im Hintergrund geladen und lokal gespeichert.
- `CatalogCoverDemoSeeder` markiert eine Demo-Ausgabe als `pending` und hält den Development-Seed offline und reproduzierbar.

Ergänzend erhielten öffentliche und interne Trefferliste eine gemeinsame Seitennavigation:

- `resources/views/components/catalog/pagination-controls.blade.php` bündelt Trefferbereich, Seitengrößenwahl und direkte Seiteneingabe für beide Surfaces.
- Wählbar sind 10, 20, 50 oder 100 Treffer pro Seite; die Standardgröße liegt bei 20, das Query-Limit bei 100.
- Nicht unterstützte Seitengrößen werden als Validierungsfehler abgewiesen, nicht still korrigiert.
- Die interne Trefferliste ist damit nicht mehr auf 50 Titel ohne Navigation begrenzt; aktive Filter bleiben beim Seitenwechsel erhalten.

Die Details stehen in `docs/PUBLIC_CATALOG_SEARCH.md`.

## Erfassungsprozess für neue Medien

Neue Medien werden unter `/betrieb/katalog/erfassen` in einem geführten Prozess mit fünf Schritten aufgenommen (Identifizieren, Treffer prüfen, Titel & Ausgabe, Exemplar, Prüfen & speichern); das Vorbild ist der siebenstufige Ablauf des Altsystems, schlanker zusammengefasst.

- Metadaten kommen aus der frei zugänglichen DNB-SRU-Schnittstelle (ISBN-Abfrage und Titel-/Autorsuche) und werden nur als Vorschlag vorbefüllt; bestätigt wird in Schritt 3.
- Bis zum ausdrücklichen Speichern wird nichts in den Katalog geschrieben; gespeichert wird als eine Transaktion (Titel, Verantwortliche, Ausgabe, Exemplar).
- Zu einer bereits vorhandenen ISBN wird nur ein weiteres Exemplar ergänzt, statt Titel doppelt anzulegen.
- Verantwortliche werden über die GND-ID bzw. einen eindeutigen Namenstreffer wiederverwendet.
- Eine ausgefallene DNB blockiert die Erfassung nicht; manuelle Erfassung steht immer offen.
- Nach dem Speichern wird das Cover im Hintergrund über Open Library (ohne Key) und optional Google Books (mit Key) geladen.
- Das Recht bleibt `catalog.manage`.

Die Details stehen in `docs/CATALOG_INTAKE.md`.

## Katalogqualität: Metadaten prüfen und Vorschläge bestätigen

Unter `/betrieb/katalog/qualitaet` listet eine Prüfliste alle Ausgaben mit unvollständigen oder fehlerhaften Metadaten (verlorene Umlaute, Steuerzeichen, fehlende Verantwortliche, Jahr, Verlag, Medientyp, ISBN). Zu jedem Fall gibt es einen Vorschlag, den eine Person Feld für Feld bestätigt.

- Der Scan (`catalog:quality:scan` bzw. „Bestand neu prüfen“) schreibt nur in die Prüftabelle `catalog_metadata_reviews`, nie in den Katalog, und ist wiederholbar.
- Vorschläge kommen aus der DNB (per gespeicherter DNB-ID, sonst per ISBN) oder als lokale Bereinigung ohne Quelle. Ein beschädigter Wert wird nur korrigiert, wenn der Quellwert nachweislich sein Ursprung ist.
- Gefüllte, unbeschädigte Werte werden nie überschrieben; Abweichungen sind nur sichtbar und nicht vorausgewählt. Passt der Datensatz nicht zur Ausgabe, wird nichts vorausgewählt.
- Die Übernahme läuft in einer Transaktion, nur für angehakte Felder und nur bei unverändertem Datenstand; jede Änderung wird protokolliert (wer, wann, alt, neu).
- „Kein Handlungsbedarf“ gilt für den geprüften Stand und öffnet sich bei späteren Änderungen wieder.
- Recht: `catalog.manage`.

Die Details stehen in `docs/CATALOG_QUALITY_REVIEW.md`.

## T4 Circulation

v0.5.0 führt den ersten Ausleih- und Rückgabe-Workflow ein:

- `circulation.manage` ist das zentrale Fachrecht für laufende Ausleihe und Rückgabe. Schüler-AG Basis/Erweitert, Mitarbeiter:innen und Verwaltung erhalten es; technische Administration, Schüler:innen und Lehrkräfte nicht.
- `Loan` persistiert Patron, physisches Exemplar, Ausleihzeitpunkt, Fälligkeit, Rückgabezeitpunkt und die handelnden Benutzer:innen mit ULID-Identität.
- `CirculationRuleEvaluator` bündelt Patronstatus, Sperre, CopyStatus, bereits offene Ausleihe und Altersfreigabe in einer zentralen fachlichen Entscheidung.
- Die Altersprüfung verwendet das vollständige Patron-Geburtsdatum gegen `Edition::minimum_age` und die Geschäftszeit `Europe/Berlin`.
- Die Standardleihfrist beträgt zunächst 14 Kalendertage. Fällt das Ziel auf einen geschlossenen Bibliothekstag, verschiebt `LoanDueDateService` die Fälligkeit mit `SchoolCalendarService` auf den nächsten Öffnungstag.
- Checkout und Return laufen vollständig in Datenbanktransaktionen und sperren die beteiligten Zeilen pessimistisch mit `lockForUpdate()`.
- Rückgaben überschreiben keinen `CopyStatus`; beschädigt/verloren/ausgesondert bleiben bewusste Catalog-Zustände.
- Der Patron-Arbeitsbereich zeigt nur offene Ausleihen und bietet Barcode-Checkout sowie Rückgabe. Bereits zurückgegebene Titel werden dort bewusst nicht als allgemeine Lesehistorie dargestellt.
- `CirculationDemoSeeder` liefert einen offenen und einen zurückgegebenen Demo-Loan reproduzierbar und idempotent.
- Mahnungen, Gebühren und Einsicht in die Ausleihhistorie bleiben Folgeschritte.
- Die Details stehen in `docs/T4_CIRCULATION.md`.

### Keine doppelten Vormerkungen (v0.24.0)

Ein Ausleihkonto kann einen Titel nur einmal offen (wartend oder bereitgelegt) vorgemerkt haben, auch über verschiedene Ausgaben desselben Titels. Die Anwendung prüft das schon beim Vormerken; zusätzlich sichert ein eindeutiger Schlüssel in der Datenbank (`open_key`) es auch bei gleichzeitigen Anfragen. Vorhandene Doppelte werden bei der Migration bereinigt: Die älteste bleibt, spätere gelten als storniert. Nach Erfüllung, Stornierung oder Ablauf ist eine neue Vormerkung wieder möglich.

### Bibliotheksnummern (v0.26.0)

Neue Ausleihkonten bekommen eine zufällige sechsstellige Bibliotheksnummer ohne Kennung für Schüler:innen, Lehrkräfte usw. (nie mit 0 am Anfang, nicht fortlaufend, einmalig). Das gilt für den Import (wenn die Datei keine Nummer enthält) und für „Ausleihkonto anlegen“ (Feld leer lassen). Eine von Hand eingetragene Nummer bleibt möglich. Vorhandene Nummern im Schema `S-10001` ersetzt der Befehl `php artisan patrons:renumber` (v0.26.1; `--dry-run` zeigt vorab die Zahl, `--yes` ohne Rückfrage). Er lässt gute und anonymisierte Nummern unberührt, läuft ganz oder gar nicht und schreibt die Zuordnung alt → neu (mit Namen, vertraulich) nach `storage/app/private/bibliotheksnummern-alt-neu-….csv`. Ausweise bleiben unverändert, nur gedruckte Zettel oder Listen mit den alten Nummern passen danach nicht mehr.

### Neue Konten mit Ausweis, Klassendaten-Import (v0.27.0, v0.27.1)

- **Einzelne Person** (Seite „Ausleihkonto anlegen“): Pflichtfeld „Ausweis (jetzt scannen)“. Konto und Ausweis entstehen in einer Transaktion; ist der Ausweis unbekannt, gesperrt oder vergeben, entsteht nichts.
- **Klassendaten-Import** (`/betrieb/ausleihkonten/import`): Die Vorlage hat nur `vorname`, `nachname`, `geburtsdatum` und optional `email`. Die **Klasse wird beim Hochladen gewählt**, alle Zeilen werden Schüler:innen dieser Klasse; Bibliotheksnummern sind zufällig, **Ausweise** gibt es erst bei der klassenweisen Ausgabe. Lehrkräfte und Mitarbeiter:innen werden einzeln mit Ausweis angelegt. Details: `docs/PATRON_IMPORT.md`.

### Erfassung: Themenbereich und Zugänglichkeit (v0.28.0)

- **Themenbereich** statt „Lokale Klassifikation“ (Schritt Titel & Ausgabe): Auswahl aus den Themenbereichen (Baum, Unterbereiche eingerückt), ohne Regalbretter und Signaturen. Gespeichert wird der Name im bisherigen Feld; Altwerte bleiben sichtbar und wählbar. Die Bezeichnung gilt auch in Prüfschritt, Katalogpflege und öffentlichem Katalog.
- **Zugänglichkeit** am Exemplar statt „Themenbereich / Signatur“: *Frei zugänglich*, *Nur Nutzung in der Bibliothek*, *Nur auf Nachfrage (verschlossen)* (Feld `access_status`, „frei“ ist der Wert des Altsystems). *Nur Nutzung in der Bibliothek* sperrt die Ausleihe; die Zugänglichkeit steht in der Exemplartabelle des öffentlichen Katalogs. Die Signatur wird nicht mehr von Hand gewählt, sie ergibt sich beim Einsortieren aus dem Regalbrett (sie bleibt beim Bearbeiten erhalten).
- **Gleich große Felder** in den Erfassungsformularen: gleiche Höhe, unten auf einer Linie, auch mit Hinweistext.

### Zeitplan-Aufgaben von Hand ausführen (v0.29.0)

Auf `/verwaltung/systemzustand` (Recht `system.view`) listet der Abschnitt „Zeitplan-Aufgaben" alle Aufgaben aus `routes/console.php` mit Erklärung und nächstem Lauf. Jede lässt sich mit „Einmal ausführen" (nach Rückfrage) sofort starten, ohne den Zeitplan zu ändern; läuft sie gerade schon, wird sie nicht doppelt gestartet. „Cron-Lauf jetzt auslösen" macht dasselbe wie der Cronjob (fällige Aufgaben und rund 10 Sekunden Warteschlange). Ergebnis und Dauer erscheinen oben, im Protokoll stehen `system.job.run` und `system.cron.run` mit dem auslösenden Konto. Die Anfragen sind je Minute begrenzt.

### Hauptnavigation ohne Scrollbalken (v0.29.1)

Die Hauptnavigation scrollt nicht mehr. Passen nicht alle Punkte in die Zeile, bleiben so viele wie möglich sichtbar (der aktuelle Punkt immer), die übrigen stehen in einem Menü mit drei Punkten („⋯", Beschriftung „Weitere Menüpunkte"). Das Menü schließt sich mit Escape oder Klick daneben. Ohne JavaScript bricht die Leiste stattdessen in mehrere Zeilen um. Geprüft in Edge bei 1600, 1000, 700 und 420 px Breite.

### Etiketten für Regalbretter (v0.58.0, Issue 10)

Unter Verwaltung → Regale und Regalbretter → **Etiketten für Regalbretter drucken** (`/verwaltung/regalbretter/etiketten`, Recht `shelves.manage`, also auch Mitarbeiter:innen) druckst du Etiketten im Format **105 × 26 mm, weißer Hintergrund, 2 × 11 = 22 je A4-Bogen**: der Standort in großer Schrift („I. A 1 a“), die Beschriftung des Bretts, Regal, Bereich und Bereichsgruppe mit ihren Namen, oben rechts Logo und „BiblioCollect“ und rechts ein **Code-128-Strichcode** mit dem Standort-Code, den das Einsortieren scannt (Schreibweise, Punkte und Leerzeichen sind dort egal). Wählbar: alle Regalbretter, eine Bereichsgruppe, ein Bereich, ein Regal, die ohne Regal oder einzelne Bretter (Vorrang); 1 bis 4 Etiketten je Brett, erster freier Platz auf dem Bogen, auf Wunsch die Themenbereiche klein dazu und auch ausgeschaltete Bretter. Die Druckseite hat den Feinabgleich (rechts, unten) und gilt für Druck in tatsächlicher Größe. Standort-Codes mit Umlauten oder zu langem Text lassen sich nicht als Strichcode darstellen: Dort steht der Hinweis „Kein Strichcode“. Der Druck steht im Protokoll (`catalog.labels.shelves_printed`).

### Kamera-Scan beim Einsortieren (v0.63.0)

Felder mit `data-camera-scan="absenden"` bekommen am Handy einen Button „Kamera“ (`resources/js/app.js`, nur bei https und vorhandener Kamera). Er öffnet einen Dialog mit Videovorschau (Rückkamera), liest Code 128, Code 39, EAN und QR-Codes (`resources/js/scanner.js`: zuerst der Barcode-Leser des Browsers, sonst ZXing `@zxing/browser`, reines JavaScript ohne WebAssembly, daher mit der strengen CSP vereinbar; die Bibliothek wird erst beim Klick nachgeladen), trägt den Wert ins Feld ein und schickt das Formular ab. Angeschlossen sind im Einsortieren das Buch-Feld und das Regalbrett-Etikett-Feld. Fehlerfälle (keine Erlaubnis, keine Kamera, Kamera belegt) zeigen eine Meldung, die Texteingabe bleibt nutzbar. `Permissions-Policy` erlaubt jetzt `camera=(self)`; Mikrofon, Standort und Zahlung bleiben gesperrt. Das zentrale Scanfeld im Arbeitsbereich (Ausleihe, Rückgabe) ist vorbereitet: Das Attribut genügt. **Nicht automatisiert testbar:** bitte am Handy (Android Chrome, iPhone Safari) und am Rechner mit Webcam prüfen.

### Link pro Thema (v0.62.0)

Neue öffentliche Route `/thema/{key}` (`public.topic`, `TopicLinkController`): Der Pfadteil ist der Themenname ohne Sonderzeichen (`CatalogTopic::publicSlug()`, z. B. `R%C3%A4tsel-Knobeln`); die Auflösung vergleicht nur Buchstaben und Ziffern in Kleinschreibung. Sie leitet auf `/katalog?thema=<Name>`. Neues Suchkriterium `theme` (`CatalogSearchCriteria`, Parameter `thema`): Treffer sind Titel mit einer Ausgabe, deren `local_classification` das Thema oder ein Unterthema nennt, oder mit einem Exemplar auf einem Regalbrett des Themas oder seiner Unterthemen (`SearchCatalogTitlesQuery::applyThemeFilter`). Der Katalog zeigt oben ein Banner mit Themenname, Beschreibung und den Regalbrettern (verlinkt auf `/regal/…`). Beim Druck der Regalbrett-Etiketten wählt man „QR-Code zeigt auf“: *Regalbrett* (Standard) oder *Thema des Bretts* (das erste Thema nach Position, ohne Thema bleibt es beim Regalbrett). Das Einsortieren erkennt Themen-Links nicht als Brettcode und weist darauf hin.

### Ausweisdruck: beidseitig oder einseitig, Verteilung im Druckmenü (v0.61.0, v0.61.1)

Die Druckseite einer Charge ist neu gestaltet (drei Schritte). Die **Druckart** ist wählbar: *beidseitig* (je Bogen eine Vorder- und eine Rückseite hintereinander, Duplex mit Wenden an der langen Kante) oder *einseitig* (der Export enthält erst alle Vorderseiten, dann alle Rückseiten, Bogen von Hand wenden; die Rückseiten sind gespiegelt angeordnet). Die Prozentfelder entfallen: Im Druckmenü wählt man je Motiv *normal* (Gewicht 1), *mehr von diesem Motiv* (Gewicht 2) oder *auslassen* (kommt nicht vor). Die Wahl gilt **nur für diesen Druck** und wird nicht gespeichert (`PatronCardController::shares`); sie wirkt nur bei Ausweisen ohne Motiv, und mindestens ein Motiv muss vorkommen. Ausweise, die schon ein Motiv haben (früherer Druck), behalten es; das Druckmenü weist darauf hin und bietet „Alle Ausweise dieser Charge neu verteilen“ (Parameter `neu`) an, zum Beispiel nach einem Probedruck. Dauerhaft ausgeschaltet werden Motive weiterhin unter „Motive verwalten“. Die in v0.61.0 kurz vorhandene Spalte `patron_card_motifs.distribution` entfernt die Migration `2026_10_10_110000` wieder.

### Ausweismotive als Paar (v0.60.0, Issue 11)

Ein Motiv besteht aus Vorder- und Rückseitenbild und gehört zusammen (Tabelle `patron_card_motifs`, ersetzt `patron_card_designs`; die Migration paart die mitgelieferten Motive über den Namen, unvollständige Uploads bleiben ausgeschaltet). Es wird beim ersten Druck am Ausweis gespeichert (`patron_cards.motif_id`); Vorderseite, Rückseite und Nachdrucke zeigen dasselbe Motiv, die Rückseite an der gespiegelten Platzposition. Upload und Verwaltung arbeiten mit Paaren. Vor dem Einspielen auf dem Server: bereits gedruckte Ausweise ohne `motif_id` bekommen beim nächsten Druck ein Motiv zugeteilt.

### QR-Code als Link zum Regalbrett (v0.59.0)

Der QR-Code auf dem Regalbrett-Etikett enthält jetzt `/regal/I-A-1-a` (Adresse der Installation). Die öffentliche Route `public.shelf` (`ShelfLinkController`) leitet auf `/katalog?regalbrett=I. A 1 a`; der Katalog filtert dann auf Titel mit einem Exemplar auf diesem Brett (neues Suchkriterium `shelf`, nur Exemplare mit genau diesem `shelf_location`) und zeigt oben Brett, Beschriftung und Standort. Das Einsortieren liest den Code auch aus dem Link (`regal` + beliebige Trennzeichen + Code), damit ein Etikett für beides genügt. Wichtig: Gedruckt werden muss von der echten Adresse, sonst steht `localhost` im QR-Code. Strichcode bleibt der reine Code.

### Regalbrett-Etiketten mit Thema im Vordergrund (v0.58.1)

Rückmeldung zu Issue 10: Für Nutzer:innen zählt das Thema, nicht die Nummer. Das Etikett zeigt jetzt die Beschriftung des Bretts groß (sonst die Themenbereiche), den Standort („I. A 1 a“) klein in einem Rahmen unten rechts und darüber einen Code. Der Code ist auf der Druckseite einstellbar: Strichcode (Code 128), QR-Code (neu, `bacon/bacon-qr-code`, reines PHP, `App\Foundation\Support\QrSvg`) oder keiner. Der Tresen-Scanner liest beide. Issue 10 bleibt offen bis zum echten Druck- und Scantest.

### Update-Pakete mit Rückwärtsstrichen (v0.57.3)

Ein mit dem Windows-PowerShell (`Compress-Archive`) gebautes Paket speichert Pfade mit Rückwärtsstrichen („app\Http\“). Die Update-Seite lehnte so ein Paket als „unzulässigen Dateinamen“ ab. Jetzt behandelt sie Rückwärtsstriche wie Schrägstriche (Prüfung und Entpacken Datei für Datei, damit auf Linux echte Ordner entstehen; ausbrechende Pfade wie `..\x` bleiben verboten), und `scripts/build-release.ps1` erzeugt die ZIP mit .NET (`ZipFile::CreateFromDirectory`) mit Schrägstrichen.

### Vorlagen für GitHub-Issues (v0.57.2)

Unter `.github/ISSUE_TEMPLATE` gibt es vier Formulare (Issue-Forms): **Fehler melden** (Label `bug`), **Neues Feature oder Änderungswunsch** (`enhancement`), **Frage oder Entscheidung nötig** (`question`) und **Problem im Betrieb** (`betrieb`: Einrichtung, Hochladen, Update, Mail, Cron, Datenbank, Cover, Wartungsmodus). Leere Issues sind ausgeschaltet; `config.yml` verweist auf die Anleitungen (`GO_LIVE`, `WEBSPACE_UPLOAD`, `LOKALE_EINRICHTUNG`). Da das Repository öffentlich ist, warnt jede Vorlage davor, Passwörter, Tokens, `.env`-Inhalte oder echte Personendaten einzutragen (Pflicht-Kästchen). Außerdem läuft der Test „seeds shelves from the free text locations“ nur noch auf SQLite, weil er die Tabelle löscht und neu anlegt (auf MariaDB blockiert der Fremdschlüssel der Regalbrett-Themen).

### Protokoll: Suche nach Personen, mehr Filter, CSV-Export (v0.57.0)

Das Protokoll (`/verwaltung/protokoll`) filtert nach Bereich, **Ereignis** (genaue Aktion), **Person** (Name oder Bibliotheksnummer; gefunden werden Ereignisse zum Konto selbst und solche, die sich im Kontext auf die Person beziehen, etwa Ausleihen und Vormerkungen), **Konto, das das Ereignis ausgelöst hat**, **Zeitraum** (von, bis) und Text. Die Tabelle zeigt eine Spalte „Person“ (Name und Nummer, aus den Konten aufgelöst; gespeichert werden weiter nur Kennungen). „Diese Auswahl als CSV exportieren“ liefert bis zu 20.000 Einträge mit Formelschutz, der Export steht selbst im Protokoll (`audit.exported`). Zugriff nur mit dem Recht „Protokoll einsehen“ (Verwaltung).

### Systemzustand: Installation und Cover-Fortschritt (v0.56.0)

Neuer Abschnitt **Installation** auf dem Systemzustand: installierte Version, PHP (mindestens 8.4), Umgebung (Warnung bei Debug im Echtbetrieb), Adresse (https), Datenbank, **offene Migrationen**, Schreibrechte auf `storage`, `storage/logs`, `bootstrap/cache` und `public/covers`, Mailversand, Warteschlange, bereitliegende Update-Pakete und Wartungsmodus, je mit Hinweis und Ergebnis (In Ordnung, Hinweis, Fehler). Der Abschnitt **Cover der Bücher** ist ausgebaut: Fortschrittsbalken mit Prozent, Zahlen (mit Cover, Suche steht aus, erfolglos gesucht, Fehler, ohne ISBN), Prognose in Nächten bei der eingestellten Menge pro Nacht, Cover-Aufgaben in der Warteschlange und fehlgeschlagene, Stand der Quellen (Open Library, Google-Books-Schlüssel), die Knöpfe zum Nachladen und die acht zuletzt geholten Cover als Vorschau.

### Konten-Import: mehrere Klassen und Aktualisieren (v0.55.0)

Der Klassenimport kennt jetzt die freiwillige Spalte **klasse**: Wählst du beim Hochladen keine Klasse („Die Klasse steht in der Datei“), ordnet er jede Zeile ihrer Klasse im aktiven Schuljahr zu (Schreibweise egal, „6 B“ findet „6b“); eine Datei mit mehreren Klassen geht damit auf einmal. Unbekannte oder fehlende Klassen sind Fehler in der Vorschau. Mit **„Vorhandene Personen aktualisieren“** ändert der Import bei bereits angelegten Personen (gleicher Name und gleiches Geburtsdatum) die E-Mail-Adresse (nie auf leer) und die Klasse; Name, Geburtsdatum und Bibliotheksnummer bleiben, Ausgeschiedene und Archivierte werden nicht angefasst. Die Vorschau zeigt jede Änderung („E-Mail: alt → neu“, „Klasse: 5a → 6b“), die Bestätigung nennt die Zahl der Anlegungen und Aktualisierungen. Die Excel- und CSV-Vorlage haben die Spalte klasse.

### Mails an mich schicken, klarere Einrichtungsseite (v0.54.0)

In der **E-Mail-Vorschau** gibt es ein Adressfeld (mit der eigenen Adresse vorbelegt) und die Knöpfe „Diese Mail schicken“ und „Alle 11 Mails schicken“: Die Mails kommen mit Betreff „[Vorschau] …“ und erfundenen Beispielangaben an, so siehst du sie in Gmail oder Outlook. Steht der Mailversand auf „log“ oder „array“, sagt die Seite, dass nichts zugestellt wird. Die **Einrichtungsseite `/_setup`** nennt jetzt den Grund: fehlendes oder falsches Token (mit Hinweis auf Leerzeichen und Anführungszeichen und auf die erst später übernommene `.env`), alle Eingabefehler auf einmal (E-Mail ungültig, Passwort mit Zeichenzahl), bei „Zugang wiederherstellen“ die vorhandenen Verwaltungskonten und was geändert wurde („Konto wieder aktiviert“, „Rolle Verwaltung vergeben“); bei vorhandenem Verwaltungskonto steht dessen Adresse. Ein abgelaufenes Formular (CSRF) stört dort nicht mehr.

### Klassenlisten als PDF mit Briefpapier (v0.53.0)

Auf der Seite Klassenlisten öffnet „Als PDF speichern (Briefpapier)“ eine Druckfassung (`/betrieb/klassenlisten/pdf`) mit denselben Filtern: je Klasse eine Seite (bei vielen Zeilen mehrere, Tabellenkopf wiederholt sich) auf dem Briefpapier, wahlweise **Hochformat oder Querformat**, Farbe, Schwarz-Weiß oder ohne Briefpapier. Gespeichert wird über den Druckdialog („Als PDF speichern“, Ränder „Keine“).

### Einsortieren: Vorschlag nach Schlagwörtern und Stapel nach Thema (v0.52.0)

Beim Einsortieren nennt die Seite zusätzlich zum Thema-Vorschlag die Regalbretter, die **nach Schlagwörtern und weiteren Angaben zum Buch** passen (`CatalogShelfSuggester`): Schlagwörter, Titel, Reihe, Thema, Zielgruppe und Inhaltsangabe werden mit Beschriftung, Themenbereichen (samt Hauptbereich und Beschreibung) und Altersangaben des Bretts („Kl. 2–3“, „ab 10“) verglichen, mit den Treffwörtern als Begründung. Vorgewählt wird jetzt: Brett der anderen Exemplare derselben Ausgabe, sonst Brett zum Thema, sonst der beste Vorschlag nach Schlagwörtern, sonst das zuletzt benutzte. Der **Stapel** zeigt die Bücher je Thema mit Zahl; „Nach Thema einsortieren“ nimmt ein Buch nach dem anderen aus einem Thema (oder ohne Thema) dran, das Formular merkt sich das Thema.

### Regalbrett-Suche und Füllstand (v0.51.0)

Auf **Regale und Regalbretter** gibt es ein Suchfeld: Standort, Beschriftung, Thema sowie Name von Regal, Bereich und Bereichsgruppe (mehrere Wörter müssen alle vorkommen), dazu der Filter „nur Regalbretter ohne Thema“. Die Ansicht zeigt dann nur Treffer und klappt die passenden Ebenen auf. Ein Regalbrett kann eine **Kapazität** haben (freiwillig, „Platz für wie viele Bücher?“): Die Zeile zeigt „12 von 40“ mit Balken, „voll“ oder „frei“; die Regalzeile fasst „n Bücher von m Plätzen“ zusammen.

### Update-Seite mit Wartungsmodus und nächtlichem Einspielen (v0.50.0)

Neue Seite **Verwaltung → Update** (`/verwaltung/update`, Recht `system.update` für Verwaltung und technische Administration): Ein Paket (ZIP aus `build-release.ps1`, enthält jetzt eine Datei `VERSION`) wird hochgeladen oder per FTP in `storage/app/updates` gelegt, geprüft (nötige Dateien, keine unzulässigen Pfade, Versionsvergleich) und mit „Jetzt einspielen“ eingespielt: Datenbanksicherung, Wartungsmodus, Entpacken, Kopieren über die Anwendung (nie `.env`, `storage`, Cover, Ausweis-Motive, `bootstrap/cache`; `public/build` wird vorher geleert), danach der **Abschluss in einer neuen Anfrage mit dem neuen Code** (`/_update/abschluss/<Schlüssel>`, im Wartungsmodus erreichbar): Migrationen, Zwischenspeicher leeren, Wartungsmodus beenden. Der Cron (`/_cron`, ebenfalls im Wartungsmodus erreichbar) schließt wartende Updates ab, bevor er etwas anderes tut. **Nachts automatisch:** Mit dem Schalter spielt `system:update-nightly` (03:15 Uhr) ein bereitliegendes, neueres Paket ein; der Abschluss folgt beim nächsten Cron-Aufruf. Der letzte Lauf steht auf der Seite; bei Fehlern geht eine Betriebsmeldung an `ALERT_EMAIL`. Rückfall: Sicherung in phpMyAdmin einspielen, bei hängendem Wartungsmodus `storage/framework/down` per FTP löschen. Außerdem: Testlauf mit 1 GB Speichergrenze (`phpunit.xml`), weil die Suite die 128 MB der Standardeinstellung überschritten hatte.

### Nur noch ein Etikett: die Inventarnummer (v0.49.0)

Die Exemplar-Etiketten mit Standort und Kurztitel sind entfernt (Controller, Seiten, Verweise). Es gibt nur noch das **Etikett mit Strichcode und Inventarnummer** (Logo, „BiblioCollect“, Strichcode, Nummer), gedruckt als Etiketten auf Vorrat unter Katalogpflege → Etiketten drucken (`/betrieb/etiketten/vorrat`). Nach dem Umstellen alter Inventarnummern druckt „Neue Etiketten drucken“ genau die neuen Nummern auf denselben Bögen (`POST /betrieb/etiketten/nummern`). Der Standort steht nicht mehr auf dem Etikett, er wird beim Einsortieren vermerkt.

### Fette Hervorhebungen in den E-Mails (v0.48.1)

In allen Mails ist das Wichtigste fett: Titel, Fälligkeits- und Abholdaten, „7 Tage“ bei Überfälligkeit, die Gültigkeit des Passwort-Links („60 Minuten“), der neue Stand eines Buchwunsches (angenommen, bestellt, erfüllt), Hinweise wie „musst du nichts tun“ und die Handlung („gib das Medium in der Bibliothek zurück“). Im Beleg stehen die Titel fett. Fettschrift ist im Thema `vdbs.css` extra kräftig eingestellt.

### E-Mail-Vorschau (v0.48.0)

Unter **Verwaltung → E-Mail-Vorschau** (`/verwaltung/mail-vorschau`, Recht `system.view`, verlinkt im Systemzustand und in der Prozessübersicht) lassen sich alle E-Mails der Anwendung mit erfundenen Beispielangaben ansehen: Passwort festlegen, E-Mail bestätigen, die drei Erinnerungen, Beleg, vier Buchwunsch-Stände und die Betriebsmeldung, wahlweise in Computer- oder Handybreite. Es wird nichts verschickt und nichts gespeichert.

### E-Mails im VDBS-Auftritt (v0.47.0)

Alle Mails (Passwort festlegen, E-Mail bestätigen, Erinnerungen, Beleg, Buchwunsch-Stand, Betriebsmeldungen) nutzen ein gemeinsames Design: oben ein lila Rand, das VDBS-Logo (PNG `public/brand/vdbs/mail-logo.png`, weil viele Mailprogramme kein SVG zeigen), „BiblioCollect“ in Lila mit grünem Strich, die Karte mit grünem Streifen, der Knopf in VDBS-Grün mit dunkler Schrift, Hinweisfelder mit grüner Kante und ein lila Fuß mit Verweis auf Impressum, Datenschutz und Barrierefreiheit. Schrift ist Lato, sonst Arial. Die Vorlagen liegen unter `resources/views/vendor/mail` (Layout, Kopf, Fuß, Thema `vdbs.css`, eingestellt in `config/mail.php`); die eigenen Mails (`resources/views/mail/*`) sind Markdown-Mails im selben Rahmen. Test: `MailDesignTest`.

### Aufklapp-Design für Regale und Themenbereiche (v0.46.0)

Die Seiten **Regale und Regalbretter** und **Themenbereiche** sind jetzt Aufklapp-Listen: Bereichsgruppe, Bereich und Regal sowie jeder Hauptbereich sind je eine Zeile mit Farbpunkt, Name und Zahlen (Anzahl der Regale und Regalbretter, der Unterbereiche) und klappen auf. Eine einzelne Bereichsgruppe und ihre Bereiche stehen offen, Regale und Hauptbereiche sind zu. „Alles aufklappen“ und „Alles zuklappen“ schalten alle Zeilen um (ohne JavaScript bleiben die Zeilen einzeln bedienbar). Regalbretter stehen kompakt in einer Zeile mit Standort, Beschriftung, Exemplarzahl und Themen als Chips; Unterbereiche eingerückt mit „↳“. Bearbeiten- und Hinzufügen-Formulare klappen weiter an der jeweiligen Stelle auf.

### Standortstruktur und klarere Verwaltungsseiten (v0.45.0)

Ein Regalbrett liegt jetzt immer in einem **Regal**, das Regal in einem **Bereich**, der Bereich in einer **Bereichsgruppe** (Standort „I. A 1 a“ = Gruppe I, Bereich A, Regal 1, Regalbrett a). Gruppen, Bereiche und Regale sind eigene Einträge mit Kennung, Name, Beschreibung und Reihenfolge (Tabelle `catalog_shelf_sections`, `catalog_shelves.section_id` und `board`). Der Standort-Code wird aus den vier Teilen zusammengesetzt; ändert sich eine Kennung, ziehen die Standorte der Regalbretter und der Exemplare darunter mit. Gelöscht werden können nur leere Ebenen. Die Migration hat aus den vorhandenen Codes lokal 1 Gruppe, 1 Bereich, 6 Regale und 30 Regalbretter angelegt; Regalbretter mit anderem Code bleiben „ohne Regal“ und lassen sich zuordnen (auch automatisch). Die Seite **Regale und Regalbretter** zeigt den Aufbau als Baum mit Farbleiste je Ebene, das Regalbrett mit Standort, Beschriftung, Themen als Chips und Exemplarzahl; alle Bearbeiten- und Hinzufügen-Formulare klappen an der jeweiligen Stelle auf. **Themenbereiche** stehen als Hauptbereich mit Karte und eingerückten Unterbereichen, je mit den zugeordneten Regalbrettern. Beim **Einsortieren** sind die Regalbretter nach Regal gruppiert („I › A › 1 · Name“), beim **Erfassen** der Themenbereich nach Hauptbereich (mit „↳“ für Unterbereiche).

### Thema → Regalbrett statt Signatur (v0.44.0)

Das Modell ist jetzt: **Inventarnummer, Thema (am Medium), Regalbrett (am Exemplar, beim Einsortieren)**. Es gibt keine eigene Signatur mehr in den Oberflächen. Ein Regalbrett gehört zu einem oder mehreren Themenbereichen, ein Themenbereich kann auf mehreren Regalbrettern stehen (Tabelle `catalog_shelf_topics`; die Migration übernimmt die bisherigen Verbindungen über die Signaturen). Beim **Einsortieren** nennt die Seite die Regalbretter zum Thema des Mediums (ohne Treffer die des übergeordneten Themenbereichs) und wählt vor: zuerst das Brett eines anderen Exemplars derselben Ausgabe, dann das erste passende Brett, dann das zuletzt benutzte. Einsortieren lernt keine Signatur mehr. Verwaltung: „Signaturen und Themen“ ist jetzt **Themenbereiche**; die Themen eines Regalbretts stellst du unter **Regalbretter** ein. Öffentliche Suche und Titelseite finden Themen über das Thema des Mediums und die Themen der Regalbretter ihrer Exemplare; die Spalte „Signatur“ in der Exemplarliste und das Etikett zeigen den Standort. Der **Altbestandsimport** übernimmt die Signaturen weiterhin im Hintergrund (Tabelle `catalog_signatures`, `signature_id` am Exemplar) und legt danach Regalbretter samt Themen an (`ImportCatalogShelvesFromSignaturesAction`, auch auf der Weboberfläche und im Konsolenbefehl).

### Qualitätsvorschläge im Zeitplan (v0.43.1)

`catalog:quality:propose` läuft jetzt jede Nacht um 02:30 Uhr im Zeitplan (nach der Sicherung um 01:30 Uhr) und holt DNB-Vorschläge für offene Qualitätsfälle vorab. Die Menge pro Nacht steht in `CATALOG_QUALITY_DAILY_LIMIT` (Standard 60, höchstens 500), klein gehalten, damit der Lauf auch über den Web-Cron in die Zeitgrenze des Anbieters passt. Der Befehl schreibt nur in die Prüftabelle, nie in den Katalog; die eindeutigen Vorschläge übernimmst du weiter unter `/betrieb/katalog/qualitaet/sicher`. Die Aufgabe erscheint auf Verwaltung → Systemzustand in den Zeitplan-Aufgaben und lässt sich dort einmal ausführen.

### Cover ohne Konsole (v0.43.0)

Auf **Verwaltung → Systemzustand** gibt es den Abschnitt **Cover der Bücher**: Zählwerte (mit Cover, Suche steht aus, schon ergebnislos gesucht, Aufgaben in der Warteschlange) und zwei Knöpfe, „Cover jetzt suchen (bis zu 200 Titel)“ und „Auch erfolglos gesuchte erneut suchen“ (entspricht `--retry-missing`, sinnvoll nach dem Eintragen eines Google-Books-Schlüssels). Die Cover lädt der Cron im Hintergrund; „Cron-Lauf jetzt auslösen“ arbeitet ein Stück davon ab. Der nächtliche Lauf (03:30 Uhr) reiht jetzt standardmäßig 200 statt 50 Titel ein (`CATALOG_COVER_DAILY_LIMIT`, höchstens 1000). Außerdem lädt `ScheduledJobs` die Zeitplan-Aufgaben jetzt über den Konsolen-Kernel nach, damit die Liste „Zeitplan-Aufgaben“ mit den Knöpfen „Einmal ausführen“ auch im Browser auf dem Webspace gefüllt ist (dort war die Tabelle leer).

### Altbestand ohne Konsole (v0.42.0)

Neue Seite **Bestand → Altbestand übernehmen** (`/betrieb/katalog/altbestand`, Recht `catalog.import`): Die phpMyAdmin-JSON-Exporte des alten Systems (`mediaList`, optional `mediaTopicList`, `mediaSignatures`) werden hochgeladen, geprüft (Analyse ohne Schreibzugriff, mit Kennzahlen, Warnungen und blockierenden Konflikten) und nach Bestätigung übernommen (`ImportLegacyCatalogAction`, wie der Konsolenbefehl `catalog:legacy:import`). Dazu übernimmt `ImportLegacyWishesAction` die Tabelle `bookWishes` als offene Wünsche ohne Person (doppelte ISBN werden übersprungen, Zeitstempel bleiben). Damit lässt sich das System auf dem Webspace komplett von Null aufbauen. Der Import wird im Protokoll vermerkt (`catalog.legacy.imported`).

### Start vorbereitet: Zurücklegen lassen, Excel-Import, Klassen, Datenbereinigung (v0.41.0)

- **Zurücklegen lassen (Issue 4):** Der Knopf auf der Titelseite erscheint auch bei freiem Exemplar. Ist eins frei, wird es sofort für die Person zurückgelegt (Vormerkung mit Abholfrist, `PlaceReservationAction` ruft `ReservationQueueService::promoteForCopy`), sonst reiht sich die Vormerkung in die Warteschlange ein. Die Grenzen (Höchstzahl offener Vormerkungen, keine Doppelten, nicht schon ausgeliehen, Altersfreigabe) gelten weiter; nur die Warteschlangen-Grenze betrifft freie Titel nicht.
- **Klassendaten per Excel:** Der Import liest `.xlsx` (erstes Tabellenblatt) und weiterhin CSV, ohne zusätzliche Bibliothek (`XlsxReader` mit ZipArchive und SimpleXML). Excel-Datumswerte (Tageszahlen) werden in Datumsangaben umgerechnet. Die **Excel-Vorlage** für die Klassenleitungen (`XlsxTemplate`) hat die Kopfzeile, eine Datumsspalte im Format TT.MM.JJJJ und ein Blatt „Hinweise“; die CSV-Vorlage bleibt als Zweitweg. Voraussetzung auf dem Server: PHP-Erweiterung `zip` (bei Strato Standard).
- **Alle Klassen der Schule:** Unter Verwaltung → Schule legt „Alle Klassen der Schule anlegen“ je Schuljahr die 47 Klassen an: Grundschule 1.1 bis 6.3, Mittelstufe 7.1 bis 10.5 mit 9.6, 10.6 und WiKo, Oberstufe 11.1 bis 11.4, 12 und 13. Vorhandene bleiben unverändert. Die WiKo zählt als Jahrgang 7 (Jahrgang ist Pflichtfeld).
- **Demodaten entfernen:** `php artisan app:launch-reset` (mit Rückfrage, vorher Sicherung) löscht Personen, Ausleihen, Vormerkungen, Wünsche, Konten, Schuljahre, Klassen, Protokoll, Etikettenläufe und Inventuren sowie alle Titel ohne Herkunft aus dem Altsystem. Der Altbestand, Signaturen, Regalbretter, Themenbereiche, Regeln, Seitentexte, Öffnungszeiten und Ausweis-Designs bleiben. Danach das Verwaltungskonto auf `/_setup` anlegen. Auf dem Server gibt es keine Konsole: Die Bereinigung läuft lokal, die bereinigte Datenbank kommt als Sicherung auf den Server (GO_LIVE Abschnitt 6).
- **Entscheidungen des Auftraggebers:** Rollen-Matrix wie in der Projektbeschreibung (`config/authorization.php`), zusätzlich darf die Schüler-AG Erweitert Buchwünsche bearbeiten (v0.41.1), Regeln sind eingestellt, Rechtstexte sind angepasst, Google-Bilder werden gespeichert (nur der API-Key `CATALOG_COVER_GOOGLE_BOOKS_KEY` fehlt noch), Leihfristen und Höchstzahlen bestätigt.

### Buchwünsche: Gesamtanzahl und Liste als PDF (v0.40.0)

Die Übersicht unter `/betrieb/buchwuensche` nennt oben die **Gesamtanzahl** aller erfassten Wünsche und, bei gesetztem Filter, die Anzahl zum Filter (die Bildschirmliste zeigt höchstens 200, die Druckliste immer alle). Der Knopf **Liste als PDF** öffnet `/betrieb/buchwuensche/liste` im **Querformat auf dem Briefpapier** (Farbe, Schwarz-Weiß oder ohne, Standard aus `LETTERHEAD`), mit Nr., Titel, Autor:in, ISBN, Person, Datum und Stand; die Tabellenüberschrift wiederholt sich auf jeder Seite. Gespeichert wird über den Druckdialog des Browsers („Als PDF speichern“, Querformat, Ränder „Keine“). Das Querformat-Briefpapier liegt als `public/brand/vdbs/briefpapier-quer-farbe.png` und `briefpapier-quer-sw.png`. Außerdem wurden 31 Wünsche aus dem alten System einmalig importiert (lokal, nicht im Echtsystem).

### Etikett-Gestaltung (v0.39.2, Abstände v0.39.3)

Jedes Etikett (Vorrat und Exemplar) hat denselben Aufbau: **oben links das Logo (VDBS-Zeichen), oben rechts der Name „BiblioCollect“, unten mittig der Strichcode und darunter kleiner die Nummer** als Klartext für den Notfall ohne Scanner. Exemplar-Etiketten zeigen zusätzlich in der Mitte Signatur (oder Standort) und den Kurztitel, Vorratsetiketten lassen die Mitte frei. Der Strichcode ist bis zu 56 mm breit und 10,5 mm hoch (Modulbreite ca. 0,42 mm). Der Innenabstand im Etikett beträgt 3 mm oben/unten und 6 mm seitlich (`--label-pad-y`, `--label-pad-x` in der gemeinsamen Etikettenvorlage), zwischen Strichcode und Nummer liegt ein kleiner Abstand von 1,2 mm. Als PDF geprüft.

**Bestätigungen:** Rückfragen („Auftrag löschen“ usw.) erscheinen nicht mehr als Standard-Browserfenster, sondern als eigener Dialog im Design der Anwendung (Abbrechen/Bestätigen, Escape bricht ab, Beschriftung über `data-confirm-label`).

### Letzte Drucke der Vorratsetiketten (v0.39.1)

Auf der Seite „Etiketten auf Vorrat“ zeigt der Abschnitt **Letzte Drucke** die zehn letzten Druckaufträge (Zeit, wer, Reihe oder Lücken, Nummernbereich, Anzahl). Ein Auftrag lässt sich einzeln löschen („Auftrag löschen“): Seine Nummern gelten dann nicht mehr als gedruckt und werden wieder vergeben. „Alle gedruckten Nummern löschen“ vergisst alle gespeicherten Nummern, auch ältere ohne Auftrag. Beides fragt vorher nach, steht im Protokoll (`catalog.labels.run_deleted`, `catalog.labels.all_cleared`) und ändert nichts an Büchern oder Exemplaren. Wird eine Nummer neu gedruckt, gehört sie dem neuesten Auftrag. Tabellen: `catalog_label_runs`, `catalog_printed_labels.run_id`.

### Etiketten auf Vorrat, Format 70 × 36 mm (v0.39.0, Issue 9)

- **Format:** Alle Exemplar-Etiketten gehen jetzt auf Bögen mit 3 × 8 = **24 Etiketten (70 × 36 mm)**, weißer Hintergrund (Avery Zweckform 3490 oder gleichwertig), vorher 21 je Bogen. Auf der Druckseite gibt es einen Feinabgleich (rechts/unten in Millimetern) und die Wahl des ersten freien Platzes.
- **Etiketten auf Vorrat** (`/betrieb/etiketten/vorrat`, Recht `catalog.manage`): Inventarnummern im Voraus drucken (Vorlauf), mit Strichcode und Nummer. **Prüfung:** Nummern, die schon einem Exemplar gehören, werden nie gedruckt; Nummern, die schon einmal auf Vorrat gedruckt wurden, werden übersprungen (Neudruck nur auf ausdrücklichen Wunsch). Die Reihe läuft dadurch lückenlos weiter, die Seite zeigt vor dem Druck, was übersprungen wird. Gedruckte Nummern merkt sich `catalog_printed_labels`; höchstens 480 (20 Bögen) je Druck.
- **Einmalige Option „Lücken der laufenden Reihe füllen“:** Zwischen zwei Nummern (Vorschlag: niedrigste bis höchste vergebene) findet das System alle Nummern, die noch keinem Exemplar gehören, und druckt dafür Etiketten. Die Grenzen muss man auf die echte laufende Reihe stellen, ein Ausreißer wie `1234567` im Bestand verfälscht sonst den Bereich.

### GitHub-Issues 3 bis 8 (v0.38.0)

- **#5 Interne Suche lädt direkt:** Die Katalogpflege zeigt ohne Suchbegriff gleich die Titel (A–Z, mit Seitenwahl); Suchen und Filter schränken ein.
- **#7 Exemplare direkt sehen:** In der Trefferliste klappt je Titel „n Exemplare, m da“ auf (Inventarnummer, Ausgabe, Stand wie ausgeliehen bis TT.MM., Standort, Zugänglichkeit); auf der Titelseite der Katalogpflege steht der Abschnitt „Exemplare“ ganz oben (`x-catalog.staff-copies`).
- **#8 Qualitätsseite:** Die Auswahlzelle der Prüfansicht ist komplett anklickbar, nicht nur das kleine Häkchen.
- **#6 Beleg automatisch per Mail:** Nach „Vorgang bestätigen“ geht der Beleg an die Adresse am Ausleihkonto (sonst an das bestätigte Onlinekonto), nach der Antwort an den Browser, damit ein langsamer Mailserver den Tresen nicht aufhält; ein Fehler bleibt ohne Folgen. Drucken und Mailen an eine andere Adresse bleiben möglich. Ein/Aus unter „Regeln → Belege“ (`circulation.auto_receipt_mail`, Standard an); steht im Datenschutztext.
- **#3 Buchwunsch am Tresen:** Auf der Seite Buchwünsche öffnet der Knopf „Buchwunsch erfassen“ eine eigene Seite (`/betrieb/buchwuensche/neu`) mit ISBN-Abfrage und Namenssuche für die Person (Auswahl aus Treffern, keine persönlichen Daten eintippen); ohne Person geht es weiter.
- **#4 Vormerken-Knopf:** Der Knopf „Titel vormerken“ erscheint weiter nur, wenn alle Exemplare ausgeliehen sind. Neu erklärt die Titelseite angemeldeten Personen, warum er fehlt: Exemplar da (direkt ausleihen), Warteschlange voll, Vormerkgrenze erreicht, Vormerken ausgeschaltet.

### Briefpapier für Ausdrucke (v0.37.1)

Alle Ausdrucke auf A4, die über die Anwendung laufen (Klassenlisten, Beleg, Auskunft, Statistik, Inventurbericht, Aussonderungsliste, Ausweisliste und jede andere Seite, die man druckt), erscheinen auf dem VDBS-Briefpapier „BiblioCollect“ in Farbe. Das Bild (`public/brand/vdbs/briefpapier-farbe.png`, Schwarz-Weiß: `briefpapier-sw.png`, beide 2481 × 3508 Pixel) steht fest an der Seitenecke und wiederholt sich auf jeder Seite; Text und Tabellen bleiben in der freien Fläche (Kopf mit Logo oben, Punktband rechts, Punkte unten, Falzmarken links). Umschaltbar mit `LETTERHEAD=farbe|sw|aus` in der `.env`. Ausweis- und Etikettenbögen haben ein eigenes Layout und bekommen kein Briefpapier. Der Druck ist randlos (`@page margin 0`), dadurch druckt der Browser auch kein Datum und keine Adresse an den Rand. Geprüft mit einer mehrseitigen Liste in Edge (PDF): Briefpapier und Satzspiegel stimmen auf allen Seiten. Das Satzspiegel-Polster oben und unten wiederholt `box-decoration-break: clone`; Ränder in `resources/css/patterns/letterhead.css`.

### Vorbereitung Testeinsatz, Block 8: Startseiten, Hilfe, Einrichtung (v0.37.0)

- **Öffentliche Startseite:** zeigt die echten Öffnungszeiten und die Schließtage der nächsten 60 Tage aus der Datenbank, die wichtigsten Ausleihregeln aus den Einstellungen („bis zu 5 Medien für 14 Tage“), „Buchwunsch abgeben“, „Onlinekonto aktivieren“ und den Knopf Anmelden bzw. Zum Konto. Die Platzhalter („später“, „ab T2“, deaktivierter Knopf) sind weg; die Seite erscheint auch bei nicht erreichbarer Datenbank.
- **Verwaltungs-Startseite:** statt des Entwicklungsmodells ein Überblick mit Systemzustand, der Liste **„Startklar?“** (Schuljahr und Klassen, Öffnungszeiten, Rechtstexte, Konten, Ausweise, Bestand, Mailversand, Betriebsmeldungen, Cronjob, Sicherung, Demo-Konten) und den Verwaltungsaufgaben nach Rechten.
- **Hilfe** (`resources/help`): Überfällige Medien, verlorene Medien wiederfinden, Vormerk-Grenze, Regeln, Benutzerkonten, Systemzustand und Sicherung, Löschverlangen, Schuljahreswechsel mit automatischer Sicherung.
- **Dokumente:** neue Einrichtungsanleitung `docs/GO_LIVE.md` mit Probelauf (Smoke-Test) und Wiederherstellungsübung; `ROADMAP.md`, `README.md` und `OFFENE_PUNKTE.md` auf den echten Stand gebracht.
- **Release-Paket:** schließt Entwicklungshilfsdateien, `.foundation` (Sicherungen der `.env`) und `.env.testing` aus.

### Vorbereitung Testeinsatz, Block 7: Lücken am Tresen (v0.36.0)

- **Exemplar wieder verfügbar:** Wird ein als verloren oder beschädigt eingetragenes Exemplar am Arbeitsplatz gescannt, führt der Scan zur Seite „Exemplar wieder verfügbar machen“ (`/betrieb/exemplar/{Nummer}/gefunden`, Recht `circulation.manage`, also auch Schüler-AG Basis). Danach ist es wieder ausleihbar; wartet jemand, wird es gleich zurückgelegt und die Meldung sagt es.
- **Warteschlange rückt nach:** Neu erfasste Exemplare und Exemplare, die im Katalog wieder auf „aktiv“ gesetzt werden, lösen das Ereignis `CopyBecameAvailable` aus; `PromoteReservationsForAvailableCopy` (Circulation) legt sie für die erste wartende Vormerkung zurück. Vorher blieb die Vormerkung dann hängen.
- **Überfällige Ausleihen für alle:** `/betrieb/ueberfaellig` (Recht `circulation.manage`), längste Überfälligkeit zuerst; die Kachel auf dem Arbeitsplatz verlinkt dorthin. Der Klassenlisten-Druck bleibt `circulation.reports`.
- **Löschverlangen:** Am ausgeschiedenen Ausleihkonto gibt es „Daten jetzt anonymisieren“ (Recht `privacy.erase`, nur Verwaltung): Name, Mail, Klasse, Onlinekonto und Ausweis verlieren den Bezug, das Geburtsjahr bleibt; Belegadressen werden gelöscht; im Protokoll `privacy.patron.erased`. Nur nach dem Austritt möglich.
- **Schuljahreswechsel:** Vor dem Wechsel wird automatisch eine Datenbanksicherung erstellt; scheitert sie, bleibt alles unverändert (`BACKUP_BEFORE_TRANSITION`, Standard an).
- **Zugang wiederherstellen:** Die Einrichtungsseite `/_setup` hat „Notfall: Zugang wiederherstellen“: neues Passwort, Konto aktiv, Rolle Verwaltung (nur mit `SETUP_TOKEN`).
- Entwicklungshinweis „reagieren später über das Ereignis PatronDeparted“ aus dem Austritts-Panel entfernt.

### Vorbereitung Testeinsatz, Block 6: Erinnerungen auch ohne Onlinekonto (v0.35.0)

`reminders:send` schickt Erinnerungen (Rückgabe bald fällig, überfällig, Vormerkung abholbereit) nicht mehr nur an bestätigte Onlinekonten, sondern bei Konten ohne Onlinekonto an die **E-Mail-Adresse am Ausleihkonto**. Regeln: Ein bestätigtes Onlinekonto hat Vorrang, und hat die Person dort die Erinnerungen abgeschaltet, bleibt es dabei. Am Ausleihkonto schaltet das Personal sie je Konto ab (Kästchen im Formular „Ausleihkonto bearbeiten“, Standard an). Kein Versand an ungültige Adressen und an nicht aktive Konten. Die Mail spricht mit Vornamen an und ist mit „deine Schulbibliothek“ unterschrieben. Pro Lauf gehen höchstens 100 Mails (`--limit`), der Rest folgt beim nächsten Cron-Lauf, damit die Anfrage nicht zu lange dauert; jede Erinnerung kommt nur einmal. `reminder_logs` kennt dazu `patron_id`. Der Datenschutztext und die Auskunft nennen die Einstellung. Papier-Klassenlisten bleiben als zweiter Weg.

### Vorbereitung Testeinsatz, Block 5: Wartezeit-Grenze beim Vormerken (v0.34.0)

Eine Person darf mehrere Titel vormerken (Höchstzahl in „Regeln“), jeden Titel nur einmal. Neu: Für einen Titel werden nur so viele Vormerkungen angenommen, wie er aktive Exemplare hat (Einstellung „Vormerkungen je Exemplar eines Titels“, Standard 1). Damit wartet niemand länger als die **Wartezeit-Grenze = Leihfrist + eine Verlängerung + Puffer** (Standard 14 + 14 + 7 = 35 Tage; alle drei Werte stammen aus den Regeln, `LoanPolicy::reservationWindow`). Ist die Warteschlange voll, nennt die Meldung die Zahl der Vormerkungen und die Grenze in Tagen. Rückgabe, Verlängerungssperre und Abholfrist sind unverändert. Annahme, die im Betrieb zu bestätigen ist: „Titel zu lange belegt“ heißt hier „Warteschlange länger als Zahl der Exemplare“.

### Vorbereitung Testeinsatz, Block 4: Seite „Regeln“ (v0.33.0)

`/verwaltung/regeln` (Recht `settings.manage`, nur Verwaltung): Leihfristen (Schüler:innen, Lehrkräfte, Mitarbeiter:innen), Höchstzahl gleichzeitiger Ausleihen je Gruppe, Verlängern (Anzahl, Dauer, bei Überfälligkeit), Vormerken (**offene Vormerkungen je Person, 0 = ausgeschaltet**, Abholfrist, Puffer für die Wartezeit), Buchwünsche, Erinnerungen (Tage vor Fälligkeit, Abstand bei Überfälligkeit). Die Werte stehen in der Tabelle `app_settings` und überschreiben die Konfigurationsdateien (`config/circulation.php`, `config/reminders.php`); beim Start legt `SettingsRepository` sie über die Konfiguration, alle bisherigen `config()`-Zugriffe sehen sie damit ohne Codeänderung. Wert = Standard entfernt die Änderung, „Alle Regeln zurücksetzen“ stellt die Standardwerte her. Änderungen gelten sofort für neue Vorgänge (laufende Ausleihen behalten ihr Fälligkeitsdatum) und stehen im Protokoll (`settings.updated`, `settings.reset`). Neue Einstellung `reservation_buffer_days` (Standard 7) für die Vormerk-Regel in Block 5.

### Vorbereitung Testeinsatz, Block 3: MySQL/MariaDB (v0.32.0)

- **Die komplette Testsuite läuft auf MariaDB** (CI-Job `mariadb` in `.github/workflows/quality.yml`, lokal geprüft mit MariaDB 10.4). Dabei gefunden und behoben: Zeitspalten ohne Vorgabe (`timestamp`) bekamen auf MariaDB automatisch „bei Änderung auf jetzt setzen“ und eine ungültige Vorgabe, was zum Beispiel das Ausleihdatum bei jeder Rückgabe überschrieben hätte. Alle betroffenen Spalten sind jetzt `dateTime`.
- **Sicherung/Wiederherstellung:** `backup:database` schreibt für MySQL/MariaDB eine eigenständige Datei (`DROP TABLE`, `CREATE TABLE`, `INSERT`, in Blöcken gelesen, atomar über `.part`), `backup:restore` und phpMyAdmin spielen sie ohne vorherige Migration ein. Probe: Demodaten in MariaDB, Sicherung, Wiederherstellung in leere Datenbank, 42 Tabellen und alle Zeilen identisch.
- **Systemzustand → Datensicherung:** „Jetzt sichern“ und Download der letzten 15 Sicherungen ohne FTP (im Protokoll vermerkt). Scheitert die tägliche Sicherung, geht eine Betriebsmeldung an `ALERT_EMAIL`.
- Zwei Tests, die einzelne Migrationen zurückrollen, laufen nur auf SQLite.

### Vorbereitung Testeinsatz, Block 2: Zeit und Betriebseinstellungen (v0.31.0)

- **Zeitzone:** `APP_TIMEZONE` (Standard `Europe/Berlin`) gilt für Speicherung, Anzeige und Zeitplan. Vorher war UTC eingestellt, sodass der Zeitplan 07:00 UTC (= 09:00 Ortszeit) lief und Anzeigen zwei Stunden daneben lagen. Bereits gespeicherte Entwicklungsdaten wirken dadurch um 1 bis 2 Stunden verschoben; im Echtbetrieb beginnt man mit der neuen Einstellung.
- **Proxy und https:** `TRUSTED_PROXIES` (Standard `*`) für die X-Forwarded-Header des Hosters; mit https-`APP_URL` erzeugt die Anwendung außerhalb von lokal/Test nur https-Links; `SESSION_SECURE_COOKIE` ist dann automatisch an.
- **Drosselung:** Konto aktivieren höchstens 5 Versuche je Minute.
- **Wartungsseite `app:doctor`** prüft zusätzlich: Sitzungs-Cookie, Logkanal und -stufe, `SETUP_TOKEN` noch gesetzt, Datenbank-Treiber, Zeitzone, Schreibrechte `public/covers` und `public/card-designs`.
- **Apache:** In `covers` und `card-designs` laufen keine Skripte; die https-Weiterleitung steht auskommentiert in `public/.htaccess`. `robots.txt` sperrt Verwaltung, Betrieb, Konto und interne Adressen.
- **CSV-Exporte** (Statistik, Inventur, Aussonderung, Ausweise, Nummernumstellung) entschärfen Zellen, die mit `=`, `+`, `-`, `@` beginnen (`CsvExport`).

### Vorbereitung Testeinsatz, Block 1: Deutsch und Fehlerseiten (v0.30.0)

Sprache Standard ist Deutsch (Locale und Fallback). Neu: `lang/de/{validation,auth,passwords,pagination}.php` mit Feldnamen und `lang/de.json` (Mail-Fußzeilen, Fehlertitel). Die Bestätigungsmail für Onlinekonten ist deutsch („Bitte bestätige deine E-Mail-Adresse"), ebenso die Meldungen bei falschen Eingaben. Eigene Fehlerseiten (403, 404, 419, 429, 500, 503) im Aussehen der Anwendung unter `resources/views/errors/`; sie hängen nicht an Sitzung oder Datenbank. Plan und weitere Blöcke: `docs/GO_LIVE.md` (folgt in Block 8).

### Inventur (v0.22.0)

`/betrieb/inventur` (Recht `inventory.count`: Mitarbeiter:innen, Verwaltung, Schüler-AG Erweitert): Eine Inventur beginnen (immer nur eine laufende), Regalbrett wählen und die Inventarnummern der dort stehenden Bücher scannen. Jeder Scan zeigt sofort: richtig einsortiert, steht laut System woanders, hat noch keinen Standort, gilt als verloren oder ausgesondert, oder Nummer unbekannt. Beim Abschließen entsteht der Bericht (auch als Zwischenstand, Druck und CSV): **Fehlt** (laut System am geprüften Regalbrett, nicht gescannt, ausgeliehene zählen nicht), **Falsch einsortiert**, **Ohne Standort**, **Verloren oder ausgesondert, aber im Regal**, **Unbekannt**. Geprüft sind nur Regalbretter, an denen gescannt wurde. „Standorte korrigieren“ setzt falsch eingetragene Standorte und fehlende auf das gefundene Regalbrett (protokolliert); mit Fehlendem passiert nichts automatisch.

### Filter „nur jetzt verfügbare Titel“ (v0.21.0)

Im öffentlichen Katalog (Suche und erweiterte Suche) und in der Katalogpflege gibt es das Kästchen „Nur jetzt verfügbare Titel“: Es bleiben Titel mit mindestens einem aktiven Exemplar, das weder ausgeliehen noch für eine Vormerkung zurückgelegt ist (`available_only`). Das Kästchen „Nur Titel mit aktiven Exemplaren“ bleibt als Katalogfilter bestehen.

### Aussonderung (v0.20.0)

- **Bücher aussondern** (`/betrieb/aussondern`, Recht `catalog.withdraw`, Mitarbeiter:innen und Verwaltung, nicht die Schüler-AG): Inventarnummern eingeben oder scannen (mehrere auf einmal), Prüfung, dann Grund (beschädigt, veraltet, doppelt, wird nicht mehr gelesen, sonstiges), Verbleib (entsorgt, verschenkt, verkauft, Archiv, offen) und Datum wählen und bestätigen. Ausgeliehene oder für eine Vormerkung zurückgelegte Bücher werden nicht ausgesondert (auch beim Bestätigen nochmals geprüft). Das Exemplar bleibt mit Status „ausgesondert“ im System, die Daten stehen in den bisherigen Feldern des Altsystems (`depreciation_reason`, `depreciated_at`, `further_use`), ältere Freitexte bleiben lesbar.
- **Liste der Aussonderungen** (`/betrieb/aussondern/liste`): Zeitraum und Grund wählen, Zusammenfassung nach Grund, Druck, CSV-Export für den Jahresbericht. Ein Irrtum lässt sich mit „Zurückholen“ rückgängig machen (protokolliert). Die Statistik zeigt „Im Zeitraum ausgesondert“.

### Signaturen, Themenbereiche und Regalbrett-Vorschlag (v0.19.0)

- **Pflege** in der Verwaltung (Recht `shelves.manage`, Mitarbeiter:innen und Verwaltung): `/verwaltung/signaturen` (Signatur anlegen, ändern, Themenbereiche in gewählter Reihenfolge zuordnen, löschen nur ohne Exemplare) und `/verwaltung/themenbereiche` (Baum aus Haupt- und Unterbereichen mit Schlüssel und Beschreibung; Schleifen sind verboten, Löschen nur ohne Unterbereiche und Signaturen). Regalbretter lassen sich mit einer Signatur verbinden.
- **Bücher bekommen eine Signatur** bei der Erfassung (Schritt Exemplar), beim Anlegen und beim Bearbeiten eines Exemplars („Themenbereich / Signatur“).
- **Vorschlag beim Einsortieren**, in dieser Reihenfolge: Regalbrett der Signatur des Exemplars, Regalbrett der anderen Exemplare derselben Ausgabe, zuletzt benutztes Regalbrett. Das System lernt: Wird ein Buch ohne Signatur auf ein Regalbrett mit Signatur gestellt, bekommt es diese Signatur.

### Benutzerkonten in der Verwaltung (v0.18.0)

Die Lücke war: Konten für Mitarbeitende, Verwaltung und technische Administration gab es nur über Einrichtungsseite oder Konsole, Rollen nur für Schüler-AG über die Konten der Schüler:innen, und Deaktivieren ging nur beim Austritt.

- **Seite** `/verwaltung/benutzer` (Recht `users.manage`, nur Verwaltung): Liste mit Suche, Filter nach Rolle und Stand (aktiv, eingeladen, deaktiviert).
- **Konto anlegen und einladen:** Name, E-Mail, mindestens eine Rolle (kombinierbar). Die Person bekommt einen Link zum Festlegen des Passworts; Passwörter vergibt die Verwaltung nie. Der Link bestätigt zugleich die E-Mail-Adresse. Einladung lässt sich erneut senden, auch als „Passwort zurücksetzen“.
- **Rollen ändern**, **Konto deaktivieren** (mit Grund; laufende Anmeldungen werden sofort beendet) und wieder aktivieren.
- **Schutz vor Aussperren:** Das letzte aktive Konto mit dem Recht „Benutzerkonten verwalten“ kann weder deaktiviert noch seiner Rolle beraubt werden, das eigene Konto nicht deaktiviert werden.
- Alle Schritte stehen im Protokoll (`identity.user.*`).

### Betriebsüberwachung (v0.17.0)

- **Systemzustand** (`/verwaltung/systemzustand`, Recht `system.view`, Verwaltung und technische Administration): Prüfungen für Datenbank, Cron, Warteschlange, Datensicherung, Fehler der letzten 24 Stunden, Mail und Speicherplatz mit Ergebnis in Ordnung, Achtung oder Fehler, dazu die letzten 25 Fehler (gleiche zusammengefasst, nach 30 Tagen aufgeräumt) und ein Knopf „Testmeldung senden“.
- **Meldungen per Mail** an `ALERT_EMAIL` bei unerwarteten Fehlern (nicht bei 404, Anmeldung, Berechtigung, Formularprüfung), fehlgeschlagenen Jobs und wenn der Cron länger als `CRON_GAP_MINUTES` (15) ausgefallen war. Dieselbe Meldung höchstens alle 30 Minuten. Es werden nie Anfragedaten, Cookies oder Eingaben gespeichert; Datenbankfehler erscheinen ohne Einzelheiten.
- **Statusadresse** `/_status` (JSON, Schlüssel `STATUS_TOKEN` oder `CRON_TOKEN` im Header `X-Api-Key`) für ein Monitoring wie UptimeRobot; bei Fehlern HTTP 503.

### Medium erfassen angepasst (Issue 2)

- **Getrennte Abfragen:** Erst die Inventarnummer (Schritt 1), dann ISBN oder Titel/Autor:in (Schritt 2); Treffer prüfen, Titel & Ausgabe, Exemplar und Prüfen & speichern folgen (6 Schritte).
- **Regel:** Neue Inventarnummern bestehen aus genau 7 Ziffern (Erfassung und Anlegen eines Exemplars; bestehende Nummern bleiben bearbeitbar, solange sie nicht geändert werden).
- **Einsortieren ist ein eigener Vorgang:** Der Standort gehört nicht zur Erfassung. Der Stapel „Einsortieren“ (`/betrieb/einsortieren`, Recht `circulation.manage`, auch Schüler-AG) ergibt sich aus dem Standort: **Jedes Exemplar im Bestand (aktiv oder beschädigt) ohne Standort gilt als nicht einsortiert**, auch der Altbestand; verlorene und ausgesonderte nicht. Neue Exemplare (Erfassung und Anlegen in der Katalogpflege) bekommen nie sofort einen Standort. **Zuerst das Buch scannen, dann das Regalbrett bestätigen** (vorgewählt: Brett der Signatur, sonst das zuletzt benutzte; alternativ ein Regalbrett-Etikett scannen; Seite ist auch für das Handy gedacht, die Kamera-Erfassung folgt später). Standort und `shelved_at` werden vermerkt (Protokoll `catalog.copy.shelved`), Kachel am Arbeitsplatz.
- **Alte Inventarnummern umstellen** nur auf ausdrücklichen Auftrag: `/verwaltung/inventarnummern` (Recht `inventory.renumber`, Mitarbeiter:innen und Verwaltung): Exemplare auswählen, neue Nummern ab der höchsten vorhandenen siebenstelligen Nummer, alte Nummer im Protokoll, danach Etiketten drucken. Nichts wird automatisch umgestellt.
- **Regalbretter pflegen** dürfen Mitarbeiter:innen und Verwaltung (Recht `shelves.manage`, ohne Zugang zum übrigen Verwaltungsbereich), nicht die Schüler-AG.
- **Regalbretter aus den Signaturen:** In der Verwaltung legt „Regalbretter aus Signaturen anlegen“ zu jeder Signatur ein Regalbrett an (Bezeichnung = Signatur, Beschriftung = Themenbereiche, Verbindung über `signature_id`). Wiederholbar; alte Schreibweisen wie „IA1d“ werden dem Brett „I. A 1 d“ zugeordnet.
- **Standort als Auswahlliste:** Tabelle `catalog_shelves` (Bezeichnung als Standort, Beschriftung, Reihenfolge, auswählbar). Die Verwaltung pflegt sie unter `/verwaltung/regalbretter` (Recht `shelves.manage`); Umbenennen zieht die Exemplare mit, Löschen nur ohne Exemplare. Die bisherigen Freitext-Standorte wurden beim Einspielen als Regalbretter übernommen. Gilt in der Erfassung, beim Anlegen und beim Bearbeiten von Exemplaren.
- **Zusammenfassung:** Fehlt sie beim DNB-Treffer, wird sie zur ISBN bei Google Books (mit Schlüssel, falls hinterlegt) und danach Open Library nachgeschlagen und als Vorschlag mit Quellenhinweis eingetragen (`config/catalog.php`, `summaries`).

### Vorgangsorientierte Navigation und Buchwünsche (v0.12.0)

- **Navigation des Bibliotheksbetriebs** nach Vorgängen: Arbeitsplatz, Ausleihe und Rückgabe, Ausweise ausgeben, Vormerkungen, Buchwünsche, Medium erfassen, Ausleihkonten, Alle Vorgänge, Hilfe. Statistik, Klassenlisten und Katalogpflege stehen auf der neuen Seite **Alle Vorgänge** (`/betrieb/vorgaenge`, definiert in `config/processes.php`; zeigt je Konto nur die erlaubten Vorgänge, in die getrennten Bereiche **Betrieb** (Ausleihe, Ausweise und Personen, Katalog, Hilfe) und **Verwaltung** (Konten und Ausweise einrichten, Bestand importieren, Auswertungen und Protokoll, Schule und Öffnungszeiten, Inhalte), jeweils nach Aufgaben gruppiert).
- **Buchwünsche** (Tabelle `circulation_book_wishes`, Recht `wishes.manage` für Mitarbeiter:innen und Verwaltung): **Buchwunsch erfassen ist ein öffentlicher Vorgang** (`/buchwunsch`, Punkt „Buchwunsch“ in der öffentlichen Navigation, ohne Anmeldung): ISBN mit Suchknopf (schlägt Titel und Autor:in über die DNB vor, speichert nichts), Titel (Pflicht), Autor:in, Bemerkung, Name und E-Mail (freiwillig, für die Rückmeldung), Speichern. Schutz vor Missbrauch durch Honeypot-Feld und Begrenzung auf 5 Wünsche pro Stunde und Adresse. Angemeldete Leser:innen mit Ausleihkonto bekommen den Wunsch automatisch ihrem Konto zugeordnet (höchstens 3 offene, keine Doppelten) und sehen Stand und Antwort in „Mein Konto“ (`/konto/buchwuensche`, mit Zurückziehen). Am Arbeitsplatz (`/betrieb/buchwuensche`) werden sie gefiltert, gesucht, mit Hinweis auf weitere Wünsche für denselben Titel angezeigt; Stand setzen (angenommen, bestellt, ist da, abgelehnt) mit kurzer Antwort, dabei geht eine Mail an die Person. Wünsche lassen sich auch am Tresen erfassen (mit oder ohne Bibliotheksnummer, ebenfalls mit ISBN-Suche, die Titel und Autor:in vorschlägt). Im öffentlichen Katalog führt „Nichts gefunden“ zum Wunschformular. Abgeschlossene Wünsche verlieren nach der Aufbewahrungsfrist den Personenbezug und die freiwilligen Kontaktangaben, die Auskunft enthält die Wünsche der Person. Kachel „Neue Buchwünsche“ am Arbeitsplatz. Ist ein Wunsch erfüllt, erscheint in den folgenden 30 Tagen ein Hinweis mit Link zum Katalog auf der Übersicht von „Mein Konto“ (eine Vormerkung wird nicht automatisch angelegt).

### Nicht personalisierte Ausweise (nach v0.9.0)

- **Modell:** Ein Ausweis trägt Logo, ein Feld „Name“ zum Selbsteintragen und einen Strichcode mit zufälliger Ausweisnummer (10 Ziffern, Luhn-Prüfziffer, nie fortlaufend, nie wieder vergeben; Tabelle `patron_cards`, Nummern werden nie gelöscht). Klasse oder Bibliotheksnummer stehen nicht darauf, der Ausweis überlebt Klassenwechsel.
- **Verwaltung** (`/betrieb/ausweise`, Recht `patrons.manage`): Chargen erzeugen (1–1000), Bögen drucken (eigene Seite je Charge `/betrieb/ausweise/charge/{n}`; Vorderseite mit Motiv, weißer Fläche, Namensfeld zum Selbsteintragen, Logo und Strichcode; Rückseite mit Motiv und Logo auf weißer Fläche; randlos, 6 mm weißer Innenrand; Startposition für angebrochene Bögen, Versatz-Regler). **Motive** (`/betrieb/ausweise/motive`, Tabelle `patron_card_motifs`): ein Motiv ist ein Paar aus Vorder- und Rückseitenbild (PNG/JPG, Verhältnis 85 : 54, mind. 1000 px breit, max. 8 MB), das zusammen hochgeladen wird, ein- und ausschalten, löschen; mitgeliefert sind vier (`public/brand/vdbs/card-defaults/`), Uploads liegen in `public/card-designs/`. Beim ersten Druck bekommt jeder Ausweis sein Motiv (gespeichert in `patron_cards.motif_id`), das auf Vorder- und Rückseite sowie bei Nachdrucken gleich bleibt; die aktiven Motive werden dabei zufällig verteilt, voreingestellt gleichmäßig; die Prozentwerte lassen sich je Druck einmalig überschreiben (Summe 100) und werden nicht gespeichert. Die Rückseite zeigt dasselbe Motiv an der gespiegelten Position des Bogens (Wenden an der langen Kante). CSV-Export der Nummern. Status: Erzeugt → Im Druck (beim Drucken der Vorderseite oder beim Export) → Verfügbar (von Hand) → Zugeordnet → Gesperrt.
- **Am Tresen** (Ausleihe, ein Scanfeld für alles): Ein neuer, freier Ausweis führt auf die Seite „Ausweis registrieren“ (`/betrieb/ausleihe/ausweis-registrieren`) mit Personensuche; ein Klick auf die Person ordnet den Ausweis zu und öffnet deren Ausleihbildschirm. Oder auf dem Personenbildschirm „Ausweis zuordnen“. Auf der **Kontoseite** der Person (`/betrieb/ausleihkonten/{id}`) stehen alle Ausweise mit Status; dort lässt sich sperren (verloren, defekt, eingezogen) und neu ausstellen (Grund für den bisherigen Ausweis wählbar). Ein neuer Ausweis sperrt alle früheren der Person (Grund „ersetzt“). „Ausweis ist verloren“ sperrt sofort. Gesperrte Ausweise werden beim Scannen abgewiesen.
- **Klassenweise Ausgabe** (`/betrieb/ausweise-ausgabe`): Klasse, alle Klassen oder „ohne Klasse“ wählen; je Person ein Scanfeld, nach dem Scannen springt der Cursor zur nächsten Person ohne Ausweis. Filter „nur Personen ohne Ausweis“ und Druck machen die Seite zur Auswertung, wer noch keinen Ausweis hat.
- **Ohne Ausweis** geht es weiter über die Namenssuche oder die Bibliotheksnummer.
- Beim Austritt einer Person werden ihre Ausweise gesperrt, bei der Anonymisierung verlieren sie den Personenbezug (Nummer bleibt reserviert). Die Auskunft nach Art. 15 nennt die Ausweise der Person.

### Statistik, Hilfe und Barrierefreiheit (v0.9.0)

- **Statistik** unter `/betrieb/statistik` (Recht `statistics.view`, Mitarbeiter:innen und Verwaltung): Ausleihen, Rückgaben, Verlängerungen, aktive Leser:innen, offene und überfällige Ausleihen, Verlauf je Monat, beliebteste Titel, Klassen, Medientypen und Bestand; nur Zählwerte. CSV-Download und Druckansicht.
- **Hilfe** unter `/betrieb/hilfe` mit Anleitungen aus `resources/help/*.md` (Ausleihe, Katalog und Ausleihkonten, Verwaltung).
- **Automatische Barrierefreiheitsprüfung** (`tests/Feature/AccessibilityTest.php`) über alle Hauptseiten; sie fängt Rückschritte ab, ersetzt aber keine Prüfung mit Screenreader und Tastatur.

### Ausleihterminal, Etiketten, Rechtliches und Regeln (v0.8.0)

- **Ausleihterminal** in zwei Bildschirmen (Navigation „Ausleihe“). *Start* (`/betrieb/ausleihe`): ein Scanfeld; ein Ausweis (Bibliotheksnummer) führt zur Person, der Barcode eines Exemplars sammelt eine Rückgabe ohne Person, ein Name startet die Personensuche. *Person* (`/betrieb/ausleihe/person`): Übersicht aller ausgeliehenen Medien mit „Verlängern“ und „Zurückgeben“, ein Scanfeld für weitere Medien (eigene Ausleihe → Rückgabe, freies Exemplar → Ausleihe) und der Vorgang mit allen Positionen. Jede Position wird sofort geprüft, gebucht wird nichts, bis gemeinsam bestätigt wird. Bestätigen läuft in einer Transaktion (erst Rückgaben, dann Verlängerungen, dann Ausleihen; scheitert eine Position, wird nichts gebucht). Danach Beleg `V-JJJJMMTT-NNN` zum Drucken oder per E-Mail (Adresse aus dem Konto oder frei eingegeben). Rückgaben gehen auch ohne Person.
- **Leihfristen und Obergrenzen** je Art des Ausleihkontos, Frist zusätzlich je Medientyp (`config/circulation.php`, `LoanPolicy`).
- **Problem melden** an der Ausleihe: beschädigt zurücknehmen oder als verloren melden.
- **Etiketten** (`/betrieb/etiketten`) und **Bibliotheksausweise** (`/betrieb/ausweise`) mit Code-128-Strichcode zum Drucken.
- **Passwort vergessen**, Begrenzung der Anmeldeversuche, Sicherheits-Header mit Content-Security-Policy im Produktivbetrieb.
- **Informationsseiten** Impressum, Datenschutz, Barrierefreiheit mit Bearbeitung unter `/verwaltung/seiten`; Links im Fußbereich.
- Belege werden mit den Ausleihen nach drei Jahren anonymisiert.

### Betrieb, Arbeitsplatz und Webspace (v0.7.0)

- Arbeitsplatz `/betrieb` mit Scanfeld (Bibliotheksnummer öffnet das Konto, Barcode bucht die Rückgabe) und Tagesübersicht.
- `app:cron` (ein Cronjob für Zeitplan und Warteschlange), `app:doctor` (Einrichtungsprüfung), `mail:test`, `backup:database`.
- Betrieb auf Webspace ohne SSH: Release-Paket (`scripts/build-release.ps1`), Einrichtungsseite `/_setup`, Web-Cron `/_cron/<Token>`, Cover ohne Symlink (`CATALOG_COVER_DISK=covers`). Siehe `docs/HOSTING_SHARED.md` und `docs/OPERATIONS.md`.
- DSGVO-Auskunft (Mitarbeiter:innen und Selbstauskunft im Portal) und Abmeldung von Erinnerungs-Mails.
- Schuljahreswechsel bietet nur noch Zieljahre nach dem laufenden an.

### Mehrere Öffnungszeiträume, Klassenleitung, Beispieldaten, gesammelte Qualitätsübernahme (v0.6.2/v0.6.3)

- Ein Wochentag kann mehrere Öffnungszeiträume haben (08:00–10:00 und 13:00–15:00).
- Klassen haben eine Klassenleitung (Freitext), die auf den Klassenlisten steht.
- `SampleOperationsSeeder` legt Beispielkonten, Klassen, Schließtage, Ausleihen und Vormerkungen auf vorhandenen Exemplaren an (keine Medien).
- `catalog:quality:propose` holt DNB-Vorschläge vorab; die Seite `/betrieb/katalog/qualitaet/sicher` übernimmt eindeutige Vorschläge nach Bestätigung gesammelt.

### Datenschutz und Klassenlisten (v0.6.1)

- `privacy:anonymize` anonymisiert Ausleihen, Vormerkungen, ausgeschiedene Ausleihkonten samt Onlinekonten, Ereignisse und Protokoll nach 3 Jahren; Erinnerungsprotokolle werden gelöscht. Wöchentlich im Scheduler, `--dry-run` zum Zählen.
- Klassenlisten (`/betrieb/klassenlisten`) als Sammeldruck offener oder überfälliger Ausleihen je Klasse für die Klassenleitungen, Recht `circulation.reports`.
- Entscheidung: keine Mahnungen und Gebühren.
- Details in `docs/PRIVACY_AND_CLASS_LISTS.md`.

### Import von Ausleihkonten (v0.6.0)

- `/betrieb/ausleihkonten/import`: CSV-Import für Schüler:innen, Lehrkräfte und Mitarbeiter:innen mit Vorlage, Vorschau, Fehlersperre und automatisch vergebenen Bibliotheksnummern.
- Details und Dateiformat in `docs/PATRON_IMPORT.md`.

### Schuljahreswechsel (v0.5.5)

- `/verwaltung/schuljahreswechsel`: Vorschau mit Vorschlägen je Klasse, Zuordnung, Bestätigung, Ausführung ganz oder gar nicht samt Aktivierung des neuen Jahres.
- Ausleihkonten mit offenen Ausleihen oder Vormerkungen dürfen nicht mehr ausscheiden (einzeln und beim Wechsel).
- Details in `docs/SCHOOL_YEAR_TRANSITION.md`.

### Portal und Erinnerungen (v0.5.4)

- „Mein Konto“ zeigt eigene Ausleihen und Vormerkungen; Verlängern, Vormerken (von der Titelseite) und Stornieren in Selbstbedienung.
- `reminders:send` verschickt täglich Erinnerungen (bald fällig, überfällig, abholbereit) an bestätigte, verknüpfte Onlinekonten.
- Details in `docs/PORTAL_AND_REMINDERS.md`.

### Protokoll und Öffnungszeiten (v0.5.3)

- Verwaltungsmaske für Öffnungszeiten und Schließtage (`/verwaltung/oeffnungszeiten`).
- Modul `Audit` mit unveränderlichem Ereignisprotokoll für Ausleihe, Vormerkungen, Katalogerfassung, Metadatenübernahme und Kalender; Einsicht unter `/verwaltung/protokoll` mit dem Recht `audit.view` (nur Verwaltung).
- Details in `docs/AUDIT_AND_CALENDAR.md`.

### Circulation v0.5.2

- Öffentliche Verfügbarkeit aus offenen Ausleihen (`CopyAvailabilityService`): „Verfügbar“, „n von m Exemplaren verfügbar“, „Derzeit ausgeliehen“, frühestes Rückgabedatum und Zahl der Vormerkungen; ohne Barcodes oder Personen.
- Verlängerungen mit zentralen Regeln: Höchstzahl, Fristberechnung ohne Verkürzung, Schließtage, Sperre bei überfälligen Ausleihen und bei wartenden Vormerkungen.
- Titelbezogene Vormerkungen mit Warteschlange, Zurücklegen bei Rückgabe, Abholfrist, Storno, Fristablauf (`circulation:reservations:expire`) und Übersichtsseite `/betrieb/vormerkungen`.
- Der Katalog-Scan erkennt zusätzlich ISBNs mit falscher Prüfziffer; `catalog:covers:queue` überspringt bereits ergebnislos geprüfte Ausgaben (`--retry-missing`); der Scheduler holt Cover und beendet Abholfristen täglich.
