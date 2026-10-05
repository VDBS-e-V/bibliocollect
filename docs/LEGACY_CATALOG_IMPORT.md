# Legacy-Katalogmigration – altes BiblioCollect-System

## Zweck

Dieser Zwischenblock migriert den vorhandenen Katalog aus dem alten BiblioCollect-System in das aktuelle `Title → Edition → Copy`-Modell. Die alte Datenbank wurde häufig automatisiert aus DNB-/Normdaten angereichert und besitzt deshalb deutlich mehr bibliografische Felder als der bisherige T3-Kern.

Die Migration ist **kein** Ersatz für die normale CSV-Importoberfläche und auch noch kein MARC21-Importer. Sie ist ein einmaliger, bewusst konservativer Console-Workflow für den historischen Bestand.

## Bevorzugtes Exportformat

Für die realen Daten wird phpMyAdmin **JSON** verwendet. Benötigt werden möglichst drei getrennte Exporte:

- `mediaList`
- `mediaTopicList`
- `mediaSignatures`

Der Reader akzeptiert sowohl den üblichen phpMyAdmin-JSON-Envelope (`type=table`, `data=[...]`) als auch eine direkte JSON-Liste von Objektzeilen. Es sollen keine Spalten vor dem Export entfernt oder Werte manuell bereinigt werden.

`mediaLog` und `bookWishes` gehören bewusst nicht in diesen ersten Katalogimport. `mediaLog` kann später als getrenntes Legacy-Audit übernommen werden; Anschaffungswünsche gehören fachlich in einen eigenen Workflow.

## Zielstruktur

### Title

Der titelbezogene Kern bleibt schlank:

- Haupttitel
- Untertitel
- Sortiertitel
- strukturierte Verantwortlichkeiten über `TitleContribution`

### Contributor

`Contributor` erhält zusätzlich `gnd_id`. Import-Matching bevorzugt eine vorhandene GND-ID; ohne GND wird konservativ über den exakten Anzeigenamen gearbeitet. Rollen bleiben offene `role_key`-Werte.

### Edition

Die konkrete Ausgabe trägt die DNB-nahen bibliografischen Metadaten:

- Verantwortlichkeitsangabe im Wortlaut (`authors_statement` / MARC 245 $c)
- Reihen-/Serienangabe
- Editions-/Auflagenangaben
- Erscheinungsort, Verlag und Jahr
- primäre ISBN sowie weitere Identifikatoren
- ISSN und DOI/Handle
- lokale Klassifikation
- Medientyp
- Sprache und Originalsprache
- Seitenzahl und physischer Umfang
- Dateigröße und Format für digitale Medien
- Inhaltsangabe
- lokale und systemische Schlagwörter
- Zielgruppe
- strukturierte Altersangabe plus vorhandenes Alters-/FSK-Label
- Metadatenquelle, DNB-RCN und Quellen-Permalink
- `legacy_source` plus stabiler Editions-Gruppierungsschlüssel

DNB-/Quellenwerte werden damit nicht nur als anonymer Freitext behandelt. Spätere DNB-/MARC21-Anreicherung kann diese Provenienz gezielt verwenden.

### Copy

`inventory_number` wird der sichtbare neue `barcode`. Die alte interne `media_id` wird **nicht** zum Primärschlüssel; interne IDs bleiben ULIDs.

Zusätzlich werden pro Exemplar erhalten:

- strukturierte Signaturreferenz plus bisheriges `shelf_location`
- Legacy-Schul-ID
- Zugangsstatus
- Kaufdatum und Kaufpreis
- Aufnahmedatum
- alter Coverpfad als reine Legacy-Referenz
- interne Notizen
- Zustandsangaben
- Aussonderungsgrund/-datum und weitere Verwendung
- alter Ausleihzähler und letztes Ausleihdatum
- alter `is_available`- und `in_transition`-Wert
- vollständige ursprüngliche Legacy-Zeile in `legacy_metadata`

Der vollständige Rohdatensatz bleibt damit nachvollziehbar, ohne dass jedes historische Feld aktuelle Fachlogik werden muss.

## Themen und Signaturen

`mediaTopicList` wird als `CatalogTopic` mit ULID, öffentlichem Schlüssel, Elternbeziehung, Name und Beschreibung übernommen. `main_topic=0` bedeutet Wurzelebene. Fehlende Eltern im Export werden als Warnung behandelt; der betroffene Topic bleibt dann auf Wurzelebene.

`mediaSignatures` wird zu `CatalogSignature`. Die JSON-Liste `topic_ids` wird über `catalog_signature_topics` als geordnete n:m-Zuordnung erhalten. Ein `Copy` kann zusätzlich direkt auf seine strukturierte Signatur verweisen. Der bisherige Textwert in `shelf_location` bleibt für bestehende Bestands-/Public-Funktionen kompatibel.

## Aktuelle Fachlogik versus Legacy-Historie

Folgende alten Werte dürfen **keine** neue Circulation-Wahrheit erzeugen:

- `is_available`
- `loan_counter`
- `last_loan_date`
- `in_transition`

Sie werden lediglich als Legacy-Historie gespeichert. Eine aktuelle Verfügbarkeitsentscheidung entsteht weiterhin ausschließlich aus aktuellem Copy-Status und Circulation-Loans. Auch `access_status` wird in diesem Zwischenblock zunächst als Bestandsmetadatum erhalten; eine spätere fachliche Regel für `praesenz`, `eingeschraenkt` oder `gesperrt` muss ausdrücklich im zentralen Circulation-Regelwerk definiert werden.

Der Import leitet den aktuellen `CopyStatus` nur aus dauerhaften Bestandsmerkmalen ab:

- vorhandene Aussonderungsangabe/-datum oder „Zerstört“ → `withdrawn`
- erkennbare Schadensangabe → `damaged`
- sonst → `active`

Ein altes `is_available=0` macht ein Exemplar ausdrücklich **nicht** automatisch beschädigt, verloren oder ausgesondert.

## Schonende Normalisierung

Der Import normalisiert nur Werte, bei denen die Bedeutung belastbar ist:

- bekannte Medientypen wie `Buch` → `book`
- bekannte Sprachen wie `Deutsch`, `ger`, `deu` → `de`
- plausible ISBN-10/ISBN-13 über den vorhandenen `CatalogIsbnNormalizer`
- `(DE-588)` wird von einer GND-ID entfernt
- `0000` beim Erscheinungsjahr → `null`
- `0000-00-00` bei Datumsfeldern → `null`
- HTML in Inhaltsangaben wird zu sicherem Klartext

Nicht plausible ISBN-/EAN-Freitexte bleiben unter den alternativen Identifikatoren erhalten und werden **nicht** als automatischer Merge-Schlüssel benutzt.

Mögliche alte Zeichensatzartefakte wie `Mu?nchen` werden gemeldet, aber nicht geraten/korrigiert. Der Originalwert bleibt zusätzlich in `legacy_metadata` erhalten.

## Gruppierung und Konflikte

Mehrere physische Exemplare derselben bibliografischen Ausgabe werden auf eine `Edition` gruppiert. Der Gruppierungsschlüssel berücksichtigt Titel/Untertitel, ISBN, Editionsangaben, Verlag/Jahr, DNB-RCN, Medientyp und Sprache. Damit führt dieselbe ISBN bei unterschiedlichen Titeln nicht automatisch zu einer Zusammenführung.

Blockierend sind insbesondere:

- fehlender Haupttitel
- fehlende Inventarnummer/Barcode
- fehlende Legacy-`media_id`
- derselbe Barcode mehrfach im Export
- derselbe Legacy-`media_id` mit unterschiedlichen Inventarnummern
- ein Barcode, der im neuen Katalog bereits zu einem anderen Datensatz gehört
- eine nicht eindeutige exakte Titelzuordnung im Zielkatalog
- strukturell unbrauchbare Topic-/Signatur-IDs

Vor dem Import wird derselbe Analysepfad ausgeführt. Bei einem Konflikt startet die Datenbanktransaktion nicht.

## Console-Workflow

Zuerst immer analysieren:

```text
php artisan catalog:legacy:analyze storage\app\private\legacy\mediaList.json --topics=storage\app\private\legacy\mediaTopicList.json --signatures=storage\app\private\legacy\mediaSignatures.json
```

Die Analyse schreibt keine `Title`, `Edition`, `Contributor`, `Copy`, Topic- oder Signaturdatensätze.

Erst wenn der Bericht keine blockierenden Konflikte enthält:

```text
php artisan catalog:legacy:import storage\app\private\legacy\mediaList.json --topics=storage\app\private\legacy\mediaTopicList.json --signatures=storage\app\private\legacy\mediaSignatures.json
```

Der Import läuft als Gesamttransaktion. Ein Fehler rollt den gesamten Lauf zurück.

## Idempotenz

Importierte Exemplare werden über `legacy_source + legacy_media_id` wiedererkannt. Importierte Editionen besitzen zusätzlich `legacy_source + legacy_record_key`. Topics und Signaturen erhalten ihre alten IDs ebenfalls nur als Legacy-Referenz; ihre internen IDs bleiben ULIDs.

Ein erneuter Lauf erzeugt deshalb keine zweiten physischen Exemplare. Bereits vorhandene normale Katalogdatensätze werden nicht still überschrieben. Ein eindeutiger Titel/ISBN-Match wird nur wiederverwendet, wenn die vorhandenen Kernangaben mit dem Legacy-Datensatz kompatibel sind; dann werden ausschließlich bislang leere Editionsmetadaten ergänzt. Widersprüchliche Auflagen-/Verlags-/Jahres-/Quellenangaben werden nicht still zusammengeführt, sondern bleiben als getrennte Ausgabe erhalten.

## Cover

`cover_image_path` wird nur als `legacy_cover_path` erhalten. Die alten Bilddateien werden nicht kopiert. Ein späterer Cover-Service soll Cover anhand ISBN/DNB-Identifier neu beziehen und unabhängig von der Legacy-Migration verwalten.

## Testbetrieb

Der Development-Seed enthält eine ausführlich angereicherte bestehende Demo-Ausgabe sowie Topic-/Signaturdaten, ohne die bisherigen Titel-/Exemplarzählwerte zu verändern. Zusätzlich liegen drei realistische phpMyAdmin-JSON-Fixtures unter `database/seeders/fixtures/`, mit denen Analyse, Import, Mehrfachexemplare, Klassifikation, Legacy-Verfügbarkeit und Warnungen automatisiert getestet werden.
