# Nächste Schritte — BiblioCollect

1. T3 v0.4.5 mit öffentlicher Katalogsuche, Filterung, Titelansicht und Bestandszusammenfassung lokal und in GitHub Actions grün bestätigen.
2. Danach CSV/MARC21-Import mit Preview, Normalisierung, Feldmapping, Dubletten-/Konflikterkennung und kontrollierter Übernahme ergänzen.
3. Importierte Verantwortlichkeiten müssen weiterhin das offene `role_key`-Modell respektieren; Medientyp und Sprachcode bleiben bis zu einem expliziten Mapping offen.
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

Zusätzlich für Änderungen am öffentlichen Katalog:

- anonyme Suche ohne Login testen,
- mindestens Titel-, Contributor- und ISBN-Suche prüfen,
- Medientyp-/Sprachfilter und „aktive Exemplare“ prüfen,
- einen Titel ohne Exemplare sowie einen Titel ohne aktive Exemplare prüfen,
- sicherstellen, dass Copy-Barcodes und interne ULIDs nicht in öffentlichen Seiten ausgegeben werden.
