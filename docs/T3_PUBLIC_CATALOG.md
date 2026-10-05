# T3 v0.4.5 – Öffentlicher Katalog

Dieses Dokument beschreibt die öffentliche Katalogstrecke von BiblioCollect ab T3 v0.4.5. Es ergänzt `docs/T3_CATALOG.md` und konzentriert sich auf Suchverhalten, Darstellung, Datenschutz und die fachliche Grenze zur späteren Ausleihe.

## Ziel

Der öffentliche Katalog soll ohne Anmeldung nutzbar sein und drei typische Wege unterstützen:

1. gezielte Suche nach einem bekannten Titel, einer verantwortlichen Person, ISBN oder Ausgabeangabe,
2. freies Stöbern im gesamten katalogisierten Bestand,
3. Eingrenzung nach Medientyp, Sprache und vorhandenem aktivem Exemplarbestand.

Die Oberfläche darf dabei keine Verfügbarkeit vortäuschen, die BiblioCollect ohne Circulation noch nicht kennt.

## Routen

### `GET /katalog`

Öffentliche Trefferliste mit:

- optionalem Suchbegriff `q`,
- optionalem `media_type`,
- optionalem `language_code`,
- optionalem `active_only=1`,
- `sort=title|recent`,
- serverseitiger Pagination.

Ein leerer Request zeigt den durchstöberbaren Katalog. Es ist kein Login erforderlich.

### `GET /katalog/titel/{titleId}`

Öffentliche Detailansicht eines Titels mit:

- Haupttitel und Untertitel,
- Verantwortlichen und Rollen,
- allen erfassten Ausgaben,
- ISBN, Verlag und Erscheinungsjahr,
- Medientyp und Sprache,
- Altersangaben,
- aggregiertem Copy-Status,
- Regalstandorten aktiver Copies.

Nicht angezeigt werden:

- Copy-Barcodes,
- interne Copy-ULIDs,
- interne Edition-ULIDs als fachliche Information,
- operative Patron- oder Ausleihdaten.

## Suchvertrag

Die bestehende `SearchCatalogTitlesQuery::execute(string $term, int $limit = 25)` bleibt erhalten. Dadurch funktionieren bisherige interne Aufrufer unverändert.

Neu ist `SearchCatalogTitlesQuery::paginate(CatalogSearchCriteria $criteria)`.

### Suchbare Felder

Ein Suchbegriff wird tokenweise auf folgende Daten angewendet:

- `Title.preferred_title`,
- `Title.subtitle`,
- `Title.sort_title`,
- `Contributor.display_name`,
- `Contributor.sort_name`,
- `Edition.isbn`,
- `Edition.publisher_name`,
- `Edition.media_type`,
- `Edition.language_code`.

### Bewusst nicht suchbar

`Copy.barcode` ist absichtlich ausgeschlossen.

Begründung:

- Ein Barcode adressiert ein physisches Exemplar, nicht den bibliografischen Titel.
- Öffentliche Recherche soll titelbezogen bleiben.
- Barcode-Suche gehört in interne operative Workflows wie Ausleihe, Rückgabe, Inventur oder Exemplarpflege.
- Ein späterer Barcode-Scanner darf deshalb einen eigenen Query-Pfad erhalten, ohne die öffentliche Recherche umzudeuten.

## Eingaben und Wildcards

Vor der Textsuche werden SQL-LIKE-Wildcards `%` und `_` entfernt. Anschließend müssen mindestens zwei Buchstaben oder Ziffern übrig bleiben.

Wichtig ist die Unterscheidung:

- gar kein Suchbegriff: Browse-Modus, alle passenden Titel dürfen erscheinen,
- eingegebener, aber nach Normalisierung zu kurzer Suchbegriff: keine Treffer.

Damit wird `%__` nicht zu einem unbeabsichtigten Verzeichnis aller Datensätze.

## Filter

### Medientyp

Der Medientyp wird exakt gegen `Edition.media_type` gefiltert.

Die Werte bleiben fachlich offen. Die Public-Surface kennt einige menschenlesbare Darstellungen wie:

- `book` → Buch,
- `audiobook` → Hörbuch,
- `ebook` / `e-book` → E-Book,
- `comic` → Comic,
- `manga` → Manga,
- `game` / `board_game` → Spiel.

Unbekannte Werte bleiben sichtbar und werden nicht verworfen.

### Sprache

Der Sprachfilter arbeitet exakt gegen `Edition.language_code` und normalisiert die Request-Eingabe auf Kleinbuchstaben.

Bekannte Codes werden für die Oberfläche übersetzt, darunter `de`, `en`, `fr`, `es` und `it`. Andere Codes bleiben als Code sichtbar.

### Nur aktive Exemplare

`active_only=1` verlangt mindestens ein `Copy` mit `CopyStatus::Active` an einer passenden Ausgabe des Titels.

Werden Medientyp, Sprache und Active-only gleichzeitig gesetzt, müssen diese Bedingungen an derselben Edition erfüllt sein. Ein Titel mit einem beschädigten deutschen Buch und einem aktiven englischen Hörbuch erfüllt deshalb nicht den Filter „Buch + Deutsch + aktives Exemplar“.

Der Filter sagt ausdrücklich nicht:

- „nur derzeit verfügbare Titel“,
- „nur nicht ausgeliehene Exemplare“.

Diese Information existiert vor T4 Circulation noch nicht.

## Sortierung

### Titel A–Z

Standard ist `preferred_title` aufsteigend. Diese Sortierung ist stabil und unabhängig von Editionsdaten.

### Zuletzt erfasst

`recent` sortiert nach `Title.created_at` absteigend und danach nach Titel. Es handelt sich um „zuletzt katalogisiert“, nicht um Erwerbungsdatum oder Erscheinungsjahr.

## Pagination

Die öffentliche Liste verwendet 12 Titel pro Seite.

Der Request validiert die Seitennummer und übernimmt nur validierte Filterparameter in die Links für „Zurück“ und „Weiter“.

Damit bleiben Filter beim Blättern erhalten, ohne beliebige Query-Parameter ungeprüft weiterzureichen.

## Bestandsmodell

`CatalogHoldingService` fasst Copy-Daten in `HoldingSummary` zusammen.

Ein Summary enthält:

- `totalCopies`,
- `activeCopies`,
- `damagedCopies`,
- `lostCopies`,
- `withdrawnCopies`,
- deduplizierte und sortierte `shelfLocations` aktiver Copies.

Die gleiche Logik wird für Trefferlisten und Titel-/Ausgabenansichten verwendet. Dadurch entstehen keine unterschiedlichen Definitionen von „aktivem Bestand“ in verschiedenen Oberflächen.

## Regalstandorte

Regalstandorte werden öffentlich nur für `active` Copies ausgegeben.

Ein Standort eines beschädigten, verlorenen oder ausgesonderten Exemplars ist intern weiterhin gespeichert, soll Leser:innen aber nicht als regulärer Fundort angeboten werden.

Mehrere aktive Copies am selben Standort ergeben nur einen Standortwert in der öffentlichen Zusammenfassung.

## Drei öffentliche Bestandszustände

Die Public-Surface unterscheidet fachlich drei leicht verständliche Zustände.

### Aktiver Bestand

Beispiel: `2 aktive Exemplare`.

Mindestens ein Copy ist `active`. Das bedeutet katalogseitig nutzbarer Bestand.

### Copies vorhanden, aber keines aktiv

Beispiel: `Derzeit kein aktives Exemplar`.

Es können beschädigte, verlorene oder ausgesonderte Copies existieren. Diese werden in der Detailansicht aggregiert erwähnt.

### Keine Copies vorhanden

Beispiel: `Noch kein Exemplarbestand`.

Der Titel und seine Edition können vollständig katalogisiert sein, obwohl noch kein physisches Exemplar angelegt wurde.

## Grenze zu Circulation

Der Katalog kennt in v0.4.5 keinen laufenden Loan-Status.

Deshalb gilt:

- `CopyStatus::Active` ist eine notwendige, aber keine hinreichende Bedingung für spätere Ausleihbarkeit.
- Ein aktives Copy kann mit T4 trotzdem gerade ausgeliehen sein.
- Erst Circulation darf eine echte Aussage „verfügbar / ausgeliehen / überfällig / vorgemerkt“ liefern.

Die aktuelle UI benennt diese Grenze explizit, damit Nutzer:innen keine falsche Zusage erhalten.

## Altersangaben

`minimum_age` und `age_rating_label` werden auf der öffentlichen Editionsansicht als Information angezeigt.

Es findet noch keine personenbezogene Entscheidung statt. Die spätere Ausleihe muss `minimum_age` gegen das Geburtsdatum des konkreten Patrons prüfen.

## Datenschutz und Need-to-know

Die öffentliche Oberfläche verarbeitet keine Patroninformationen.

Sie veröffentlicht nur katalogbezogene Daten, die für die Recherche nötig sind.

Insbesondere werden keine internen operativen IDs als fachliche Informationen ausgegeben. Route-IDs bleiben technische Adressierung, werden aber nicht als „Exemplarnummer“ oder ähnliche Nutzerinformation dargestellt.

## Offene Vokabulare

Medientyp, Sprache und Verantwortlichkeitsrolle bleiben importfreundlich offen.

`PublicCatalogPresenter` übersetzt nur bekannte Werte für die Anzeige. Diese Klasse liegt absichtlich in der Public-Surface, weil deutsche UI-Beschriftungen keine Catalog-Domänenregel sind.

## Demo-Daten

Der Development-Seed enthält für v0.4.5 zusätzlich:

### `The Giver`

- Verantwortliche: Lois Lowry,
- Medientyp: `book`,
- Sprache: `en`,
- aktives Exemplar: `BC-GIVER-001`,
- öffentlicher Standort: `EN 7 LOWR`.

Damit werden Sprachfilter und aktiver englischsprachiger Bestand demonstriert.

### `Die Welle`

- Verantwortlicher: Morton Rhue,
- Medientyp: `book`,
- Sprache: `de`,
- Edition vorhanden,
- kein Copy vorhanden.

Damit wird der Zustand „Noch kein Exemplarbestand“ demonstriert.

## Testmatrix

`PublicCatalogWorkflowTest` prüft den HTTP-Workflow:

- anonymer Zugriff,
- Browsing ohne Suchbegriff,
- Suche über Contributor,
- Suche über ISBN,
- Ausschluss von Copy-Barcodes,
- Medienfilter,
- Sprachfilter,
- Active-only-Filter,
- öffentliche Titelansicht,
- Nichtausgabe von Barcodes und Copy-ULIDs,
- Unterschied zwischen „kein Copy“ und „kein aktives Copy“,
- Wildcard-only-Eingabe,
- Pagination,
- Validierungsfehler bei ungültigen Filterwerten.

`CatalogPublicSearchQueryTest` prüft die wiederverwendbare Catalog-Logik:

- Rückwärtskompatibilität von `execute()`,
- paginiertes Browsing,
- kombinierte Filter,
- Barcode-Ausschluss auf Query-Ebene,
- Copy-Statusaggregation,
- öffentliche aktive Regalstandorte,
- dynamisch abgeleitete Filteroptionen.

`DemoSeederTest` prüft zusätzlich die neuen Demo-Suchzustände und weiterhin die Idempotenz des gesamten Seeds.

## Bewusst nicht Teil von v0.4.5

Nicht enthalten sind:

- Vormerkungen,
- echte Ausleihverfügbarkeit,
- Ranking oder Volltextindex,
- Tippfehler-/Fuzzy-Suche,
- Facettenzählungen,
- Cover-Import,
- externe Metadatenanbieter,
- CSV-/MARC21-Import,
- Erwerbungsdaten und Preise.

Diese Grenzen verhindern, dass der öffentliche Katalog bereits Fachlogik vorgibt, die erst in späteren T3-/T4-Schritten belastbar definiert wird.
