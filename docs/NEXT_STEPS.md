# Nächste Schritte — BiblioCollect

1. T3 v0.4.1 mit bibliografischen Metadaten, strukturierten Verantwortlichen und titelbasierter Query lokal und in GitHub Actions grün bestätigen.
2. Danach interne Katalogpflege mit eigenen Actions, Validierung und fein geschnittenen Catalog-Permissions umsetzen.
3. Öffentliche Katalogsuche auf der titelbasierten Query aufbauen; physische Verfügbarkeit bleibt `Copy`-bezogen.
4. CSV/MARC21-Import erst danach mit Preview, Normalisierung und Konfliktbehandlung ergänzen.
5. T4 `Circulation` anschließend mit zentralem Regel-Evaluator, Altersprüfung gegen Patron-Geburtsdatum und Edition-Mindestalter, Transaktionen und Row Locks umsetzen.

## Qualitäts-Gate

Vor jedem Commit:

- `php vendor/bin/pint --test`
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `php artisan foundation:check`
- `php vendor/bin/pest`
- `npm run build`
