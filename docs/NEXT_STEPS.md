# Nächste Schritte — BiblioCollect

1. T3 v0.4.4 mit Seed-Fix und Exemplarpflege lokal und in GitHub Actions grün bestätigen.
2. Öffentliche Katalogsuche auf `SearchCatalogTitlesQuery` aufbauen; physische Verfügbarkeit bleibt `Copy`-bezogen.
3. CSV/MARC21-Import danach mit Preview, Normalisierung und Konfliktbehandlung ergänzen.
4. T4 `Circulation` anschließend mit zentralem Regel-Evaluator, Altersprüfung gegen Patron-Geburtsdatum und Edition-Mindestalter, Transaktionen und Row Locks umsetzen.
5. Mit T4 die Wechselwirkungen zwischen Ausleihzustand und den Katalogstatuswerten `damaged`, `lost` und `withdrawn` explizit absichern.

## Qualitäts-Gate

Vor jedem Commit:

- `php vendor/bin/pint --test`
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `php artisan foundation:check`
- `php vendor/bin/pest`
- `npm run build`
