# Nächste Schritte — BiblioCollect

1. T3 v0.4.6 mit CSV-Upload, Mapping, persistenter Preview, Konfliktprüfung und transaktionaler Übernahme lokal und in GitHub Actions grün bestätigen.
2. Die formatunabhängige Import-Pipeline bei einem späteren MARC21-Schritt über einen weiteren Source-Adapter wiederverwenden; MARC21 selbst ist in v0.4.6 ausdrücklich noch nicht implementiert.
3. Import-Mappings und spätere Quellen müssen weiterhin das offene `role_key`-Modell respektieren; Medientyp und Sprachcode bleiben offene Vokabulare mit schonender Normalisierung.
4. T4 `Circulation` anschließend mit zentralem Regel-Evaluator, Altersprüfung gegen Patron-Geburtsdatum und Edition-Mindestalter, Transaktionen und Row Locks umsetzen.
5. Mit T4 die öffentliche Bestandsanzeige um den echten Ausleihzustand erweitern. Erst dann darf aus „aktives Exemplar“ eine belastbare Aussage wie „derzeit verfügbar“ werden.
6. Danach Vormerkungen titelbezogen aufbauen und die öffentliche Titelansicht um den Vormerkungsstatus ergänzen, ohne Copy-Identitäten öffentlich zu machen.

## Qualitäts-Gate

Vor jedem Commit:

- `php vendor/bin/pint --test`
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `php artisan foundation:check`
- `php vendor/bin/pest`
- `npm run build`

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
- Medientyp-/Sprachfilter und „aktive Exemplare“ prüfen,
- einen Titel ohne Exemplare sowie einen Titel ohne aktive Exemplare prüfen,
- sicherstellen, dass Copy-Barcodes und interne ULIDs nicht in öffentlichen Seiten ausgegeben werden.
