# Entwicklungsplan — BiblioCollect

Stand: nach T4 v0.5.0, Legacy-Katalogmigration, Katalogrecherche, Cover-Cache und Seitennavigation. Die Reihenfolge folgt `docs/NEXT_STEPS.md`, `docs/T4_CIRCULATION.md` ("Bewusst später") und den leeren Modulen im Foundation-Gerüst.

## Phase 0 — Release-Hygiene (sofort)

Der aktuelle Arbeitsbaum auf `feature/public-catalog-redesign` enthält drei Blöcke ungetrennt, ist nicht committet und grün (158 Tests).

1. Arbeitsbaum in logische Commits teilen: Legacy-Katalog, Recherche/Cover, Seitennavigation.
2. Die ca. 25 `*.patch`-Dateien im Repo-Root sind Übergabeartefakte. Sie gehören nicht ins Repo (`.gitignore` oder in ein eigenes Verzeichnis außerhalb).
3. Branch mergen, Tag setzen (z. B. v0.5.1).

## Phase 1 — Realdaten absichern (Katalog abschließen)

1. `catalog:legacy:import` auf dem realen Altbestand ausführen, danach `catalog:legacy:audit-quality` und den JSON-Bericht prüfen.
2. Schreibfreie DNB-Neuanreicherung für Datensätze mit belastbarer DNB-Referenz: Diff vor `--apply`, kein stilles Überschreiben sauberer lokaler Werte.
3. Zeichensatzverluste (`Mu?nchen`) bleiben manuelle Prüffälle. Dafür eine Prüfliste in der POS-Oberfläche anbieten.
4. Konkreten `CatalogCoverProvider` binden. Betrieb: Queue Worker, Scheduler für `catalog:covers:queue`, `storage:link`.

Gate: Audit ohne ungeklärte Contributor-Lücken, Cover-Job idempotent, kein externer Request in Webrequests.

## Phase 2 — T4 Circulation vervollständigen (Kernnutzen)

Reihenfolge ist fachlich bedingt:

1. **Öffentliche Verfügbarkeit** auf Basis offener Loans. Erst danach darf "aktiv" zu "derzeit verfügbar" werden. Sie bleibt titelbezogen, ohne Barcodes oder ULIDs.
2. **Verlängerungen** mit expliziten Regeln auf dem bestehenden `Loan`-Modell (maximale Anzahl, Sperre bei Vormerkung, Schließtage über `SchoolCalendarService`).
3. **Vormerkungen**, titelbezogen, mit Status in der öffentlichen Titelansicht. Pickups folgen darauf.
4. **Mahnungen und Gebühren** nur, wenn die Schule sie fachlich will. Vorher als Entscheidung klären.
5. **Differenzierte Einsicht in Ausleihhistorie** zusammen mit dem Datenschutzkonzept (Phase 4).

Gate pro Schritt: Regeln im `CirculationRuleEvaluator` zentral, Transaktionen mit `lockForUpdate()`, Rollen-Matrix getestet.

## Phase 3 — Schulbetrieb (T2-Nachlauf)

Bewusst aus dem T2-Gate ausgeklammert, im Alltag aber bald nötig:

1. Schuljahreswechsel mit Vorschau, Mapping und Konfliktbehandlung.
2. Produktiver Schulimport.
3. Verwaltungsmaske für Öffnungszeiten und Schließtage (Fristen hängen daran).

## Phase 4 — Querschnitt

Die Module `Audit`, `Privacy`, `Reminders` sind im Gerüst vorhanden, aber leer. Ihre Reihenfolge hängt an Phase 2:

1. `Audit`: nachvollziehbare Ausleih-, Sperr- und Katalogänderungen.
2. `Privacy`: Löschkonzept, Aufbewahrungsfristen und Einsicht in Historie.
3. `Reminders`: Benachrichtigungen (Fälligkeit, Vormerkung bereit) auf Basis der Phase-2-Ereignisse.

## Später / bei Bedarf

- MARC21 als weiterer Source-Adapter an der bestehenden Import-Pipeline.
- `Acquisition`, `Collection`, `Content`, `Events`, `Lists`: noch ohne Code. Erst nach Bedarfsklärung mit der Schule anfassen. Lieferanten-, Preis- und Budgetlogik gehört laut Architektur nicht in Catalog.

## Empfehlung

Phase 0 sofort, dann Phase 1 (Schritt 1 und 2 zuerst, weil sie echte Daten absichern) und anschließend Phase 2 in der angegebenen Reihenfolge. Phase 3 lässt sich parallel einplanen, sobald der erste echte Schuljahreswechsel absehbar ist.
