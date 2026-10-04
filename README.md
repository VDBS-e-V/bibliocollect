# BiblioCollect T2 v0.3.0

Dieser Patch baut auf **T1 v0.2.2** auf und führt die ersten fachlichen Kerndomänen ein:

- Identity: Anmeldung, Abmeldung, E-Mail-Verifikation, persistierte kombinierbare Rollen
- Patrons: eigenständige Ausleihkonten mit Bibliotheksnummer, Pflicht-Geburtsdatum und optionaler E-Mail
- Sichere Verknüpfung Onlinekonto ↔ Ausleihkonto über einmaligen persönlich ausgegebenen Code
- School: Schuljahre, Klassen, Wochenöffnungszeiten, konkrete Schließtage und `SchoolCalendarService`
- Server-seitiger Schutz von Portal, POS und Verwaltung mit `auth` + `verified` + Permission
- VDBS-konforme Anmelde-, Aktivierungs- und Verifikationsseiten
- Architektur- und Feature-Tests für Modulgrenzen, Rollen, Verknüpfungscodes und Öffnungstage

## Wichtige Architekturentscheidung

`User` und `Patron` bleiben getrennte Datensätze. Identity kennt das Patrons-Modul nicht konkret, sondern nur das Interface `PatronLinkGateway`. Die Implementierung liegt im Patrons-Modul. Technische Admin-Konten benötigen keinen Patron.

Neue fachliche Tabellen verwenden ULIDs. Die bestehende Laravel-Auth-Tabelle `users` behält in T2 ihren numerischen technischen Primärschlüssel, erhält aber zusätzlich eine `public_id` als ULID. Damit vermeiden wir jetzt eine destruktive Änderung an der Session-/Auth-Basis.

## Dateien

- `BiblioCollect_T2_v0.3.0_from_v0.2.2.patch` – normaler Patch für den aktuellen Stand
- `prerequisite/00_T1_v0.2.2_from_main.patch` – nur für einen frischen Checkout, der noch auf dem gepushten T1-v0.2.0-Stand steht
- `APPLY.md` – konkrete Windows-/XAMPP-Schritte
- `TEST_FLOW.md` – optionaler manueller Test des Konto-Verknüpfungsflusses
