# Nächste Schritte — BiblioCollect

1. T2 v0.3.4 lokal und in GitHub Actions grün bestätigen.
2. T2 fachlich abschließen: Entscheidung, ob der vollständige Schuljahres-Massenwechsel noch vor T3 umgesetzt wird oder als Verwaltungsworkflow nachgezogen wird.
3. T3 `Catalog` beginnen: Title/Edition und Copy strikt trennen, ULIDs intern, sichtbare Barcodes nicht als Primärschlüssel verwenden.
4. Danach T4 `Circulation` mit zentralem `LoanRuleEvaluator`, Transaktionen und Row Locks umsetzen.

## Qualitäts-Gate

Vor jedem Commit:

- `php vendor/bin/pint --test`
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `php artisan foundation:check`
- `php vendor/bin/pest`
- `npm run build`
