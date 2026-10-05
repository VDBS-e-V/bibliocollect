# Development- und Demo-Seed

Der `DemoSeeder` stellt einen reproduzierbaren Entwicklungsstand für die bereits implementierten BiblioCollect-Domänen bereit. Er ist **nicht für Produktionsdaten** gedacht und wird in `production` nicht über den normalen `DatabaseSeeder` ausgeführt.

## Pflegekonvention

Der Demo-Seed ist ein lebendes Entwicklungsartefakt.

Wenn eine neue Funktion

- einen neuen Domänenzustand,
- eine neue Rolle oder Permission,
- einen neuen Workflow,
- neue Stammdaten,
- neue Suchzustände oder
- eine neue Arbeitsoberfläche

einführt, werden `DemoSeeder` und `DemoSeederTest` im selben Entwicklungsschritt ergänzt. Die Seed-Daten sollen die neue Funktion anschließend ohne zusätzliche Handarbeit testbar machen.

## Datenbank neu aufbauen

Achtung: `migrate:fresh` löscht die lokale Datenbank vollständig.

```text
php artisan migrate:fresh --seed
```

Ein erneutes `php artisan db:seed` ist ebenfalls möglich. Der Seed ist für seine eigenen Demo-Datensätze idempotent aufgebaut.

## Demo-Passwort

Alle aktiv nutzbaren Demo-Onlinekonten verwenden:

```text
Bibliothek2026!
```

## Onlinekonten und Rollen

| E-Mail | Rollen / Zweck |
| --- | --- |
| `student@demo.bibliocollect.test` | Schüler:in, mit aktivem Ausleihkonto verknüpft |
| `teacher@demo.bibliocollect.test` | Lehrkraft, mit aktivem Ausleihkonto verknüpft |
| `ag-basic@demo.bibliocollect.test` | Schüler:in + Schüler-AG Basis, inklusive `circulation.manage` |
| `ag-extended@demo.bibliocollect.test` | Schüler:in + Schüler-AG Erweitert, inklusive `catalog.manage` und `circulation.manage`, ausdrücklich ohne `catalog.import` |
| `staff@demo.bibliocollect.test` | Mitarbeiter:in für Patron-, Katalog-, Import- und Circulation-Workflows |
| `management@demo.bibliocollect.test` | Verwaltung für Schule, Rollen, fachliche Verwaltung, Katalogimport und Circulation |
| `technik@demo.bibliocollect.test` | technische Administration ohne Patron-, Katalog-, Import- oder Circulation-Rechte |
| `departed@demo.bibliocollect.test` | deaktiviertes Konto eines dauerhaft ausgeschiedenen Patrons |

Damit sind alle derzeit definierten Rollen sowie die kombinierbaren Schüler-/AG-Rollen abgedeckt.

## Schule

Der Seed enthält drei Schuljahre:

- `2025/26` als abgeschlossenes/inaktives Vorjahr,
- `2026/27` als aktives Schuljahr,
- `2027/28` als vorbereitetes Folgejahr.

Je Schuljahr werden sechs Klassen angelegt. Die Klassenstufen bilden einen einfachen Übergang vom Vorjahr über das aktive Jahr zum Folgejahr ab.

Zusätzlich werden Öffnungszeiten für alle sieben Wochentage angelegt:

- Montag und Dienstag: 09:00–15:00,
- Mittwoch: 09:00–13:00,
- Donnerstag: 09:00–15:00,
- Freitag: 09:00–13:00,
- Samstag und Sonntag: geschlossen.

Mehrere Demo-Schließtage zeigen Ferien- und Feiertagszustände. Der Seed sucht vorhandene Schließtage über `whereDate`, damit ein erneutes Seeden unter SQLite und MySQL/MariaDB denselben Datensatz aktualisiert statt einen Unique-Konflikt auszulösen.

## Ausleihkonten

Die Bibliotheksnummern sind absichtlich stabil:

| Bibliotheksnr. | Zustand |
| --- | --- |
| `S-10001` | aktive Schülerin mit verknüpftem Onlinekonto |
| `S-10002` | aktiver Schüler mit AG-Basis-Onlinekonto |
| `S-10003` | aktive, aktuell gesperrte Schülerin mit AG-Erweitert-Konto |
| `S-10004` | aktiver, noch nicht verknüpfter Schüler für den Linkcode-Workflow |
| `S-10005` | aktiver Schüler mit historischer Sperre und anschließender Entsperrung |
| `S-10006` | dauerhaft ausgeschiedene Schülerin mit deaktiviertem Onlinekonto |
| `L-20001` | aktive Lehrkraft |
| `M-30001` | aktives Mitarbeiter-Ausleihkonto |
| `M-30002` | archiviertes Mitarbeiter-Ausleihkonto |

Block-/Unblock- und Austrittsereignisse werden über die vorhandenen Domain-Actions erzeugt, damit Audit-Zustände realistisch bleiben.

## Demo-Linkcode

Für `S-10004` / Noah Linkcode wird ein offener Demo-Linkcode vorbereitet:

```text
D3MZ2-26ABC
```

Die Datenbank speichert davon nur den HMAC-Fingerprint. Der Klartext steht ausschließlich in dieser Development-Dokumentation und in der Konsolenausgabe des Seeders.

Zusätzlich gibt es einen bereits verwendeten Tokenzustand und einen widerrufenen Tokenzustand für die vorhandenen Patron-Workflows.

## Katalog

Der Seed enthält neun Titel und zehn Ausgaben. Darunter:

- `Momo`
- `Die unendliche Geschichte`
- `Tschick`
- `Krabat`
- `Der kleine Prinz`
- `Matilda`
- `Harry Potter und der Stein der Weisen`
- `The Giver`
- `Die Welle`

`Michael Ende` wird absichtlich an mehreren Titeln wiederverwendet. Damit lässt sich der Hinweis beim Bearbeiten gemeinsam genutzter `Contributor`-Datensätze testen.

Die Beispieldaten enthalten außerdem unterschiedliche Verantwortlichkeitsrollen:

- `author`
- `illustrator`
- `translator`

`Momo` besitzt zusätzlich zwei unterschiedliche Ausgaben, darunter eine Hörbuchausgabe. Damit sind `Title` und `Edition` im Seed sichtbar getrennt.

Für die öffentliche Katalogsuche ergänzt `PublicCatalogDemoSeeder` zwei gezielte Suchzustände:

- `The Giver` besitzt eine englischsprachige Buchausgabe (`language_code = en`) mit aktivem Exemplar am Standort `EN 7 LOWR`. Damit können Sprachfilter, unbekontoabhängige Recherche und aktive Bestandsanzeige getestet werden.
- `Die Welle` besitzt eine deutschsprachige Ausgabe, aber bewusst noch kein physisches Exemplar. Damit lässt sich der öffentliche Zustand „Noch kein Exemplarbestand“ testen.

`PublicCatalogDemoSeeder` wird vom normalen `DemoSeeder` aufgerufen, ist idempotent und verweigert wie der Hauptseeder die direkte Ausführung in `production`.

## Katalogimport

Ab v0.4.6 ruft der normale `DatabaseSeeder` nach dem bestehenden `DemoSeeder` zusätzlich `CatalogImportDemoSeeder` auf. Die Fixture `database/seeders/fixtures/catalog-import-demo.csv` enthält zwei Zeilen für `Der Hobbit` mit derselben normalisierten ISBN und zwei unterschiedlichen Demo-Barcodes.

Der Import-Demo-Seed legt **keine** neuen Bibliotheksdatensätze an. Er erzeugt einmalig einen persistenten `CatalogImportBatch`, berechnet dessen Preview und lässt ihn im Zustand `ready`. Damit können Mapping, Reload, Normalisierung, ISBN-Gruppierung und die explizite Übernahme direkt in der POS-Oberfläche demonstriert werden.

Die Fixture deckt unter anderem ab:

- ISBN-Normalisierung aus `ISBN 978-3-423-21412-6`,
- `Buch` → `book`,
- `DEU` → `de`,
- `Autor` → `author`,
- `Aktiv` → `active` und `beschädigt` → `damaged`,
- zwei unterschiedliche Barcodes bei derselben ISBN → eine geplante Edition mit zwei Copies.

`CatalogImportDemoSeeder` verweigert die direkte Ausführung in `production`. Der normale `DatabaseSeeder` beendet sich dort weiterhin vor sämtlichen Demo-Seedern. Ein erneuter Seed-Aufruf erzeugt für die Fixture keinen zweiten Batch.


## Circulation

Ab T4 v0.5.0 ruft der normale `DatabaseSeeder` nach den bestehenden Demo-Seedern zusätzlich `CirculationDemoSeeder` auf.

Der Seeder ergänzt zwei feste Workflow-Zustände:

- `S-10001` / Lina Berger hat `BC-MOMO-001` seit dem 01.10.2026 offen ausgeliehen; Fälligkeit ist der 15.10.2026.
- `L-20001` / Anna Lehrkraft besitzt eine bereits am 22.09.2026 zurückgegebene historische Ausleihe von `BC-PRINZ-001`.

Damit lassen sich offene Ausleihe, Rückgabe und die bewusste Nichtanzeige abgeschlossener Lesehistorie im Patron-Arbeitsbereich direkt prüfen. `CirculationDemoSeeder` ist idempotent und verweigert die direkte Ausführung in `production`.

## Exemplare

Es werden 15 physische Exemplare mit stabilen Demo-Barcodes und Regalstandorten angelegt.

Die vorhandenen Exemplarzustände sind vollständig vertreten:

- `active`
- `damaged`
- `lost`
- `withdrawn`

Diese Datensätze sind die feste Testbasis für die ab T3 v0.4.4 vorhandene Exemplarpflege. Insbesondere `BC-MOMO-002`, `BC-UNEND-002` und `BC-KRAB-002` decken die nicht-aktiven Statuswerte samt Regalstandorten ab.

## Automatischer Seed-Test

`tests/Feature/DemoSeederTest.php` sowie `tests/Feature/CirculationDemoSeederTest.php` prüfen unter anderem:

- alle Schuljahre, Klassen, Öffnungszeiten und Schließtage,
- alle Demo-Rollen und Berechtigungsgrenzen,
- aktive, gesperrte, entsperrte, ausgeschiedene und archivierte Patrons,
- deaktivierte Onlinekonten,
- offene, verwendete und widerrufene Patron-Linktokens,
- Titel, Ausgaben, Verantwortliche und Exemplare,
- alle vorhandenen Copy-Statuswerte sowie konkrete Demo-Barcodes und Regalstandorte,
- die Wiederverwendung eines Contributors,
- die `catalog.import`-Grenze zwischen Mitarbeiter:innen/Verwaltung und AG Erweitert/Technik,
- die `circulation.manage`-Grenze und die beiden reproduzierbaren Demo-Loans,
- den persistenten, konfliktfreien Demo-Import-Batch samt Idempotenz,
- den englischsprachigen öffentlichen Suchzustand `The Giver`,
- den Titel `Die Welle` ohne physische Exemplare,
- Idempotenz bei erneutem Seed-Aufruf.
