# Nächste Schritte — BiblioCollect

1. T3 v0.4.0 Catalog-Grundmodell lokal und in GitHub Actions grün bestätigen.
2. T3 in kleinen Schritten erweitern: bibliografische Felder und titelbasierte Queries, danach interne Katalogpflege.
3. Öffentliche Katalogsuche auf `Title`/`Edition` aufbauen; physische Verfügbarkeit bleibt `Copy`-bezogen.
4. CSV/MARC21-Import erst danach mit Preview, Normalisierung und Konfliktbehandlung ergänzen.
5. T4 `Circulation` anschließend mit zentralem Regel-Evaluator, Altersprüfung gegen Patron-Geburtsdatum und Edition-Mindestalter, Transaktionen und Row Locks umsetzen.

## Qualitäts-Gate

Vor jedem Commit:

- `php vendor/bin/pint --test`
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `php artisan foundation:check`
- `php vendor/bin/pest`
- `npm run build`
