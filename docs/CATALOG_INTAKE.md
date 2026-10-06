# Erfassungsprozess für neue Medien

Neue Medien werden unter `/betrieb/katalog/erfassen` in einem geführten Prozess aufgenommen. Er löst das lose Nebeneinander von Titel-, Ausgaben- und Exemplarformularen für den Regelfall ab; die bestehende Pflege (Titel, Verantwortliche, Ausgaben, Exemplare) bleibt unverändert für nachträgliche Korrekturen erhalten.

Der Einstieg ist der Link „Medium erfassen“ in der Katalogpflege. Das Recht ist `catalog.manage` (Schüler-AG Erweitert, Mitarbeiter:innen, Verwaltung). Technische Administration und Schüler-AG Basis haben keinen Zugriff.

## Ablauf

| Schritt | Inhalt |
|---|---|
| 1 Identifizieren | Barcode des Exemplars, dann ISBN **oder** Titel/Autor:in für die DNB-Abfrage – oder „ohne Abfrage manuell erfassen“ |
| 2 Treffer prüfen | Nur wenn nötig: DNB-Treffer, bereits vorhandene Ausgaben mit gleicher ISBN, manuelle Erfassung |
| 3 Titel & Ausgabe | Titel, Verantwortliche, Veröffentlichung, Einordnung – vorbefüllt, vollständig änderbar |
| 4 Exemplar | Signatur/Standort und Zustand im Bestand |
| 5 Prüfen & speichern | Zusammenfassung, danach „Speichern & nächstes Medium“ oder „Speichern & Titel öffnen“ |

Gegenüber dem Altsystem (sieben Schritte) sind Primärdaten, Veröffentlichungsdaten und Normen/Klassen in **einen** Schritt zusammengefasst. Felder, die nur Legacy-Historie sind (Ausleihzähler, letzte Ausleihe, Dateigröße), werden bei der Erfassung nicht abgefragt.

Wichtige Abkürzungen:

- Liefert die DNB zu einer ISBN genau einen Treffer und ist die ISBN noch nicht im Katalog, **entfällt Schritt 2**.
- Gibt es zur ISBN schon eine Ausgabe, wird angeboten, **nur ein weiteres Exemplar** zu ergänzen. Dann entfällt Schritt 3, und es entsteht kein doppelter Titel.
- Ist die DNB nicht erreichbar oder liefert nichts, geht es mit manueller Erfassung weiter; die ISBN bleibt vorbelegt.

## Grundsätze

- **Nichts wird vor Schritt 5 geschrieben.** Der Zwischenstand liegt nur in der Session der handelnden Person (`CatalogIntakeDraft`). „Vorgang abbrechen“ verwirft ihn.
- **Alles oder nichts.** `RecordCatalogIntakeAction` speichert Titel, Verantwortliche, Ausgabe und Exemplar in einer Transaktion. Wird der Barcode zwischenzeitlich anderweitig vergeben, wird nichts gespeichert und der Vorgang meldet das am Barcode-Feld.
- **Der Barcode wird früh geprüft** (Schritt 1) und beim Speichern erneut.
- **Externe Daten sind Vorschläge.** Sie werden nur übernommen, wenn die Person sie in Schritt 3 bestätigt. Das Mindestalter wird nie automatisch gesetzt, weil es die Ausleihe sperrt; die DNB-Zielgruppe landet nur als Freitext.
- **Herkunft bleibt nachvollziehbar:** `metadata_source`, `source_record_id` und `source_permalink` der Ausgabe zeigen auf den DNB-Datensatz.
- **Verantwortliche werden nicht doppelt angelegt:** Zuerst über die GND-ID (eindeutig), sonst nur bei genau einem exakten Namenstreffer ohne widersprüchliche GND-ID.

## DNB-Abfrage

`DnbLookupProvider` nutzt die frei zugängliche SRU-Schnittstelle der Deutschen Nationalbibliothek (`https://services.dnb.de/sru/dnb`, MARC21-XML, kein Key nötig). Er liegt hinter dem Vertrag `BibliographicLookupProvider`; weitere Quellen (z. B. andere Verbundkataloge) können denselben Vertrag erfüllen.

- ISBN-Abfrage: `num=<ISBN>`.
- Titel/Autor: Freitext wird in einzelne Wörter zerlegt (`tit=… and per=…`). Sonderzeichen entfallen, damit Eingaben die Abfrage nicht verändern können.
- Eine ausgefallene Quelle blockiert die Erfassung nie; `BibliographicLookupService` meldet sie als „nicht verfügbar“.

Zuordnung MARC21 → Katalog (`DnbMarcMapper`):

| MARC | Katalogfeld |
|---|---|
| 001 | `source_record_id`, Permalink `https://d-nb.info/<001>` |
| 020 $a | ISBN (13-stellig bevorzugt) |
| 245 $a/$n/$p, $b, $c | Haupttitel, Untertitel, Verantwortlichkeitsangabe |
| 100/110/700/710 | Verantwortliche; Rolle aus $4/$e, GND-ID aus $0 `(DE-588)`; 700 mit $t entfällt |
| 250 | Auflage/Ausgabe |
| 264 (ind2=1) / 260 | Verlag, Verlagsort, Jahr |
| 300 $a | Umfang |
| 041 $a/$h, sonst 008 | Sprache, Originalsprache (MARC → ISO 639-1) |
| 336/338, Leader | Medientyp (`book`, `ebook`, `audiobook`, `dvd`, `magazine`) |
| 490/830 | Reihe |
| 520 | Zusammenfassung |
| 650/651, 653 | Schlagwörter (653 mit Klammerpräfix wie `(Zielgruppe)`, `(BISAC …)` entfällt) |
| 653 `(Zielgruppe)ab …`, 385 | Zielgruppe (Freitext) |

Die DNB liefert Umlaute zerlegt (Unicode NFD). Der Mapper normalisiert auf NFC, sonst fände die Katalogsuche „Seeräuber“ nicht. Dafür wird `Normalizer` genutzt; ohne PHP-Erweiterung `intl` stellt das Symfony-Polyfill (`symfony/polyfill-intl-normalizer`) die Klasse bereit.

## Cover

Nach dem Speichern eines **neuen** Mediums mit ISBN wird `RefreshEditionCoverJob` eingereiht (`cover_status = pending`). Der Job fragt die Provider-Kette ab, prüft Typ (JPEG/PNG/WebP) und Größe am Inhalt und legt das Bild lokal ab. Webrequests laden nie extern.

| Quelle | Key | Hinweis |
|---|---|---|
| Open Library | nein | zuerst abgefragt; bei deutschen Titeln lückenhaft |
| Google Books | ja (kostenlos) | `CATALOG_COVER_GOOGLE_BOOKS_KEY`; ohne Key aus. Ohne Key lehnt Google Anfragen ab. Bildlinks werden nur von Google-Hosts geladen |

Betrieb:

- Queue Worker: `php artisan queue:work` (`QUEUE_CONNECTION=database`). Ohne Worker bleibt der Status `pending`.
- Bestandstitel nachholen: `php artisan catalog:covers:queue --limit=100`, bei Bedarf als Scheduler-Eintrag.
- `php artisan storage:link` beim Standard-Disk `public`.
- Quellen abschalten: `CATALOG_COVER_OPEN_LIBRARY=false`, Google-Key leeren.

Bei Google Books ist die Speicherung der Bilder durch deren Nutzungsbedingungen enger geregelt als bei Open Library. Vor produktiver Nutzung des Fallbacks sollte die Schule das prüfen.

## Tests

- `BibliographicLookupTest`: Mapping eines echten DNB-Datensatzes (Fixture `tests/Fixtures/dnb/`), Abfrageaufbau, Ausfall und Fehlerantworten.
- `CatalogCoverProvidersTest`: Open Library, Google Books, Fallback-Reihenfolge, Host-Prüfung.
- `CatalogIntakeWorkflowTest`: Rechte, alle Schritte, Schutz vor Überspringen, vorhandene Ausgabe, GND-Wiederverwendung, Rollback bei Barcode-Konflikt, Abbruch.

Die Tests laufen offline (`Http::fake`); in `phpunit.xml` ist der externe Cover-Abruf abgeschaltet.
