# Öffentlicher Katalog, erweiterte Suche und Cover-Cache

Dieser Zwischenblock überarbeitet die Katalogrecherche für die Gemeinschaftsschule. Die öffentliche Oberfläche bleibt bewusst einfacher als die interne Katalogpflege, nutzt aber dieselbe Catalog-Query und dieselben bibliografischen Daten.

## Öffentliche Recherche

`/katalog` ist weiterhin anonym erreichbar. Die Startsuche ist bewusst niedrigschwellig und kombiniert:

- freie Suche über Titel, Verantwortliche, Identifikatoren und ausgewählte bibliografische Felder,
- schnelle Filter für Medientyp, Sprache und katalogseitig aktive Exemplare,
- Sortierung nach Titel, Erscheinungsjahr oder Erfassungszeitpunkt,
- wählbare Seitengrößen mit 10, 20, 50 oder 100 Treffern und direkte Seitennavigation,
- eine coverorientierte Trefferliste mit deutlich sichtbarem Titel, Verantwortlichen und Bestand,
- Themen/Klassifikation als zurückhaltende Zusatzinformation.

Copy-Barcodes, interne ULIDs, Erwerbungsdaten, interne Notizen und Legacy-Historie werden öffentlich weiterhin nicht ausgegeben.

## Öffentliche erweiterte Suche

`/katalog/erweiterte-suche` bietet eine schulgerechte, reduzierte Advanced Search. Kombinierbar sind:

- freie Suche,
- Titel,
- Autor:in / Verantwortliche,
- Schlagwort,
- ISBN bzw. bibliografische Kennung,
- Verlag,
- Thema bzw. lokale Klassifikation,
- Erscheinungsjahr von/bis,
- Medientyp,
- Sprache,
- katalogseitig aktiver Bestand.

Die Ergebnisse erscheinen wieder in der normalen Katalog-Trefferliste. Aktive erweiterte Kriterien werden dort kenntlich gemacht und können erneut bearbeitet werden.

## Interne Recherche

Die Katalogpflege unter `/betrieb/katalog` verwendet eine deutlich ausführlichere Suchmaske für `catalog.manage`. Zusätzlich zur öffentlichen Auswahl können intern unter anderem Erscheinungsort, Reihe, lokale Klassifikation, Zielgruppe und DNB-/Quell-ID gefiltert werden. Auch dort stehen 10, 20, 50 oder 100 Treffer pro Seite sowie eine direkte Seitenauswahl zur Verfügung.

Die interne Suche schafft keine neue Berechtigung. Sie bleibt vollständig hinter `catalog.manage`; technische Administration erhält dadurch keinen fachlichen Katalogzugriff.

## Cover-Grundsatz

Eine Webanfrage des öffentlichen Katalogs lädt **niemals** ein Cover von einem externen Dienst. Die Oberfläche verwendet ausschließlich:

1. eine lokal auf dem konfigurierten Cover-Disk gespeicherte Datei oder
2. den gebündelten Platzhalter `public/brand/vdbs/catalog-cover-placeholder.png`.

Damit hängen Antwortzeit und Verfügbarkeit der Suche nicht von einer Cover-API ab.

## Persistierte Cover-Metadaten

`catalog_editions` erhält:

- `cover_path` – relativer lokaler Dateipfad,
- `cover_source` – technische Herkunft des Bildes,
- `cover_source_reference` – optionale Referenz beim Quellsystem,
- `cover_status` – z. B. `pending`, `ready`, `missing`, `error`,
- `cover_checked_at`,
- `cover_fetched_at`.

Die öffentliche Oberfläche gibt `cover_source_reference` nicht aus.

## Hintergrundprozess

`CatalogCoverProvider` ist die formatunabhängige Schnittstelle für eine spätere konkrete Cover-API. Der aktuelle Standard ist absichtlich `NullCatalogCoverProvider`; es findet also noch kein externer Request statt.

Sobald ein konkreter Provider gebunden ist, kann

```text
php artisan catalog:covers:queue --limit=100
```

Editionen mit ISBN oder Quell-ID als `RefreshEditionCoverJob` in die Laravel-Queue stellen. Der Job ruft den Provider im Hintergrund auf, akzeptiert nur JPEG/PNG/WebP innerhalb des Größenlimits und speichert das Bild auf dem lokalen Cover-Disk. Erst danach wird `cover_path` auf der Edition gesetzt.

Für einen produktiven Betrieb gehören daher später zusammen:

- ein konkreter API-Adapter für `CatalogCoverProvider`,
- ein laufender Laravel Queue Worker,
- optional ein Scheduler-Eintrag, der `catalog:covers:queue` regelmäßig ausführt,
- `php artisan storage:link`, wenn der Standard-Disk `public` verwendet wird.

## Demo und Tests

`CatalogCoverDemoSeeder` markiert die Demo-Ausgabe von `The Giver` als `pending`, lädt aber ausdrücklich nichts aus dem Internet. Dadurch bleibt der Development-Seed offline und reproduzierbar.

Regressionstests prüfen unter anderem:

- öffentliche und interne Advanced Search,
- kombinierte Edition-Filter,
- Topic-/Klassifikationsfilter,
- lokale Cover-URLs und Platzhalter,
- lokales Speichern eines Provider-Covers,
- Queue statt synchronem Download,
- Rollback der Cover-Migration,
- Seed-Idempotenz.
