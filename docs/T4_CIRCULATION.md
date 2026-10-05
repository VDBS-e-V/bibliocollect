# T4 Circulation — v0.5.0 Grundworkflow

## Ziel

T4 v0.5.0 führt den ersten produktiven Circulation-Slice ein: physische Exemplare werden an aktive Ausleihkonten ausgeliehen und wieder zurückgegeben. Die Regeln liegen zentral im `Circulation`-Modul; Catalog und Patrons bleiben fachlich eigenständig.

Nicht Teil dieses Schritts sind Verlängerungen, Vormerkungen, Gebühren, Mahnungen, öffentliche Verfügbarkeitsanzeigen oder ein Zugriff auf abgeschlossene Lesehistorien in der POS-Oberfläche.

## Berechtigung

Das Fachrecht lautet `circulation.manage`.

Es wird vergeben an:

- Schüler-AG Basis,
- Schüler-AG Erweitert,
- Mitarbeiter:innen,
- Verwaltung.

Nicht berechtigt sind Schüler:innen, Lehrkräfte und technische Administration. Technische Administration erhält insbesondere keinen indirekten Zugriff auf Patron- oder Lesedaten.

Die fachliche Laufzeitlogik prüft nur Permission-Keys; Rollennamen werden nicht in Controllern oder Domain-Actions verzweigt.

## Datenmodell

`circulation_loans` verwendet ULIDs und speichert:

- `patron_id`,
- `copy_id`,
- `checked_out_at`,
- `due_on`,
- `returned_at`,
- handelnde Benutzer:innen für Ausleihe und Rückgabe.

Ein Datensatz wird bei Rückgabe nicht gelöscht. Eine offene Ausleihe ist ausschließlich über `returned_at IS NULL` definiert.

Das Circulation-Modul hängt laut `module.json` von Patrons, School und Catalog ab. Umgekehrt erhalten Catalog und Patrons keine Abhängigkeit zu Circulation.

## Ausleihregeln

`CirculationRuleEvaluator` bündelt die Ausleihentscheidung. Eine Ausleihe wird blockiert, wenn mindestens eine Regel verletzt ist:

- Patronstatus ist nicht `active`,
- das Ausleihkonto ist gesperrt,
- der CopyStatus ist nicht `active`,
- das Exemplar besitzt bereits eine offene Ausleihe,
- das Editions-Mindestalter ist noch nicht erreicht,
- für eine notwendige Altersprüfung fehlt ein gültiges Geburtsdatum.

Das Mindestalter wird gegen das vollständige Patron-Geburtsdatum und die Geschäftszeit in `Europe/Berlin` geprüft.

## Fälligkeit

Die Standardleihfrist wird über `config/circulation.php` festgelegt und beträgt zunächst 14 Kalendertage.

Fällt das rechnerische Fälligkeitsdatum auf einen geschlossenen Bibliothekstag, verschiebt `LoanDueDateService` die Fälligkeit mit dem bestehenden `SchoolCalendarService` auf den nächsten Öffnungstag.

## Transaktionen und Sperren

Ausleihe und Rückgabe laufen vollständig in Datenbanktransaktionen.

Bei der Ausleihe werden Patron, Exemplar und Edition pessimistisch mit `lockForUpdate()` gelesen. Die offene Ausleihe für dasselbe Exemplar wird innerhalb derselben Transaktion erneut geprüft.

Bei der Rückgabe wird zuerst das Exemplar und anschließend der Loan mit `lockForUpdate()` gesperrt. Die einheitliche Sperrreihenfolge zum Checkout reduziert Deadlock-Risiken; eine doppelte Rückgabe wird als Fachkonflikt abgelehnt.

Der CopyStatus wird bei Rückgabe nicht automatisch verändert. `damaged`, `lost` und `withdrawn` bleiben bewusste Katalogzustände und werden nicht durch Circulation überschrieben.

## POS-Oberfläche

Der Circulation-Workflow liegt im vorhandenen Patron-Arbeitsbereich:

- Barcode eingeben,
- Ausleihe bestätigen,
- offene Ausleihen mit Titel, Barcode und Fälligkeit sehen,
- offene Ausleihe zurückgeben.

Es wird bewusst keine Liste bereits zurückgegebener Medien angezeigt. Damit erhält die Schüler-AG die für den laufenden Betrieb notwendigen Daten, aber keine allgemeine Lesehistorie.

## Demo-Seed

`CirculationDemoSeeder` ergänzt reproduzierbar:

- eine offene Ausleihe von `BC-MOMO-001` an `S-10001`,
- eine bereits zurückgegebene historische Ausleihe von `BC-PRINZ-001` an `L-20001`.

Der Seeder ist idempotent und verweigert die direkte Ausführung in `production`.

## Tests

Der T4-Slice deckt automatisiert ab:

- Permission-Grenzen,
- erfolgreichen Checkout und Return,
- gesperrte sowie nicht aktive Patrons,
- nicht aktive Exemplare,
- bereits ausgeliehene Exemplare,
- Altersgrenzen inklusive Geburtstag,
- Fälligkeitsverschiebung auf den nächsten Öffnungstag,
- persistente Historie ohne Anzeige zurückgegebener Titel im POS,
- erneute Ausleihe nach Rückgabe,
- Transaktions-/Row-Lock-Vorgaben,
- Migration-Rollback,
- Demo-Seed und Idempotenz.

## Bewusst später

Folgende Punkte bleiben nach v0.5.0 offen:

- öffentliche Anzeige „derzeit verfügbar“ auf Basis offener Loans,
- Verlängerung und Verlängerungsregeln,
- titelbezogene Vormerkungen,
- Pickups,
- Mahnungen und Gebühren,
- differenzierte Einsicht in historische Ausleihen.
