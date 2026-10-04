# Nächste Schritte — BiblioCollect

1. T3 v0.4.2 mit interner Titel- und Editionspflege lokal und in GitHub Actions grün bestätigen.
2. Danach Verantwortliche in der Katalogpflege ergänzen und die vorhandenen `Contributor`-/`TitleContribution`-Strukturen bedienbar machen.
3. Anschließend Exemplarpflege für Barcode, Standort und Exemplarstatus ergänzen.
4. Öffentliche Katalogsuche auf `SearchCatalogTitlesQuery` aufbauen; physische Verfügbarkeit bleibt `Copy`-bezogen.
5. CSV/MARC21-Import erst danach mit Preview, Normalisierung und Konfliktbehandlung ergänzen.
6. T4 `Circulation` anschließend mit zentralem Regel-Evaluator, Altersprüfung gegen Patron-Geburtsdatum und Edition-Mindestalter, Transaktionen und Row Locks umsetzen.

## Qualitäts-Gate

Vor jedem Commit:

- `php vendor/bin/pint --test`
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `php artisan foundation:check`
- `php vendor/bin/pest`
- `npm run build`
