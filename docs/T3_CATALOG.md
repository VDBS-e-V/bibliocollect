# T3 – Catalog

## Domänengrenzen

Der Katalog trennt drei Ebenen strikt:

- `Title`: titelbezogene bibliografische Identität und Verantwortlichkeiten.
- `Edition`: konkrete Ausgabe mit ausgabebezogenen Angaben wie ISBN, Verlag, Erscheinungsjahr, Medientyp, Sprachcode und Altersfreigabe.
- `Copy`: physisches Exemplar mit eigenem Barcode, Standort und Exemplarstatus.

Öffentliche Recherche und spätere Reservierungen werden titelbezogen aufgebaut. Ausleihe, Rückgabe, Inventur und Schäden arbeiten exemplarbezogen.

## Verantwortliche

`Contributor` ist absichtlich neutral benannt und kann Personen oder Körperschaften repräsentieren. `TitleContribution` verbindet einen Verantwortlichen mit einem Titel, speichert eine flexible `role_key` und eine Position für die Anzeigereihenfolge.

Die Rollen werden in v0.4.1 noch nicht als starres Enum begrenzt. Damit bleiben spätere CSV-/MARC21-Importe offen für weitere Verantwortlichkeitsarten, ohne das Domänenmodell sofort ändern zu müssen.

## Editionsmetadaten

`media_type` und `language_code` sind in v0.4.1 bewusst offene, kurze Zeichenketten. Normalisierung, kontrollierte Vokabulare und Import-Mappings folgen erst mit der Katalogpflege bzw. dem Import-Workflow.

Die vorhandenen Felder `minimum_age` und `age_rating_label` bleiben an `Edition`. Eine harte Ausleihentscheidung wird erst in Circulation gegen das Geburtsdatum des Patrons ausgewertet.

## Suche

`SearchCatalogTitlesQuery` liefert immer `Title`-Datensätze zurück. Gesucht wird über Titel, Untertitel, Sortiertitel, Verantwortliche sowie ausgewählte Editionsfelder. Physische Barcodes sind bewusst nicht Teil dieser titelbasierten Recherche.

Die Query entfernt SQL-LIKE-Wildcards aus Benutzereingaben, verlangt für eine Textsuche mindestens zwei Buchstaben/Ziffern und begrenzt die Treffermenge. Ab v0.4.5 besitzt sie zusätzlich einen paginierten Kriterienpfad für die öffentliche Recherche. Ein leerer Suchbegriff bedeutet dort bewusst „Katalog durchstöbern“; ein nicht-leerer, nach der Bereinigung aber zu kurzer Suchbegriff liefert dagegen keine Treffer und wird nicht zum unbeabsichtigten Verzeichnis aller Titel.

## Interne Katalogpflege

Ab v0.4.2 liegt die Katalogpflege im Surface `Bibliotheksbetrieb` und nicht in der Systemverwaltung. Das Fachrecht `catalog.manage` wird Schüler-AG Erweitert, Mitarbeiter:innen und Verwaltung zugewiesen. Schüler-AG Basis und technische Administration erhalten es nicht.

v0.4.2 hat die Pflege von `Title` und `Edition` eingeführt. v0.4.3 ergänzt die Verantwortlichenpflege. v0.4.4 ergänzt die Pflege physischer `Copy`-Datensätze. Änderungen laufen jeweils über eigene Catalog-Actions und validierte DTOs; die HTTP-Validierung bleibt in der POS-Surface.

Löschfunktionen werden nicht pauschal angeboten. Bei Verantwortlichen wird nur die Titelverknüpfung gelöst und ein verwaister Contributor kontrolliert bereinigt. Physische Exemplare werden überhaupt nicht hart gelöscht.

## Verantwortlichenpflege

Ab v0.4.3 können Verantwortliche innerhalb der Titelpflege angelegt, bearbeitet und vom Titel gelöst werden. `display_name` und optional `sort_name` gehören zum wiederverwendbaren `Contributor`; `role_key` und `position` gehören zur konkreten `TitleContribution`.

`role_key` bleibt weiterhin ein offener technischer Schlüssel. Die Oberfläche normalisiert ihn auf Kleinbuchstaben und erlaubt Buchstaben, Ziffern, Punkt, Unterstrich und Bindestrich. Dadurch bleiben spätere Import-Mappings möglich, ohne früh ein starres Rollen-Enum einzuführen.

Wird ein Contributor bearbeitet, ändern sich seine Namensdaten an allen Titeln, die denselben Datensatz verwenden. Das Bearbeitungsformular weist darauf hin, wenn der Contributor an mehreren Titeln genutzt wird. Beim Entfernen eines Verantwortlichen wird nur die Titelverknüpfung gelöst; ein danach verwaister Contributor wird automatisch bereinigt.

## Exemplarpflege

Ab v0.4.4 werden physische Exemplare innerhalb ihrer `Edition` gepflegt. Die editierbaren Felder sind:

- `barcode`: katalogweit eindeutig und sichtbar, aber niemals Primärschlüssel,
- `shelf_location`: optionaler Regal- oder Standortwert,
- `status`: einer der vorhandenen Werte `active`, `damaged`, `lost` oder `withdrawn`.

Ein `Copy` kann in diesem Workflow nicht auf eine andere Ausgabe verschoben werden. Das ist absichtlich keine Nebenwirkung eines normalen Bearbeitungsformulars. Sollte ein solcher Fachworkflow später benötigt werden, braucht er eine eigene Action mit expliziten Regeln.

Es gibt keine Hard-Delete-Route für Exemplare. `withdrawn` repräsentiert dauerhaft ausgesonderten Bestand, ohne die Exemplaridentität zu verlieren. Das ist wichtig, weil spätere Circulation-, Inventur- und Schadenshistorien auf derselben internen Copy-ULID aufbauen sollen.

Der aktuelle `CopyStatus` beschreibt den Katalog-/Bestandszustand. T4 Circulation muss zusätzlich den tatsächlichen Ausleihzustand berücksichtigen; ein Statuswert allein ist noch keine vollständige Verfügbarkeitsentscheidung.

Barcode-Duplikate werden nicht nur durch den Datenbankindex verhindert. `CreateCopyAction` und `UpdateCopyAction` prüfen die Eindeutigkeit fachlich und liefern der Oberfläche einen verständlichen Fehler. Die Datenbank-Unique-Constraint bleibt die letzte technische Sicherung.


## Öffentlicher Katalog

Ab v0.4.5 ist die bisherige Startseiten-Vorschau durch eine echte anonyme Katalogstrecke ergänzt.

### Routen und Surface-Grenze

Die Public-Surface stellt bereit:

- `GET /katalog` für Suche, Browsing, Filter und Pagination,
- `GET /katalog/titel/{titleId}` für die öffentliche Titel-/Ausgabenansicht.

Für diese Routen gibt es bewusst keine Login- oder Rollenanforderung. Bibliotheksnutzung und Katalogrecherche bleiben damit unabhängig von einem Onlinekonto.

HTTP-Validierung, menschenlesbare Beschriftungen und Blade-Darstellung liegen in `Surfaces/Public`. Das Catalog-Modul bleibt frei von konkreten Surface-Abhängigkeiten.

### Suchkriterien

`CatalogSearchCriteria` transportiert die fachlich neutralen Suchparameter:

- optionaler Suchbegriff,
- optionaler Medientyp,
- optionaler Sprachcode,
- optional „nur Titel mit aktiven Exemplaren“,
- Sortierung,
- Seitengröße und Seitennummer.

Die Textsuche bleibt titelbezogen und berücksichtigt:

- Haupttitel,
- Untertitel,
- Sortiertitel,
- Contributor-Anzeige- und Sortiernamen,
- ISBN,
- Verlag,
- Medientyp,
- Sprachcode.

Copy-Barcodes bleiben ausgeschlossen. Sie identifizieren ein physisches Exemplar und gehören in operative Copy-/Circulation-Workflows, nicht in die öffentliche titelbezogene Recherche.

### Filter und offene Vokabulare

`CatalogSearchFilterOptionsQuery` leitet vorhandene Medientypen und Sprachcodes aus den Editionsdaten ab. Damit werden unbekannte oder später importierte Werte nicht verworfen.

Die Public-Surface übersetzt bekannte Werte wie `book`, `audiobook`, `de` oder `en` in verständliche Beschriftungen. Unbekannte Werte werden lesbar dargestellt, aber nicht in der Domäne auf ein starres Enum gezwungen.

### Bestandszusammenfassung

`CatalogHoldingService` aggregiert pro Titel oder Ausgabe:

- Gesamtzahl physischer Exemplare,
- aktive Exemplare,
- beschädigte Exemplare,
- verlorene Exemplare,
- ausgesonderte Exemplare,
- Regalstandorte aktiver Exemplare.

Öffentlich werden keine Copy-Barcodes und keine internen Copy-ULIDs ausgegeben.

Regalstandorte werden nur aus aktiven Exemplaren abgeleitet. Ein Lagerort eines verlorenen, beschädigten oder ausgesonderten Exemplars soll nicht als regulärer Fundort für Leser:innen erscheinen.

### „Aktiv“ ist noch nicht „verfügbar“

Die wichtigste fachliche Grenze von v0.4.5 ist die Sprache der Bestandsanzeige:

- `active` bedeutet: Das Exemplar ist katalogseitig nutzbarer Bestand.
- `damaged`, `lost` und `withdrawn` sind nicht aktive Bestandszustände.
- Ob ein aktives Exemplar gerade ausgeliehen ist, ist noch unbekannt.

Die Public-Surface verwendet deshalb Formulierungen wie „2 aktive Exemplare“ und vermeidet Aussagen wie „2 verfügbar“. Erst T4 Circulation kann eine echte Verfügbarkeitsentscheidung aus Copy-Status und laufendem Ausleihzustand zusammensetzen.

### Titel ohne Bestand

Ein `Title` darf öffentlich recherchierbar sein, obwohl aktuell kein physisches `Copy` existiert. Das ist für vorbereitete Katalogisate, noch nicht eingetroffene Bestände und spätere Import-Workflows wichtig.

Die Oberfläche unterscheidet deshalb:

- „Noch kein Exemplarbestand“: Es existiert kein Copy.
- „Derzeit kein aktives Exemplar“: Copies existieren, aber keines davon ist `active`.
- „N aktive Exemplare“: Mindestens ein katalogseitig aktives Copy existiert.

### Pagination und Datenschutz

Die öffentliche Browse-Ansicht paginiert serverseitig. Filterparameter werden beim Blättern erhalten, aber nur nach erfolgreicher Validierung wieder an Pagination-Links angehängt.

Die Titelansicht veröffentlicht bibliografische Daten und aggregierten Bestand. Interne operative Identitäten werden nicht als öffentliche Navigations- oder Anzeigedaten verwendet.


## Import-Infrastruktur

Ab v0.4.6 besitzt Catalog eine formatunabhängige Importpipeline. Das Modul definiert mit `CatalogImportSource` nur den Vertrag für Header und Quellzeilen. `CsvCatalogImportSource` ist die erste Implementierung; ein späterer MARC21-Adapter soll denselben Normalisierungs-, Preview- und Commitpfad verwenden.

Importe sind bewusst zweiphasig. Upload, Mapping und Preview persistieren ausschließlich `CatalogImportBatch`/`CatalogImportRow` und verändern weder `Title` noch `Edition`, `Contributor`, `TitleContribution` oder `Copy`. Erst eine konfliktfreie Preview mit zusätzlicher Bestätigung darf über `CommitCatalogImportAction` Katalogdaten schreiben.

Das Fachrecht `catalog.import` ist von `catalog.manage` getrennt. Nur Mitarbeiter:innen und Verwaltung erhalten das Massenimportrecht. Schüler-AG Erweitert kann weiterhin einzelne Katalogdatensätze pflegen, aber keine Import-Batches einsehen oder übernehmen. Technische Administration erhält ebenfalls kein Importrecht.

Die Match-Regeln sind konservativ: Nur eine strukturell plausible normalisierte ISBN-10/ISBN-13 darf als eindeutiger Schlüssel eine vorhandene Edition wiederverwenden; nicht standardisierte ISBN-Freitextwerte bleiben erhalten, lösen aber keinen Editions-Merge aus. Ein exakter eindeutiger Haupttitel darf einen Titel wiederverwenden, fuzzy Titelzusammenführungen gibt es nicht. Wiederverwendete Datensätze werden durch Importdaten nicht still überschrieben. Mehrere Zeilen derselben ISBN teilen sich bei übereinstimmenden bibliografischen Angaben einen Editionsplan und können dadurch mehrere Copies erzeugen.

Barcodes bleiben katalogweit eindeutig. Duplikate innerhalb der Importdatei und bereits vorhandene Katalog-Barcodes sind blockierende Konflikte. Die Übernahme prüft den aktuellen Katalog unmittelbar vor dem Schreiben erneut und läuft als Gesamttransaktion.

Weitere Details, Statusmodell, Normalisierung und UI-Ablauf stehen in `docs/T3_CATALOG_IMPORT.md`.
