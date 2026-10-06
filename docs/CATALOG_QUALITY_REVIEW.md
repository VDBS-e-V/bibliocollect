# Katalogqualität: Metadaten prüfen und Vorschläge bestätigen

Die Seite `/betrieb/katalog/qualitaet` (Link „Metadaten prüfen“ in der Katalogpflege) listet Ausgaben mit unvollständigen oder fehlerhaften Metadaten. Zu jeder gibt es einen Vorschlag, den eine Person Feld für Feld bestätigt. Das Recht ist `catalog.manage`.

## Was als Problem gilt

| Problem | Erkennung | Gewicht |
|---|---|---|
| Verlorene Umlaute | `?` zwischen Buchstaben (z. B. „Ra?uber“) in Titel, Untertitel, Verantwortlichkeitsangabe, Verlag, Ort, Auflage, Reihe, Umfang und Namen der Verantwortlichen | 40 |
| Zeichensatzfehler | typische Mojibake-Folgen („Ã¼“) | 40 |
| Steuerzeichen | unsichtbare C1-Zeichen (U+0080–U+009F), die DNB-Sortiermarker | 30 |
| Keine Verantwortlichen | Titel ohne verknüpfte Person | 25 |
| Kein Erscheinungsjahr | leer | 15 |
| Kein Verlag / kein Medientyp | leer | 10 |
| Keine ISBN | leer | 8 |
| Keine Zusammenfassung / Schlagwörter | leer | 0 |

Fehlende Zusammenfassung oder Schlagwörter zählen **nicht** als Mangel (sie fehlen im Altbestand fast überall). Sie haben Schwere 0, erscheinen nicht in der Standardliste und nicht als Badge, sind aber über die Filter „Anreicherung“ erreichbar.

Die Fälle stehen in `catalog_metadata_reviews` (eine Zeile je Ausgabe). Die Standardliste zeigt offene Fälle mit Schwere > 0, sortiert nach Schwere, dann Titel.

## Scan

`php artisan catalog:quality:scan` oder „Bestand neu prüfen“ auf der Seite bewertet alle Ausgaben. Der Scan schreibt **nur** in die Prüftabelle, nie in den Katalog, und ist wiederholbar:

- neue Probleme legen einen offenen Fall an,
- behobene Probleme schließen den Fall (`resolved`),
- „kein Handlungsbedarf“ bleibt, solange sich die Ausgabe nicht geändert hat (Prüfsumme), und öffnet sich sonst wieder,
- ein gespeicherter Vorschlag wird verworfen, sobald sich die Ausgabe ändert.

## Vorschläge

Ein Vorschlag wird erst beim Öffnen eines Falls geholt und am Fall gespeichert; „Vorschlag neu abfragen“ holt ihn erneut. Quellen:

1. **DNB über die gespeicherte DNB-ID** (`idn=<ID>`): treffsicherer als die ISBN.
2. **DNB über die ISBN**, wenn es keine DNB-ID gibt und die DNB genau einen Datensatz mit passender ISBN liefert.
3. **Lokale Bereinigung** ohne Quelle (Steuerzeichen und zerlegte Zeichen entfernen), auch wenn die DNB nicht erreichbar ist.

Je Feld gibt es eine Art:

| Art | Bedeutung | vorausgewählt |
|---|---|---|
| ergänzt | Feld ist leer, die Quelle hat einen Wert | ja |
| korrigiert | Wert ist beschädigt und die Quelle ist nachweislich sein Ursprung | ja |
| bereinigt | lokale Bereinigung ohne Quelle | ja |
| Person ergänzen | fehlende verantwortliche Person | nur, wenn die Ausgabe bisher keine hat |
| Name korrigiert | beschädigter Name einer vorhandenen Person | ja (betrifft alle Titel dieser Person; die Seite nennt die Zahl) |
| abweichend | beide Seiten gefüllt und verschieden | nie; nur in einem eingeklappten Bereich |

„Nachweislich Ursprung“ heißt: Der DNB-Wert wird in seine zerlegte Form gebracht und jedes Kombinationszeichen durch `?` ersetzt. Ergibt das genau den gespeicherten Wert (`München` → `Mu?nchen`), ist die Korrektur belegt. Geraten wird nie.

Schutzregeln:

- Eine vorhandene ISBN wird nie ersetzt.
- Passt der Titel der Quelle nicht zur Ausgabe oder weicht die ISBN ab, erscheint eine Warnung und **nichts** ist vorausgewählt.
- DNB-Treffer zu einer ISBN werden verworfen, wenn ihre ISBN nicht zur gesuchten passt (die DNB liefert bei vertippter Prüfziffer sonst einen anderen Titel).
- Verlage, Drucker und Vertrieb (`pbl`, `prt`, `dst`, …) werden nicht als Verantwortliche vorgeschlagen.
- **Nie angefasst:** lokale Klassifikation, Mindestalter, Exemplare, Signaturen, interne Notizen, Cover und Legacy-Felder. Welche Felder vorgeschlagen werden dürfen, steht in `MetadataFields`.

## Übernehmen

Die Seite überträgt nur die Schlüssel der angehakten Zeilen. `ApplyMetadataProposalAction` schreibt ausschließlich die im Vorschlag **gespeicherten** Werte, nie Werte aus der Anfrage:

- eine Transaktion; scheitert eine Änderung, wird nichts geschrieben,
- Prüfung der Prüfsumme: Hat jemand die Ausgabe seit dem Vorschlag geändert, wird nichts übernommen und der Vorschlag neu geholt,
- die Herkunft (`metadata_source`, DNB-ID, Permalink) wird nur ergänzt, wenn sie fehlt; eine vorhandene Quellenangabe (z. B. `vdbs-legacy`) bleibt,
- Verantwortliche gehen über `ContributorResolver`: GND-ID zuerst, sonst nur bei eindeutigem Namenstreffer,
- Protokoll im Fall (`history`): wer, wann, Feld, alter und neuer Wert.

Nach dem Übernehmen oder Abweisen springt die Seite zum nächsten offenen Fall. „Überspringen“ lässt einen Fall offen.

## Betrieb

- Migration: `php artisan migrate`, danach einmal `php artisan catalog:quality:scan`.
- Nach Importen oder größeren Änderungen den Scan erneut ausführen.
- Die DNB-Abfrage braucht keinen Zugangsschlüssel. Bei Ausfall bleibt die Seite benutzbar (lokale Bereinigung).
- Eine ISBN mit falscher Prüfziffer (z. B. die Demo-ISBN von „Momo“) führt dazu, dass die DNB keinen passenden Datensatz liefert; die Seite weist darauf hin.

## Tests

- `CatalogMetadataQualityTest`: Textwerkzeuge, Bewertung je Problemart, Scan (idempotent, schließen, wieder öffnen, Abweisen), Rollback der Migration.
- `CatalogMetadataProposalTest`: Vorschlagsarten, Plausibilität, ISBN-Rückfall, Ausfall, Verantwortliche, Übernahme (nur Ausgewähltes, Whitelist, Prüfsumme, Rollback, GND, Herkunft).
- `CatalogQualityWorkflowTest`: Rechte, Liste und Filter, Vorschlag beim Öffnen (nur einmal abgefragt), Übernehmen, Abweisen, Überspringen, Fehlerfälle.
- `BibliographicLookupTest`: DNB-ID-Abfrage, ISBN-Filter, ISBN-10/13, Verlage ausgeschlossen.

Alle Tests laufen offline (`Http::fake`, Hilfsklasse `Tests\Support\DnbRecordXml`).
