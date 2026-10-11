# BiblioCollect – Redesign-Integration

## Ziel und Rahmen

Die genehmigte VDBS-Marke bleibt die einzige Designgrundlage. Inspiration durch ZLB Berlin und Oodi Helsinki betrifft Informationsarchitektur, Weißraum, redaktionelle Präsentation und Suchorientierung – nicht deren Corporate Design.

BiblioCollect läuft im Schulbetrieb. Darum erfolgen Änderungen klein, testbar und ausschließlich in Feature-Branches mit Pull Request und Review. Die funktionierende Anwendungslogik aus Catalog, Circulation, Identity und den anderen Modulen bleibt unverändert.

## Erste Integrationsstufe – öffentliche Startseite

Neue wiederverwendbare Blade-Komponenten:

- `resources/views/components/library/catalog-search.blade.php` – barrierearm beschriftete Katalogsuche, GET auf die bestehende Route, konfigurierbare ID, Aktion, Query und Platzhalter.
- `resources/views/components/library/quick-action.blade.php` – Linkkarte mit Titel, Kurzbeschreibung und dekorativem Icon; Zugriffsrechte und Zielroute legt jeweils die aufrufende Seite fest.

Neuer CSS-Baustein:

- `resources/css/patterns/public-home-redesign.css` – Markenbühne, Suchfeld, schnelle Aktionen und responsive Layouts; importiert über `resources/css/patterns/index.css`.

Die Blade-Startseite `resources/views/pages/welcome.blade.php` nutzt diese Komponenten. **Unverändert** bleiben Katalog- und Kontorouten, Empfehlungen, Neuerscheinungen, Themen, Öffnungszeiten, Schließtage, Leihregeln und Portalzugriffsprüfung. Die echten VDBS-Grafiken unter `public/brand/vdbs/` werden weiterverwendet; es werden keine Demo-Cover oder Demo-Ausleihen produktiv eingebaut.

## Vorgeschlagene weitere Schritte

| Stufe | Vorlagen | Reale Oberflächen | Hinweise |
|---|---|---|---|
| 2 | Suche & Filter, Ergebnisliste | `/katalog`, `/katalog/erweiterte-suche` | Pagination, Suchvorschläge, Filter und Datenschutz erhalten |
| 3 | Detailansicht | `/katalog/titel/{titleId}` | Verfügbarkeit aus der bestehenden Query, keine Barcodes/ULIDs öffentlich |
| 4 | Persönliche Übersicht | `/konto`, Merkliste, Leselisten, Buchwünsche | Identität und Berechtigungen nicht im Frontend nachbauen |
| 5 | Arbeitstisch & Assistenten | Ausleihe/Rückgabe, Erfassung, Import, Inventur | Scannerfokus, Validierung, Transaktionen und Zwischenschritte erhalten |
| 6 | Datentabelle, Formulare, Dashboard, Print | Verwaltung, Statistik, Regale, Nutzer, Etiketten | Datendichte und Druckformate funktionsspezifisch halten |

Die neuen Library-Komponenten ergänzen zunächst `resources/views/components/ui/`. Wenn mehrere Seiten dieselbe Kombination benötigen, kann diese in `resources/views/patterns/` und schließlich `resources/views/templates/` überführt werden (Token → Component → Pattern → Template → Page).

## Prüfungen vor Merge

- `composer quality` (Pint, PHPStan, foundation:check, Pest) und `npm run build`.
- `php artisan test --filter=PublicHomeRedesignTest` oder `php vendor/bin/pest tests/Feature/PublicHomeRedesignTest.php`.
- Regressionen: `StartPagesTest`, `HomeShowcaseTest`, `AccessibilityTest`, `PublicCatalogWorkflowTest`.
- Browserprüfung bei 320, 375, 768 und Desktop; Hell-/Dunkelmodus, Tastatur und Screenreader.
- Echte, datenschutzkonforme Bibliotheksinhalte erst auf dem vorgesehenen Testsystem ansehen; keine realen Bestands- oder Personendaten ins Repository einspielen.

## Architektur- und Betriebsgrenzen

- Keine Änderungen an Datenbankmigrationen, Berechtigungen, Geschäftsregeln oder produktiven Datensätzen.
- Kein direktes Deployment durch diesen Branch; Release-Prozess gemäß `AGENTS.md`.
- Formulare nutzen weiterhin die existierenden Laravel-Routen, CSRF- und Validierungsmechanismen.
