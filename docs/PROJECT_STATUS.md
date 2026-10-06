# Projektstatus — BiblioCollect

Stand: v0.6.3. Katalog mit Erfassung, Qualitätsprüfung und Covern; Ausleihe mit Verlängerung, Vormerkung und Abholung; Portal, Erinnerungen, Protokoll, Schuljahreswechsel, Import und Anonymisierung. Die Abschnitte unten sind nach Themen geordnet, neuere Bausteine stehen oben in den Unterabschnitten „v0.5.x/v0.6.x“.

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
