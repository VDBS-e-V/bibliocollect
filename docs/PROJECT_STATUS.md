# Projektstatus — BiblioCollect

Stand: T3 v0.4.5 – Öffentlicher Katalog

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
- Die öffentliche Oberfläche nennt diesen Zustand nicht „verfügbar“, weil laufende Ausleihen erst mit T4 Circulation bekannt sind.
- `/katalog/titel/{titleId}` zeigt Titel, Verantwortliche, Ausgaben, Altersangaben, aktive Regalstandorte und aggregierte Bestandszustände.
- Copy-Barcodes und interne ULIDs werden in der öffentlichen Ausgabe nicht angezeigt.
- `CatalogHoldingService` bündelt die wiederverwendbare Bestandsaggregation für Titel und Ausgaben.
- `PublicCatalogPresenter` übersetzt offene technische Werte ausschließlich für die Public-Surface; unbekannte Werte bleiben darstellbar.
- Der Development-Seed enthält zusätzlich einen englischsprachigen Titel mit aktivem Bestand und einen Titel ohne physische Exemplare.
- HTTP-, Query-, Filter-, Pagination-, Datenschutz- und Seed-Regressionen sind automatisiert abgedeckt.
