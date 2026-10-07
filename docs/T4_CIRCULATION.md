# T4 Circulation — v0.5.0 Grundworkflow

## Ziel

T4 v0.5.0 führt den ersten produktiven Circulation-Slice ein: physische Exemplare werden an aktive Ausleihkonten ausgeliehen und wieder zurückgegeben. Die Regeln liegen zentral im `Circulation`-Modul; Catalog und Patrons bleiben fachlich eigenständig.

Nicht Teil des ersten Schritts waren Verlängerungen, Vormerkungen, Gebühren, Mahnungen, öffentliche Verfügbarkeitsanzeigen oder ein Zugriff auf abgeschlossene Lesehistorien in der POS-Oberfläche. Öffentliche Verfügbarkeit, Verlängerungen und Vormerkungen mit Abholung folgen in v0.5.2 (siehe unten); Mahnungen, Gebühren und Historieneinsicht bleiben offen.

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

- eine offene Ausleihe von `BC-MOMO-001` an `384917`,
- eine bereits zurückgegebene historische Ausleihe von `BC-PRINZ-001` an `156803`.

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

## Öffentliche Verfügbarkeit (v0.5.2)

`CopyAvailabilityService` im Circulation-Modul leitet die Verfügbarkeit aus offenen Ausleihen ab; Catalog bleibt ohne Abhängigkeit zu Circulation, die Public-Oberfläche verbindet beide.

- Gezählt werden nur Exemplare mit Status `active`. Eine offene Ausleihe eines beschädigten oder verlorenen Exemplars mindert die Verfügbarkeit nicht.
- Verfügbar = aktiv − ausgeliehen − für eine Vormerkung zurückgelegt.
- Die öffentliche Liste und die Titelseite zeigen „Verfügbar“, „n von m Exemplaren verfügbar“, „Derzeit ausgeliehen“ oder „Für Vormerkung zurückgelegt“. Ohne aktive Exemplare bleibt es bei der bisherigen Bestandsaussage.
- Ist nichts verfügbar, steht dabei das früheste Rückgabedatum und die Zahl der Vormerkungen („Frühestens zurück am 24.12.2026 · 2 Vormerkungen“).
- Öffentlich erscheinen nur Zählwerte und das früheste Datum, nie Barcodes, Personen, Ausleih-IDs oder Bibliotheksnummern.
- Der Filter „Nur Titel mit aktiven Exemplaren“ bleibt ein Katalogfilter (aktiv, nicht verfügbar). Ein Filter „nur verfügbar“ würde eine Abhängigkeit des Katalogs zu Circulation brauchen und ist bewusst nicht Teil dieses Schritts.

## Verlängerungen (v0.5.2)

`RenewLoanAction` verlängert eine offene Ausleihe im Patron-Arbeitsbereich („Verlängern“). Die Regeln stehen zentral in `CirculationRuleEvaluator::renewalViolations()`, die Konfiguration in `config/circulation.php`.

- Höchstzahl: `max_renewals` (Standard 2). Der Zähler steht in `renewal_count`, dazu `last_renewed_at` und `last_renewed_by_user_id`.
- Dauer: `renewal_period_days`, sonst die Leihfrist. Die neue Fälligkeit zählt ab heute, aber nie vor der bisherigen Fälligkeit, damit eine frühe Verlängerung die Frist nicht verkürzt. Schließtage verschieben sie auf den nächsten Öffnungstag.
- Blockiert wird bei: Rückgabe bereits erfolgt, Konto nicht aktiv oder gesperrt, Exemplar nicht `active`, Höchstzahl erreicht, überfällig (`allow_overdue_renewal`, Standard `false`) und wenn jemand auf den Titel wartet (`ReservationBlockChecker`).
- Die Oberfläche nennt bei einer gesperrten Verlängerung den Grund. Transaktion, Sperrreihenfolge (Patron, Exemplar, Ausleihe) und Berechtigung `circulation.manage` wie bei Ausleihe und Rückgabe.

## Vormerkungen und Abholung (v0.5.2)

Vormerkungen sind titelbezogen (`circulation_reservations`, Status `waiting`, `ready`, `fulfilled`, `cancelled`, `expired`). Die Reihenfolge der Warteschlange ergibt sich aus `requested_at`.

- Erfassen im Patron-Arbeitsbereich über Exemplar-Barcode oder ISBN (`PlaceReservationAction`). Vorgemerkt wird nur, wenn kein Exemplar des Titels verfügbar ist; sonst wird direkt ausgeliehen. Abgelehnt werden außerdem: Konto nicht aktiv oder gesperrt, derselbe Titel bereits vorgemerkt oder ausgeliehen, mehr als `max_open_reservations` (Standard 5) offene Vormerkungen, Mindestalter bei allen Ausgaben nicht erreicht.
- Wird ein Exemplar zurückgegeben, rückt die erste wartende **berechtigte** Vormerkung nach (`ReservationQueueService`): Das Exemplar wird für sie zurückgelegt (`ready`), die Abholfrist beträgt `reservation_pickup_days` (Standard 7) und wird auf einen Öffnungstag gelegt. Gesperrte, ausgeschiedene oder zu junge Personen werden übersprungen und behalten ihren Platz.
- Die Rückgabemeldung nennt, für wen das Exemplar zurückzulegen ist. Die Seite „Vormerkungen“ (`/betrieb/vormerkungen`, Navigation „Vormerkungen“) listet abholbereite Exemplare mit Barcode und Frist sowie die Warteschlangen je Titel.
- Ein zurückgelegtes Exemplar kann nur die vorgemerkte Person ausleihen. Leiht sie stattdessen ein anderes Exemplar desselben Titels aus, wird die Vormerkung erfüllt und das zurückgelegte Exemplar geht an die nächste Person.
- Stornieren (`CancelReservationAction`) gibt ein zurückgelegtes Exemplar an die Nächsten weiter.
- `php artisan circulation:reservations:expire` (täglich 04:00 im Scheduler) beendet abgelaufene Abholfristen und gibt die Exemplare weiter. Ist das zurückgelegte Exemplar nicht mehr ausleihbar (beschädigt, verloren, ausgesondert), wartet die Person wieder in der Warteschlange.
- Öffentlich sichtbar ist nur die Zahl der Vormerkungen je Titel.
- Eine Selbstbedienung (Vormerken im Onlinekonto) gibt es noch nicht: Das Portal ist bisher ein Platzhalter. Benachrichtigungen („abholbereit“) folgen mit dem Reminders-Modul.

## Bewusst später

Folgende Punkte bleiben nach v0.5.0 offen:

- Selbstbedienung für Vormerkungen im Portal,
- Benachrichtigungen bei Abholbereitschaft und Fälligkeit,
- Mahnungen und Gebühren,
- differenzierte Einsicht in historische Ausleihen.
