# Themen und Regalbretter importieren (ohne Konsole)

Die Seite **Verwaltung → Regalbretter → „Themen und Regalbretter importieren“** (`/verwaltung/klassifikation-import`) übernimmt die Themenliste
und die Regalsignaturen des Altsystems. Sie braucht keinen Konsolenzugriff und läuft nach einem normalen Update.

## Berechtigung

`shelves.manage` (Mitarbeiter:innen und Verwaltung). Alle Aktionen sind angemeldet, mit CSRF-Schutz und Begrenzung der Anfragen
(10 Vorschauen und 5 Importe je Minute).

## Dateien

| Datei | Tabelle | Inhalt |
|---|---|---|
| `mediaTopicList.json` | `mediaTopicList` | `id`, `public_topic_id`, `main_topic` (Eltern-ID, 0 = oberste Ebene), `topic`, `description` |
| `mediaSignatures.json` | `mediaSignatures` | `id`, `signature` („I. A 1 a“), `topic_ids` (JSON-Liste von Themen-IDs, auch leer) |

Erlaubt sind der phpMyAdmin-Export (Kopf, Datenbank, Tabelle mit `data`) oder eine einfache Liste von Zeilen (derselbe Leser wie beim
Altbestandsimport, `PhpMyAdminJsonTableReader`). Jede Datei höchstens 2 MB und 5000 Zeilen. Eine oder beide Dateien sind möglich;
Signaturen dürfen auf Themen verweisen, die schon im Bestand sind.

## Ablauf

1. **Vorher sichern:** Systemzustand → „Jetzt sichern“.
2. **Prüfen (Vorschau):** Die Dateien werden außerhalb des Webroots abgelegt (`storage/app/private/klassifikation-import`) und mit
   SHA-256 festgehalten. Die Seite zeigt neue, unveränderte und abweichende Themen, Regalbretter, Warnungen und Fehler.
   **Es wird nichts in Katalogdaten geschrieben.**
3. **Bestätigen:** Der Import prüft unmittelbar vor dem Schreiben noch einmal: Die Dateien müssen unverändert sein, der Entwurf gehört
   derselben Person, ist nicht abgelaufen (60 Minuten) und nicht schon übernommen, und die Prüfsumme der Vorschau muss zum aktuellen
   Stand von Dateien und Bestand passen. Dann wird in **einer Transaktion** geschrieben (alles oder nichts).
4. **Bericht:** neue und bestehende Themen, Themenkonflikte, Signaturen, Regalbretter, neue Themenzuordnungen, Warnungen. Der Import
   steht im Protokoll (`catalog.classification.imported`, mit Prüfsummen der Dateien und den Zahlen).

## Was geschrieben wird

- **Themen** (`catalog_topics`) mit Quelle `vdbs-legacy` und der Legacy-ID; so erkennt der Import auch Themen wieder, die der
  Altbestandsimport schon angelegt hat. Die alte ID wird nie Primärschlüssel; `main_topic` wird auf das neue Elternthema abgebildet.
- **Signaturen** (`catalog_signatures`) und ihre Themen (`catalog_signature_topics`).
- **Regalbretter** (`catalog_shelves`, Code = Signatur) mit Regal, Bereich und Bereichsgruppe (`catalog_shelf_sections`) und den
  Themen (`catalog_shelf_topics`). Namen der Gruppen und Bereiche (Literatur, Fachliteratur, …) werden nur eingetragen, wo noch keiner steht.

Nicht angefasst werden Exemplare, Ausleihen, Vormerkungen, Inventarnummern und die Standorte vorhandener Exemplare. Vorhandene
Regalbretter behalten Reihenfolge, Beschriftung und Schalter. Vorhandene Themenzuordnungen bleiben; es werden nur fehlende ergänzt.
Es wird nichts gelöscht und nichts zusammengeführt. Veraltete Themen aus früheren Importen bleiben bestehen (Bereinigung ist ein eigener Schritt).

## Was den Import verhindert (Fehler)

- ungültiges JSON, fehlende Tabelle, zu große Datei
- fehlende oder doppelte IDs, doppelte öffentliche Schlüssel in der Datei
- ein öffentlicher Schlüssel, der im Bestand bei einem anderen Thema liegt
- fehlende Namen, zu lange Felder
- Elternthema, das weder in der Datei noch im Bestand steht; Zyklen; ein Thema als eigenes Elternthema
- Signaturen, die nicht die Form „I. A 1 a“ haben, doppelte Signaturen, ungültige `topic_ids`, Verweise auf unbekannte Themen
- eine Legacy-ID, die im Bestand zu einer anderen Signatur gehört

**Abweichungen** vorhandener Themen (Name, Beschreibung, Schlüssel, Elternthema) sind **Konflikte**, aber keine Fehler. Standardmäßig
bleiben solche Themen unverändert; mit dem ausdrücklichen Haken „Abweichungen übernehmen“ werden sie an die Datei angepasst.

## Wiederholter Import

Derselbe Import ist idempotent: Themen und Signaturen werden über Legacy-ID beziehungsweise Signatur wiedererkannt, Regalbretter über den Code
(auch bei anderer Schreibweise wie „IA1a“, mit Warnung), Zuordnungen nur ergänzt. Die Vorschau meldet dann „nichts zu übernehmen“.

## Technik

`App\Modules\Catalog\Import\Classification\ClassificationImportPlanner` (Prüfung ohne Schreiben), `ClassificationImporter`
(Transaktion), Entwürfe in `catalog_classification_import_drafts`, Oberfläche `ClassificationImportController`. Die bestehende
Importlogik (`ImportLegacyCatalogAction`, `ImportCatalogShelvesFromSignaturesAction`) wurde nicht verändert; letztere fasst
Exemplar-Standorte an und eignet sich deshalb nicht für diesen Ablauf.
