# Projektstatus — BiblioCollect

Stand: T2 v0.3.4

## Abgeschlossen

- T0 Foundation mit Laravel 13, Modul-/Surface-Struktur, CI, Pint, PHPStan und Pest
- T1 mit Permission-/Role-Registry, Navigation, vier Surfaces, VDBS-App-Shell und `Europe/Berlin`
- getrennte Onlinekonten (`User`) und Ausleihkonten (`Patron`)
- E-Mail-Verifikation und einmalige Patron-Verknüpfungscodes
- persistierte kombinierbare Rollen
- Mitarbeiteroberfläche für Patron-Suche, Stammdaten und Klassenzuordnung
- protokolliertes Sperren/Entsperren
- kontrollierter dauerhafter Austritt mit Statusprotokoll und Onlinekonto-Deaktivierung
- Schuljahr-/Klassenverwaltung und Vorbereitung des Schuljahreswechsels
- `SchoolCalendarService` als Grundlage für Öffnungstage

## Noch offen in/ab T2

- vollständiger Massenworkflow für Schuljahreswechsel mit Vorschau, Mapping und Konfliktbehandlung
- produktiver Schulimport
- Öffnungszeiten-/Schließtage-Verwaltungsmaske

## Danach

T3 `Catalog`: Titel/Editionen, physische Exemplare, Medienarten, Barcode- und Statusmodell, öffentliche Recherche und erste interne Katalogpflege.
