# Nächste Schritte — BiblioCollect

1. Den eingeschobenen Legacy-Katalogmigrationsblock lokal grün bestätigen und anschließend den realen Altbestand über `catalog:legacy:analyze` vorprüfen.
2. Nach Lieferung der drei phpMyAdmin-JSON-Exporte (`mediaList`, `mediaTopicList`, `mediaSignatures`) zuerst den Analysebericht prüfen und erst danach den transaktionalen Console-Import ausführen.
3. Danach die öffentliche Bestandsanzeige um den echten Ausleihzustand erweitern. Erst dann darf aus „aktives Exemplar“ eine belastbare Aussage wie „derzeit verfügbar“ werden.
4. Anschließend Verlängerungen mit expliziten Regeln auf dem bestehenden Loan-Modell ergänzen.
5. Danach Vormerkungen titelbezogen aufbauen und die öffentliche Titelansicht um den Vormerkungsstatus ergänzen, ohne Copy-Identitäten öffentlich zu machen.
6. Die formatunabhängige Import-Pipeline bei einem späteren MARC21-Schritt über einen weiteren Source-Adapter wiederverwenden; MARC21 selbst bleibt außerhalb von T4. Die jetzt eingeführten DNB-/GND-/Quellenfelder bilden dafür bereits eine fachliche Zielstruktur.
7. Import-Mappings und spätere Quellen müssen weiterhin das offene `role_key`-Modell respektieren; Medientyp und Sprachcode bleiben offene Vokabulare mit schonender Normalisierung.

## Qualitäts-Gate

Vor jedem Commit:

- `php vendor/bin/pint --test`
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `php artisan foundation:check`
- `php vendor/bin/pest`
- `npm run build`
- `git diff --check`

Zusätzlich für den Legacy-Katalogimport:

- phpMyAdmin-JSON-Envelope und direkte JSON-Row-Liste lesen können,
- `catalog:legacy:analyze` nachweislich ohne Katalog-Writes ausführen,
- `inventory_number` als Barcode übernehmen und katalogweite Barcode-Konflikte vor dem ersten Write blockieren,
- mehrere Exemplare derselben bibliografischen Ausgabe auf eine `Edition` gruppieren,
- gleiche ISBN bei widersprüchlichen Titeln **nicht** zusammenführen,
- DNB-RCN/Permalink, GND, Reihe, Verantwortlichkeitsangabe, Erscheinungsort, Auflage, Identifikatoren, Umfang, Inhaltsangabe, Schlagwörter, Zielgruppe und Altersangaben erhalten,
- `mediaTopicList` hierarchisch sowie `mediaSignatures.topic_ids` als strukturierte Zuordnung übernehmen,
- `is_available`, `loan_counter` und `last_loan_date` ausschließlich als Legacy-Historie erhalten und niemals aktuelle Circulation daraus ableiten,
- `0000`, `0000-00-00` und ungültige optionale Legacy-Werte schonend als Warnung behandeln,
- mögliche Zeichensatzartefakte melden, aber nicht erraten/korrigieren,
- Wiederholung idempotent über Legacy-IDs und Editions-Gruppierungsschlüssel halten,
- den gesamten Write als eine Transaktion ausführen,
- Demo-Seed und öffentliche Metadatenanzeige regressionssicher prüfen.

Zusätzlich für Änderungen an Circulation:

- `circulation.manage` für Schüler-AG Basis/Erweitert, Mitarbeiter:innen und Verwaltung prüfen,
- technische Administration, Schüler:innen und Lehrkräfte vom POS-Circulation-Workflow fernhalten,
- gesperrte, ausgeschiedene und archivierte Patrons blockieren,
- nicht aktive Copies blockieren,
- doppelte offene Ausleihe desselben Exemplars blockieren,
- Altersprüfung gegen Patron-Geburtsdatum und `Edition::minimum_age` prüfen,
- Fälligkeitsdatum bei Schließtagen auf den nächsten Öffnungstag verschieben,
- Ausleihe und Rückgabe transaktional mit `lockForUpdate()` absichern,
- Rückgabe ohne automatische Änderung des CopyStatus prüfen,
- nach Rückgabe eine erneute Ausleihe desselben Exemplars erlauben,
- im Patron-Arbeitsbereich ausschließlich offene Ausleihen anzeigen,
- Seed-Idempotenz und Migration-Rollback prüfen.

Zusätzlich für Änderungen am Katalogimport:

- `catalog.import` ausschließlich für Mitarbeiter:innen und Verwaltung prüfen,
- Upload und Header-Erkennung mit mindestens Komma/Semikolon testen,
- Preview auf Null-Schreibzugriffe gegen `Title`, `Edition`, `Contributor`, `TitleContribution` und `Copy` prüfen,
- ISBN-, Sprach-, Medientyp- und CopyStatus-Normalisierung prüfen,
- doppelte und bereits vorhandene Barcodes als blockierende Konflikte prüfen,
- eindeutige ISBN-Wiederverwendung ohne Überschreiben bestehender Stammdaten prüfen,
- mehrere Zeilen derselben ISBN auf eine Edition mit mehreren Copies prüfen,
- Commit nur nach expliziter Bestätigung und vollständig transaktional prüfen,
- Reload eines Preview-Batches sowie den Importbericht prüfen,
- technische Administration und Schüler-AG Erweitert vom Import fernhalten.

Zusätzlich für Änderungen am öffentlichen Katalog:

- anonyme Suche ohne Login testen,
- mindestens Titel-, Contributor- und ISBN-Suche prüfen,
- erweiterte bibliografische Suchfelder (z. B. Reihe, GND, DNB-RCN, Schlagwörter) prüfen,
- Medientyp-/Sprachfilter und Bestandsfilter prüfen,
- einen Titel ohne Exemplare sowie einen Titel ohne aktive Exemplare prüfen,
- sicherstellen, dass Copy-Barcodes, interne ULIDs, interne Notizen, Erwerbungspreise und Legacy-Historie nicht in öffentlichen Seiten ausgegeben werden.
