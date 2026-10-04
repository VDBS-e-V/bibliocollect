# Projektstatus — BiblioCollect

Stand: T3 v0.4.1 – Bibliografische Metadaten und titelbasierte Suche

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
