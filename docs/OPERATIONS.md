# Betrieb: Befehle, Cron und Prüfung (v0.7.0)

Auf einem eigenen Server (VM) laufen Queue Worker und Scheduler dauerhaft. Auf einem Webspace ohne SSH ersetzt `app:cron` beides, siehe `docs/HOSTING_SHARED.md`.

## Ein Cronjob für alles

```
* * * * * php /pfad/zu/bibliocollect/artisan app:cron
```

`app:cron` führt fällige Zeitplan-Aufgaben aus und arbeitet danach die Warteschlange ab (maximal 40 Sekunden, ohne dauerhaft zu laufen). Es schreibt einen Zeitstempel, den `app:doctor` prüft. Wer einen eigenen Server hat, nimmt stattdessen `php artisan queue:work` (dauerhaft) und `php artisan schedule:run` jede Minute.

Zeitplan (`routes/console.php`):

| Zeit | Aufgabe |
|---|---|
| täglich 01:30 | `backup:database` |
| täglich 03:30 | `catalog:covers:queue --limit=50` |
| täglich 04:00 | `circulation:reservations:expire` |
| täglich 07:00 | `reminders:send` |
| sonntags 02:00 | `privacy:anonymize` |

## Prüfen und testen

| Befehl | Zweck |
|---|---|
| `php artisan app:doctor` | Prüft PHP, Erweiterungen, Schlüssel, Datenbank, Migrationen, Warteschlange, Cronjob, Schreibrechte, Cover-Speicher und Mail. Endet mit Fehlercode, wenn etwas fehlt. |
| `php artisan mail:test adresse@example.org` | Schickt eine Testnachricht (Hinweis, wenn der Mailer nur ins Log schreibt). |
| `php artisan foundation:check` | Prüft Module und Abhängigkeiten (Entwicklung). |

## Sicherung

`php artisan backup:database` legt unter `storage/app/backups` eine Sicherung an (SQLite: Datei, MySQL/MariaDB: `.sql.gz` mit den Daten) und behält die letzten 14 (`--keep=`). Wiederherstellen bei MySQL: Struktur mit `php artisan migrate --force` anlegen, dann die Datei in phpMyAdmin oder mit `mysql` einspielen. Sicherungen regelmäßig auch außerhalb des Servers ablegen.

## Arbeitsplatz im Alltag

- `/betrieb`: ein Scanfeld. Bibliotheksnummer öffnet das Ausleihkonto (dort ist der Barcode-Eingabe fokussiert), der Barcode eines ausgeliehenen Exemplars bucht die Rückgabe und nennt, für wen es zurückzulegen ist.
- Die Kacheln zeigen überfällige, heute fällige, abholbereite und wartende Ausleihen sowie offene Katalogfälle, dazu die Öffnungszeiten von heute.

## Auskunft, Abmeldung, Aufbewahrung

- Mitarbeiter:innen und Verwaltung: „Auskunft über gespeicherte Daten“ am Ausleihkonto (Ansicht zum Drucken und JSON-Download, im Protokoll vermerkt).
- Personen mit Onlinekonto: „Meine gespeicherten Daten herunterladen“ und „Erinnerungen per E-Mail“ an/aus unter „Mein Konto“.
- Aufbewahrung: alles wird nach drei Jahren anonymisiert (`docs/PRIVACY_AND_CLASS_LISTS.md`).
