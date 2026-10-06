# Nächste Schritte — BiblioCollect

0. Vorab `php artisan catalog:quality:propose --limit=800` ausführen und danach unter `/betrieb/katalog/qualitaet/sicher` die eindeutigen Vorschläge gesammelt prüfen und übernehmen; der Rest bleibt Einzelprüfung.
1. Den Bestand über `/betrieb/katalog/qualitaet` abarbeiten (Fälle mit Mangel zuerst): Vorschläge der DNB bzw. lokale Bereinigungen prüfen und bestätigen, siehe `docs/CATALOG_QUALITY_REVIEW.md`. Die Seite löst die frühere Idee einer `--apply`-Neuanreicherung ab: schreibfrei bis zur Bestätigung, saubere Werte werden nie überschrieben, jede Änderung ist vorab sichtbar und protokolliert.
2. Nach dem Abarbeiten `catalog:quality:scan` erneut laufen lassen und prüfen, ob noch Fälle ohne DNB-ID und ohne gültige ISBN übrig sind. Diese bleiben manuelle Prüffälle; aus Zeichenfolgen wie `Mu?nchen` wird niemals geraten.
3. Die Qualitätsseite meldet jetzt auch ISBNs mit falscher Prüfziffer. Diese Fälle von Hand anhand des Buchs berichtigen; gültige, aber falsch zugeordnete ISBNs (z. B. eine „Matilda“-Ausgabe mit der ISBN von „Mr. Fox“) fallen nur über das Cover auf.
4. Den Betrieb einrichten: dauerhaft laufender Queue Worker (`php artisan queue:work`), `php artisan schedule:run` jede Minute (Cover-Nachholung täglich 03:30, Ablauf von Abholfristen täglich 04:00), `php artisan storage:link` und ein kostenloser Google-Books-API-Key als Fallback (danach einmal `catalog:covers:queue --retry-missing`). Die Nutzungsbedingungen für das Speichern von Google-Bildern vorher prüfen.
5. SMTP-Zugangsdaten für den Mailversand eintragen (bis dahin schreibt `MAIL_MAILER=log` die Erinnerungen ins Log) und prüfen, ob Erinnerungen abbestellbar sein sollen.
6. Entschieden: keine Mahnungen und Gebühren. Erinnerungen per E-Mail und die Klassenlisten (`/betrieb/klassenlisten`) reichen; vor dem Betrieb einmal `privacy:anonymize --dry-run` ausführen und die Zahlen prüfen.
7. Den Ausleihkonten-Import (`docs/PATRON_IMPORT.md`) mit der echten Schülerliste ausprobieren; dafür zuerst Schuljahr und Klassen anlegen und aktivieren.
8. Die formatunabhängige Import-Pipeline bei einem späteren MARC21-Schritt über einen weiteren Source-Adapter wiederverwenden. Die DNB-/GND-/Quellenfelder und der Qualitätsaudit bilden dafür bereits eine fachliche Zielstruktur.
9. Import-Mappings und spätere Quellen müssen weiterhin das offene `role_key`-Modell respektieren; Medientyp und Sprachcode bleiben offene Vokabulare mit schonender Normalisierung.

10. Vor dem Betrieb auf einem Webspace die Voraussetzungen in `docs/HOSTING_SHARED.md` beim Anbieter prüfen (vor allem PHP 8.4). Ist PHP 8.4 dort nicht verfügbar, auf die VM warten.
11. Nach dem Einrichten `php artisan app:doctor` (bzw. die Prüfung auf `/_setup`) ausführen und `mail:test` mit den echten SMTP-Daten.

## Qualitäts-Gate

Vor jedem Commit:

- `php vendor/bin/pint --test`
- `php vendor/bin/phpstan analyse --no-progress --memory-limit=1G`
- `php artisan foundation:check`
- `php vendor/bin/pest`
- `npm run build`
- `git diff --check`

Zusätzlich für den Legacy-Katalogimport:

- phpMyAdmin-JSON-Envelope und direkte JSON-Row-Liste lesen können,
- `catalog:legacy:analyze` nachweislich ohne Katalog-Writes ausführen,
- `inventory_number` als Barcode übernehmen und katalogweite Barcode-Konflikte vor dem ersten Write blockieren,
- mehrere Exemplare derselben bibliografischen Ausgabe auf eine `Edition` gruppieren,
- gleiche ISBN bei widersprüchlichen Titeln **nicht** zusammenführen,
- DNB-RCN/Permalink, GND, Reihe, Verantwortlichkeitsangabe, Erscheinungsort, Auflage, Identifikatoren, Umfang, Inhaltsangabe, Schlagwörter, Zielgruppe und Altersangaben erhalten,
- `mediaTopicList` hierarchisch sowie `mediaSignatures.topic_ids` als strukturierte Zuordnung übernehmen,
- `is_available`, `loan_counter` und `last_loan_date` ausschließlich als Legacy-Historie erhalten und niemals aktuelle Circulation daraus ableiten,
- `0000`, `0000-00-00` und ungültige optionale Legacy-Werte schonend als Warnung behandeln,
- mögliche Zeichensatzartefakte melden, aber nicht erraten/korrigieren,
- Wiederholung idempotent über Legacy-IDs und Editions-Gruppierungsschlüssel halten,
- den gesamten Write als eine Transaktion ausführen,
- Demo-Seed und öffentliche Metadatenanzeige regressionssicher prüfen.

Zusätzlich für Änderungen an Circulation:

- `circulation.manage` für Schüler-AG Basis/Erweitert, Mitarbeiter:innen und Verwaltung prüfen,
- technische Administration, Schüler:innen und Lehrkräfte vom POS-Circulation-Workflow fernhalten,
- gesperrte, ausgeschiedene und archivierte Patrons blockieren,
- nicht aktive Copies blockieren,
- doppelte offene Ausleihe desselben Exemplars blockieren,
- Altersprüfung gegen Patron-Geburtsdatum und `Edition::minimum_age` prüfen,
- Fälligkeitsdatum bei Schließtagen auf den nächsten Öffnungstag verschieben,
- Ausleihe und Rückgabe transaktional mit `lockForUpdate()` absichern,
- Rückgabe ohne automatische Änderung des CopyStatus prüfen,
- nach Rückgabe eine erneute Ausleihe desselben Exemplars erlauben,
- im Patron-Arbeitsbereich ausschließlich offene Ausleihen anzeigen,
- Seed-Idempotenz und Migration-Rollback prüfen,
- öffentliche Verfügbarkeit nur aus Zählwerten und dem frühesten Rückgabedatum ableiten, nie Barcodes, Personen oder Ausleih-IDs ausgeben,
- Verlängerungen nur über `CirculationRuleEvaluator::renewalViolations()` erlauben (Höchstzahl, Überfälligkeit, Sperre, wartende Vormerkung) und die Frist nie verkürzen,
- Vormerkungen nur bei nicht verfügbarem Titel zulassen, Warteschlange nach `requested_at` abarbeiten, nicht berechtigte Personen überspringen und zurückgelegte Exemplare nur an die vorgemerkte Person ausleihen,
- Storno und Fristablauf geben ein zurückgelegtes Exemplar an die nächste Person weiter.

Zusätzlich für Änderungen am Erfassungsprozess und an externen Metadaten:

- `catalog.manage` prüfen; technische Administration und Schüler-AG Basis bleiben ausgeschlossen,
- vor dem Speichern in Schritt 5 nachweislich nichts in `Title`, `Edition`, `Contributor` und `Copy` schreiben,
- Barcode früh (Schritt 1) und beim Speichern prüfen; bei Konflikt vollständig zurückrollen,
- Schritte nicht überspringbar halten (Guards), abgeschlossene Vorgänge nicht doppelt speichern,
- zu vorhandener ISBN nur ein Exemplar ergänzen können,
- Verantwortliche über GND-ID wiederverwenden (`gnd_id` ist eindeutig),
- DNB-Ausfall, leere Treffer und SRU-Diagnosemeldungen als „nicht verfügbar“ behandeln, nie als Fehlerseite,
- Nutzereingaben in der DNB-Abfrage auf Wörter reduzieren,
- DNB-Daten auf NFC normalisieren (die DNB liefert zerlegte Umlaute),
- das Mindestalter niemals aus externen Daten vorbelegen,
- Cover nur über Queue-Jobs laden, Bildtyp am Inhalt prüfen, Google-Bildlinks nur von Google-Hosts akzeptieren,
- Tests offline halten (`Http::fake`, kein externer Request aus `phpunit.xml`).

Zusätzlich für Änderungen am Katalogimport:

- `catalog.import` ausschließlich für Mitarbeiter:innen und Verwaltung prüfen,
- Upload und Header-Erkennung mit mindestens Komma/Semikolon testen,
- Preview auf Null-Schreibzugriffe gegen `Title`, `Edition`, `Contributor`, `TitleContribution` und `Copy` prüfen,
- ISBN-, Sprach-, Medientyp- und CopyStatus-Normalisierung prüfen,
- doppelte und bereits vorhandene Barcodes als blockierende Konflikte prüfen,
- eindeutige ISBN-Wiederverwendung ohne Überschreiben bestehender Stammdaten prüfen,
- mehrere Zeilen derselben ISBN auf eine Edition mit mehreren Copies prüfen,
- Commit nur nach expliziter Bestätigung und vollständig transaktional prüfen,
- Reload eines Preview-Batches sowie den Importbericht prüfen,
- technische Administration und Schüler-AG Erweitert vom Import fernhalten.

Zusätzlich für Änderungen am öffentlichen Katalog:

- anonyme Suche ohne Login testen,
- mindestens Titel-, Contributor- und ISBN-Suche prüfen,
- erweiterte bibliografische Suchfelder (z. B. Reihe, GND, DNB-RCN, Schlagwörter) prüfen,
- Medientyp-/Sprachfilter und Bestandsfilter prüfen,
- einen Titel ohne Exemplare sowie einen Titel ohne aktive Exemplare prüfen,
- Seitengrößen 10, 20, 50 und 100 sowie die direkte Seiteneingabe in öffentlicher und interner Trefferliste prüfen,
- eine nicht unterstützte Seitengröße als Validierungsfehler prüfen, nicht als stille Korrektur,
- Erhalt der aktiven Filter beim Seitenwechsel prüfen,
- sicherstellen, dass öffentliche Seiten ausschließlich lokal gespeicherte Cover oder den gebündelten Platzhalter laden und `cover_source_reference` nicht ausgeben,
- sicherstellen, dass Copy-Barcodes, interne ULIDs, interne Notizen, Erwerbungspreise und Legacy-Historie nicht in öffentlichen Seiten ausgegeben werden.

Zusätzlich für Änderungen an der Katalogqualität:

- `catalog.manage` prüfen; technische Administration, Schüler:innen und Schüler-AG Basis bleiben ausgeschlossen,
- Scan, Ansehen und Vorschlagen schreiben nie in Katalogdaten, nur in `catalog_metadata_reviews`,
- nur Schlüssel aus dem gespeicherten Vorschlag übernehmen, nie Werte aus der Anfrage; Felder außerhalb von `MetadataFields` (Mindestalter, Klassifikation, Exemplare, Cover) bleiben unantastbar,
- Übernahme nur bei unverändertem Datenstand (Prüfsumme), vollständig in einer Transaktion und protokolliert,
- gefüllte, unbeschädigte Werte nie vorauswählen oder überschreiben; Abweichungen nur anzeigen,
- bei unpassendem Datensatz (Titel oder ISBN weicht ab) nichts vorauswählen,
- DNB-Treffer zur ISBN verwerfen, wenn ihre ISBN nicht zur gesuchten passt,
- „kein Handlungsbedarf“ gilt nur für den geprüften Stand und öffnet sich bei Änderungen wieder,
- Verlage, Drucker und Vertrieb nicht als Verantwortliche übernehmen.
