# Entwicklungsplan — BiblioCollect

Stand: nach v0.5.2 (öffentliche Verfügbarkeit, Verlängerungen, Vormerkungen mit Abholung) auf Basis von v0.5.1, T4 v0.5.0, Legacy-Katalogmigration, Katalogrecherche, Cover-Cache und Seitennavigation. Die Reihenfolge folgt `docs/NEXT_STEPS.md`, `docs/T4_CIRCULATION.md` ("Bewusst später") und den leeren Modulen im Foundation-Gerüst.

## Phase 0 — Release-Hygiene (sofort)

Der aktuelle Arbeitsbaum auf `feature/public-catalog-redesign` enthält drei Blöcke ungetrennt, ist nicht committet und grün (158 Tests).

1. Der Arbeitsbaum ist in thematische Commits aufgeteilt (Recherche/Cover/Seitennavigation, Erfassung mit DNB, Cover-Darstellung, Katalogqualität).
2. Die `*.patch`-Dateien im Repo-Root sind per `.gitignore` ausgeschlossen.
3. Erledigt: Branch `feature/public-catalog-redesign` ist nach `main` gemergt und als `v0.5.1` getaggt.

## Phase 1 — Realdaten absichern (Katalog abschließen)

1. `catalog:legacy:import` auf dem realen Altbestand ausführen, danach `catalog:legacy:audit-quality` und den JSON-Bericht prüfen.
2. Metadaten-Prüfung mit Vorschlägen (`/betrieb/katalog/qualitaet`): **umgesetzt**, siehe `docs/CATALOG_QUALITY_REVIEW.md`. Jetzt den Bestand damit abarbeiten.
3. Zeichensatzverluste (`Mu?nchen`) werden dort gefunden und, wo die DNB den Ursprung belegt, zur Bestätigung vorgeschlagen; ohne Beleg bleiben sie manuelle Prüffälle.
4. Cover-Betrieb: Scheduler-Eintrag für `catalog:covers:queue`, `--retry-missing` und `storage:link` sind umgesetzt. Offen im Betrieb: Queue Worker und `schedule:run` dauerhaft einrichten, Google-Books-Key eintragen (Nutzungsbedingungen prüfen).

Gate: Audit ohne ungeklärte Contributor-Lücken, Cover-Job idempotent, kein externer Request in Webrequests.

## Phase 2 — T4 Circulation vervollständigen (Kernnutzen)

Reihenfolge ist fachlich bedingt:

1. ✔ **Öffentliche Verfügbarkeit** auf Basis offener Loans. Erst danach darf "aktiv" zu "derzeit verfügbar" werden. Sie bleibt titelbezogen, ohne Barcodes oder ULIDs.
2. ✔ **Verlängerungen** mit expliziten Regeln auf dem bestehenden `Loan`-Modell (maximale Anzahl, Sperre bei Vormerkung, Schließtage über `SchoolCalendarService`).
3. ✔ **Vormerkungen**, titelbezogen, mit Zahl der Vormerkungen in der öffentlichen Titelansicht und Abholung (zurücklegen, Abholfrist, Ablauf). Offen: Selbstbedienung im Portal und Benachrichtigungen.
4. **Mahnungen und Gebühren** nur, wenn die Schule sie fachlich will. Vorher als Entscheidung klären.
5. **Differenzierte Einsicht in Ausleihhistorie** zusammen mit dem Datenschutzkonzept (Phase 4).

Gate pro Schritt: Regeln im `CirculationRuleEvaluator` zentral, Transaktionen mit `lockForUpdate()`, Rollen-Matrix getestet.

## Phase 3 — Schulbetrieb (T2-Nachlauf)

Bewusst aus dem T2-Gate ausgeklammert, im Alltag aber bald nötig:

1. Schuljahreswechsel mit Vorschau, Mapping und Konfliktbehandlung.
2. Produktiver Schulimport.
3. ✔ Verwaltungsmaske für Öffnungszeiten und Schließtage (siehe `docs/AUDIT_AND_CALENDAR.md`).

## Phase 4 — Querschnitt

Die Module `Audit`, `Privacy`, `Reminders` sind im Gerüst vorhanden, aber leer. Ihre Reihenfolge hängt an Phase 2:

1. ✔ `Audit`: Ausleihe, Vormerkungen, Katalogerfassung und Kalender sind protokolliert, Einsicht für die Verwaltung. Offen: weitere Ereignisarten.
2. `Privacy`: Löschkonzept, Aufbewahrungsfristen und Einsicht in Historie.
3. ✔ `Reminders`: E-Mail-Erinnerungen (bald fällig, überfällig, Vormerkung abholbereit), siehe `docs/PORTAL_AND_REMINDERS.md`. Offen: Mahnungen/Gebühren (fachliche Entscheidung), Abmeldung von Erinnerungen.

## Später / bei Bedarf

- MARC21 als weiterer Source-Adapter an der bestehenden Import-Pipeline.
- `Acquisition`, `Collection`, `Content`, `Events`, `Lists`: noch ohne Code. Erst nach Bedarfsklärung mit der Schule anfassen. Lieferanten-, Preis- und Budgetlogik gehört laut Architektur nicht in Catalog.

## Empfehlung

Phase 0 sofort, dann Phase 1 (Schritt 1 und 2 zuerst, weil sie echte Daten absichern) und anschließend Phase 2 in der angegebenen Reihenfolge. Phase 3 lässt sich parallel einplanen, sobald der erste echte Schuljahreswechsel absehbar ist.
