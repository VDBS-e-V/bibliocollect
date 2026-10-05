# Projektstatus — BiblioCollect

Stand: T3 v0.4.3 – Verantwortlichenpflege

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
