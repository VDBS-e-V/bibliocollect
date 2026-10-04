# Nächste Schritte — BiblioCollect

1. Bootstrap ausführen und ersten grünen CI-Lauf herstellen.
2. Permission- und Navigation-Registry als Foundation-Infrastruktur ergänzen.
3. T2 starten: `Identity`, `Patrons`, `School` mit konkreten Models und Migrations.
4. Erst danach Catalog und Circulation implementieren.

## Definition of Done für Bootstrap

- `php artisan foundation:check` erfolgreich
- `./vendor/bin/pint --test` erfolgreich
- `./vendor/bin/phpstan analyse` erfolgreich
- `./vendor/bin/pest` erfolgreich
- `npm run build` erfolgreich
- `/` antwortet mit HTTP 200
- `/up` antwortet mit HTTP 200
