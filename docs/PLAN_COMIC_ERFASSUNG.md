# Plan: Comics erfassen (Beispiel Lustiges Taschenbuch)

Stand: Bewertung des Erfassungsprozesses auf `main` (v0.71). Noch nichts umgesetzt.

## Ausgangsfrage

Wie gut trägt der Erfassungsprozess Comic-Reihen, am Beispiel „Lustiges Taschenbuch“ (LTB, Egmont Ehapa, rund 600 Bände, ältere Bände ohne ISBN)? Die Lösung soll für **alle** Comics gelten, nicht nur für das LTB.

## Bewertung

Gesamturteil: Erfassen geht, aber nicht gut. Bücher sind sehr gut abgedeckt, Comic-Reihen tragen nur bedingt.

### Was schon trägt

- Der Ablauf (`docs/CATALOG_INTAKE.md`) kennt Barcode zuerst, ISBN-Abfrage und vollständig manuelle Erfassung ohne ISBN. Ältere Bände ohne ISBN sind erfassbar.
- Reihe und Bandnummer werden aus der Reihenangabe abgeleitet (`SeriesParser`, `SeriesService`, `Edition::series_id` und `series_volume`). „Lustiges Taschenbuch ; 123“ ergibt Reihe und Band 123.
- Die DNB-Reihe (MARC 490/830, `$a ; $v`) wird übernommen (`DnbMarcMapper::series`).
- Rollen Autor:in, Illustrator:in, Übersetzer:in, Herausgeber:in; Zeichner und Texter lassen sich als Illustrator:in und Autor:in führen.
- Mehrere Exemplare je Ausgabe, öffentliche Reihenseite mit „nächster Band“ (`Public\SeriesController`).

### Schwächen

1. **Comic-Reihen können als Verlagsreihe versteckt werden.** `SeriesService::IMPRINT_ENDINGS` enthält `' taschenbuch'`. Der Schlüssel „lustiges taschenbuch“ endet so und bekommt `is_hidden = true`; die öffentliche Reihenseite und die Reihenübersicht zeigen ihn dann nicht.
2. **Kein Medientyp „Comic“ in der Erfassung.** `CatalogIntakeVocabulary::mediaTypes()` bietet Buch, Hörbuch, E-Book, Zeitschrift, DVD, Spiel. „Comic“ und „Manga“ kennen nur Anzeige und Statistik. `DnbMarcMapper::mediaType` liefert für Comics `book` oder `null` (Befund „Kein Medientyp“), nie `comic`.
3. **Duplikaterkennung nur über die ISBN.** Ohne ISBN erkennt die Erfassung eine vorhandene Ausgabe nicht; der nächste Band würde als neuer Titel mit gleichem Haupttitel angelegt.
4. **Bandnummer nur über Freitext.** Was nicht „Name ; Nr“ entspricht („LTB 500“, „Enten-Edition 12“), wird nicht als Band erkannt.
5. **Titelmodell:** Alle Bände heißen gleich („Lustiges Taschenbuch“), der eigentliche Titel steht im Untertitel; Suche und Etiketten unterscheiden nur über Band und Untertitel.
6. **Cover:** Open Library und Google Books sind bei Comics lückenhaft; ohne ISBN gibt es keine Cover-Abfrage.
7. **Regalvorschlag ignoriert den Medientyp.** `CatalogShelfSuggester::bookTerms()` vergleicht nur Titel, Schlagwörter, Reihe, Zielgruppe und Inhaltsangabe mit den Regalbrettern. Ein Comic ohne das Wort „Comic“ dort bekommt ein Regalbrett „Comics“ nie vorgeschlagen.

## Vorschlag: allgemeine Comic-Option

Keine Namenslisten und keine Sonderregel für einzelne Reihen, sondern ein durchgängiger Medientyp `comic`, an dem die Reihenlogik hängt. Kleine Schritte, je ein Pull Request.

1. **Medientyp `comic` (und `manga`)** in `CatalogIntakeVocabulary::mediaTypes()`. Er wird in der Erfassung gewählt oder aus der DNB vorbelegt. `DnbMarcMapper::mediaType` erkennt Comics allgemein (MARC 336 `sti`, Genre- und Schlagwort „Comic“, 655), nicht anhand eines Titels.
2. **Reihen von Comics werden nie als Verlagsreihe ausgeblendet.** `SeriesService::resolve` bekommt den Medientyp der Ausgabe; bei `comic` und `manga` ist `is_hidden` immer `false`. Die Heuristik `looksLikePublisherLine` (`IMPRINTS`, `IMPRINT_ENDINGS`, Verlagsname) gilt nur für Bücher. Die Endung `' taschenbuch'` bleibt für Bücher unverändert.
3. **Hefte ohne ISBN:** Die Erfassung (`CatalogIntakeController`) sucht zusätzlich nach vorhandenen Ausgaben mit gleicher Reihe und gleichem Band und bietet „nur Exemplar ergänzen“ an (bisher nur über `matches->editionsByIsbns`).
4. **Bandnummer:** Weiterhin aus der Reihenangabe (`SeriesParser`). Comics bekommen in Erfassungsschritt 3 ein eigenes Feld „Band“, das `series_statement` in der Form „Reihe ; Nr“ zusammensetzt.
5. **Bestand:** Nach dem Release `catalog:series:sync` ausführen und vorhandene Reihen mit Comic-Ausgaben einblenden (`is_hidden = false`).
6. **Regalvorschlag:** `CatalogShelfSuggester::bookTerms()` wertet den Medientyp aus (`comic` wird zum Wort „Comic“, Gewicht 3, wie Titel und Schlagwörter).

Schritt 6 ist bewusst klein: Der Medientyp wird nur als zusätzliche Quelle für den Vergleich genutzt, die Bewertung bleibt unverändert.

## Verifikation

- `composer quality`.
- Tests: LTB-Fixture in `BibliographicLookupTest` (Medientyp `comic`), Reihentest (nicht versteckt, Band 123), Erfassungstest ohne ISBN mit vorhandener Reihe und Band, Regalvorschlag mit Medientyp `comic`.
- Manuell lokal: Erfassung „Lustiges Taschenbuch ; 123“ ergibt eine sichtbare öffentliche Reihenseite und den Medientyp Comic.
