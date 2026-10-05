# T3 v0.4.6 – Katalogimport

## Ziel und Abgrenzung

v0.4.6 führt die Infrastruktur für kontrollierte Katalog-Massenimporte ein. CSV ist die erste Eingangsquelle. Die fachliche Pipeline kennt das Dateiformat jedoch nicht; spätere Quellen wie MARC21 sollen einen eigenen Adapter für `CatalogImportSource` erhalten und danach dieselben Normalisierungs-, Match-, Preview- und Commit-Schritte durchlaufen.

Nicht Bestandteil dieses Schritts sind MARC21-Decoding, Circulation, Verfügbarkeitsberechnung oder Hard-Delete-Workflows.

## Berechtigung

Der Import liegt im Surface `Bibliotheksbetrieb`, verwendet aber ein eigenes Permission-Key `catalog.import`.

- Mitarbeiter:innen: `catalog.manage` + `catalog.import`
- Verwaltung: `catalog.manage` + `catalog.import`
- Schüler-AG Erweitert: `catalog.manage`, **kein** `catalog.import`
- Schüler-AG Basis: kein Import
- technische Administration: kein Import und weiterhin kein automatischer fachlicher Katalog-/Patronzugriff

Routen prüfen ausschließlich Permission-Keys. Es gibt keine verstreuten Rollennamenprüfungen.

## Zwei Phasen

### 1. Upload, Mapping und Preview

`CsvCatalogImportSource` liest Header und Datenzeilen. Komma, Semikolon und Tabulator werden als Trennzeichen erkannt. Die Raw-Datei wird nicht in einem öffentlichen Storage abgelegt. Stattdessen persistiert der Import die für die Nachvollziehbarkeit nötigen Header und Rohwerte in `CatalogImportBatch` und `CatalogImportRow`.

Das Mapping verbindet CSV-Header mit den neutralen Feldern:

- Haupttitel, Untertitel, Sortiertitel
- Edition/Auflage, ISBN, Verlag, Erscheinungsjahr
- Medientyp, Sprache, Mindestalter, Altersfreigabe-Label
- Contributor Name, Contributor Sort Name, Contributor Role
- Barcode, Regalstandort, Copy Status

Die Preview normalisiert und bewertet jede Zeile und speichert `normalized_data`, `plan`, `warnings`, `conflicts` und den Zeilenstatus persistent. Ein Reload verliert die Vorschau nicht. In dieser Phase werden keine `Title`, `Edition`, `Contributor`, `TitleContribution` oder `Copy` geschrieben oder verändert.

### 2. Explizit bestätigte Übernahme

Nur ein Batch im Zustand `ready` kann übernommen werden. Das Formular verlangt zusätzlich eine explizite Bestätigung. `CommitCatalogImportAction` berechnet die Preview direkt vor dem Schreiben erneut, sperrt den Batch und führt anschließend sämtliche Katalog-Writes in **einer** Datenbanktransaktion aus. Ein Fehler in einer späteren Zeile rollt auch bereits ausgeführte Writes früherer Zeilen zurück.

## Persistenz und Status

`CatalogImportBatch` und `CatalogImportRow` verwenden interne ULIDs.

Batch-Status:

- `uploaded`: Quelle eingelesen; Mapping kann geprüft werden
- `previewed`: technischer Zwischenstatus während der Auswertung
- `ready`: Preview ist konfliktfrei und übernahmefähig
- `blocked`: mindestens eine ungültige Zeile oder ein kritischer Konflikt
- `committed`: vollständig und erfolgreich übernommen
- `failed`: reservierter persistenter Fehlerstatus für spätere Betriebs-/Queue-Erweiterungen

Zeilenstatus:

- `pending`
- `valid`
- `invalid`
- `conflict`

Es werden keine Import-Hard-Delete-Routen angeboten.

## Normalisierung

### ISBN

Leerzeichen, Bindestriche und übliche `ISBN`-/`ISBN-10`-/`ISBN-13`-Präfixe werden für strukturell plausible ISBN-10/ISBN-13 entfernt. Andere kurze Werte werden nicht zerstört, sondern getrimmt und mit Warnung erhalten. Werte über der Editionsgrenze von 32 Zeichen sind ungültig. Nur strukturell plausible normalisierte ISBN-10/ISBN-13 werden als Match- und Gruppierungsschlüssel verwendet; erhaltene Freitextwerte lösen keine automatische Editionszusammenführung aus. Beim Match werden auch bereits vorhandene, historisch mit Bindestrichen oder ISBN-Präfix gespeicherte Editions-ISBNs mit derselben Normalisierung verglichen.

### Sprache und Medientyp

Bekannte Varianten werden auf kurze bestehende Werte normalisiert, z. B. `DEU`/`Deutsch` → `de` und `Buch` → `book`. Unbekannte Werte bleiben erhalten, sofern sie die Feldgrenzen einhalten. Das offene Vokabular wird damit nicht künstlich zu einem Enum gemacht.

### CopyStatus

Bekannte deutsche und englische Werte werden auf die existierenden `CopyStatus`-Werte `active`, `damaged`, `lost` und `withdrawn` gemappt. Ein leerer Status bedeutet `active`. Ein unbekannter Status ist ungültig, weil `Copy.status` ein begrenzter Bestandszustand ist.

### Contributor-Rollen

Bekannte Rollen wie `Autor` werden normalisiert, weitere technisch gültige Rollen bleiben als offene `role_key` erhalten. Leer gelassene Rollen bei vorhandenem Contributor werden mit Warnung auf `contributor` gesetzt.

## Match- und Konfliktregeln

Die Pipeline arbeitet bewusst konservativ:

- doppelte Barcodes innerhalb desselben Batches blockieren alle betroffenen Zeilen,
- bereits existierende Katalog-Barcodes blockieren die Zeile,
- mehrere bestehende Editionen mit derselben ISBN sind nicht eindeutig und blockieren,
- ein eindeutiger ISBN-Match darf die vorhandene Edition und deren Titel wiederverwenden,
- widerspricht bei einem ISBN-Match der importierte Haupttitel dem bestehenden Titel, blockiert die Zeile,
- abweichende importierte Editionswerte überschreiben einen ISBN-Match nicht; die Preview warnt,
- ohne ISBN wird ein Titel nur bei exakt einem identischen `preferred_title` wiederverwendet,
- ähnliche/fuzzy Titel werden nie automatisch zusammengeführt,
- mehrere Datei-Zeilen derselben ISBN müssen in ihren bibliografischen Titel-/Editionsfeldern übereinstimmen; dann teilen sie sich einen Editionsplan und erzeugen mehrere Copies,
- Contributor-Wiederverwendung basiert auf exaktem Namen und – falls gemappt – exaktem Sortiernamen,
- vorhandene Datensätze werden durch einen Import niemals still aktualisiert.

## Preview und Bericht

Die Preview zählt mindestens:

- gültige, ungültige und konfliktbehaftete Zeilen,
- neue und wiederverwendete Titel,
- neue und wiederverwendete Editionen,
- neue Contributors,
- neue Contributions,
- neue Copies,
- Warnungen und Konflikte.

Nach dem Commit werden die tatsächlich neu erzeugten Datensätze und `committed_rows` im Batch-Bericht gespeichert. Wiederverwendungswerte stammen aus der unmittelbar vor dem Commit erneut berechneten Preview.

## UI

Der Einstieg `Import` befindet sich in der Katalogpflege und ist nur mit `catalog.import` sichtbar. Der Workflow besteht aus Upload, Mapping, persistenter Vorschau und bestätigter Übernahme. Die Vorschau verwendet das bestehende BiblioCollect/VDBS-Surface und eine horizontal scrollbar kapselte Tabelle statt eines generischen Admin-Dashboards.

## Development-Seed

`database/seeders/fixtures/catalog-import-demo.csv` und `CatalogImportDemoSeeder` erzeugen einen reproduzierbaren `ready`-Batch. Die Fixture demonstriert zwei Exemplare derselben ISBN. Der Seed committet den Batch absichtlich nicht, damit die Preview ohne Nebenwirkungen wiederholt geprüft werden kann und die bestehenden Demo-Katalogzählwerte stabil bleiben.
