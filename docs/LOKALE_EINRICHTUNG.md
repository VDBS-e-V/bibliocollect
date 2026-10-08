# Lokale Einrichtung: BiblioCollect auf dem eigenen Rechner

Diese Anleitung bringt BiblioCollect auf einem Windows-Rechner zum Laufen, zum Ausprobieren, zum Üben und zum Vorbereiten der Echtdaten. Für den Betrieb auf dem Webspace gilt `docs/GO_LIVE.md`; dort steht auch der Weg ohne Konsole.

## 1. Voraussetzungen

| Was | Version | Prüfen mit |
| --- | --- | --- |
| PHP | 8.4 oder neuer, mit den Erweiterungen `sqlite3`, `pdo_sqlite`, `mbstring`, `zip`, `curl`, `intl` oder `fileinfo`, `openssl` | `php -v`, `php -m` |
| Composer | 2.x | `composer -V` |
| Node.js | 20 oder neuer (mit npm) | `node -v` |
| Git | beliebig | `git --version` |

Eine Datenbank-Software ist nicht nötig: Lokal läuft BiblioCollect mit SQLite, einer einzelnen Datei (`database/database.sqlite`). Ein Webserver ist auch nicht nötig, PHP bringt einen eigenen mit.

## 2. Installieren

Im Projektordner (zum Beispiel `C:\xampp\htdocs\bibliocollect`) eine Konsole öffnen:

```text
composer setup
```

Das erledigt in einem Schritt: Abhängigkeiten laden, `.env` aus der Vorlage anlegen, die SQLite-Datei erzeugen, den Anwendungsschlüssel erzeugen, die Datenbank anlegen und die Oberfläche (CSS und JavaScript) bauen.

Danach in der `.env` prüfen und anpassen:

```text
APP_URL=http://127.0.0.1:8000
SETUP_TOKEN=<langer Zufallstext>
```

Den Zufallstext erzeugst du mit `php -r "echo bin2hex(random_bytes(24));"` (mindestens 24 Zeichen). `SETUP_TOKEN` schaltet die Einrichtungsseite frei; ohne Wert gibt es sie nicht. Alle anderen Vorgaben (SQLite, Mails in die Logdatei, Sitzungen und Warteschlange in der Datenbank) passen lokal so.

## 3. Starten

```text
php artisan serve
```

Die Anwendung läuft dann unter <http://127.0.0.1:8000>. Beenden mit `Strg + C`.

Alternativ startet `composer dev` Server, Warteschlange und Entwicklungs-Oberfläche zusammen. Änderungen an CSS oder JavaScript baust du mit `npm run build` neu.

## 4. Ersteinrichtung ohne Daten

1. **Einrichtungsseite öffnen:** <http://127.0.0.1:8000/_setup>. Auf der Seite zuerst „Migrationen ausführen“, dann „Verwaltungskonto anlegen“ (Name, E-Mail-Adresse, Passwort mit mindestens 12 Zeichen). Jede Aktion verlangt den `SETUP_TOKEN` aus der `.env`. Mit „Einrichtung prüfen“ siehst du, was noch fehlt.
2. **Anmelden** unter <http://127.0.0.1:8000/anmelden> mit dem neuen Konto.
3. **Schuljahr und Klassen:** Verwaltung → Schule → Schuljahr anlegen und aktivieren, danach „Alle Klassen der Schule anlegen“ (47 Klassen).
4. **Öffnungszeiten und Schließtage:** Verwaltung → Öffnungszeiten.
5. **Regeln:** Verwaltung → Regeln (Leihfristen, Höchstzahlen, Vormerken, Erinnerungen).
6. **Seiten:** Verwaltung → Seiten (Impressum, Datenschutz, Barrierefreiheit).
7. **Benutzerkonten:** Verwaltung → Benutzerkonten, Mitarbeitende einladen und Rollen vergeben.
8. Danach `SETUP_TOKEN` in der `.env` wieder **leeren**.

## 5. Bestand und Personen einspielen

Alles geht im Browser, ohne Konsole:

- **Altbestand** (Katalog aus dem alten BiblioCollect): Bestand → Altbestand übernehmen (<http://127.0.0.1:8000/betrieb/katalog/altbestand>). Die phpMyAdmin-JSON-Exporte `mediaList`, `mediaTopicList` und `mediaSignatures` hochladen, **prüfen**, dann **übernehmen**. Darunter die Tabelle `bookWishes` für die alten Buchwünsche.
- **Neue Medien:** Medium erfassen (ISBN-Suche) oder Katalog importieren (CSV).
- **Klassen mit Personen:** Ausleihkonten → Klassendaten importieren. Die Excel-Vorlage herunterladen, von den Klassenleitungen ausfüllen lassen, pro Klasse hochladen, Vorschau prüfen, bestätigen. Ausweise gibst du später klassenweise aus, wenn die Schüler:innen vor dir stehen.
- **Einzelne Personen:** Ausleihkonto anlegen, mit Ausweis.

## 6. Mails, Cron und Warteschlange lokal

- **Mails** werden lokal nicht verschickt, sondern in `storage/logs/laravel.log` geschrieben (`MAIL_MAILER=log`). Dort findest du Bestätigungs-, Erinnerungs- und Beleg-Mails. Echten Versand probierst du mit SMTP-Daten in der `.env` und `php artisan mail:test deine@adresse.de`.
- **Zeitplan und Warteschlange** (Sicherung, Fristen, Erinnerungen) laufen nur, wenn etwas sie anstößt. Zum Ausprobieren: Verwaltung → Systemzustand → bei der gewünschten Aufgabe „Einmal ausführen“, oder in der Konsole `php artisan app:cron`.

## 7. Sicherung, Zurücksetzen, Wiederherstellen

- **Sicherung:** `php artisan backup:database` (liegt unter `storage/app/backups`) oder Verwaltung → Systemzustand.
- **Wiederherstellen:** `php artisan backup:restore <Datei>`. Das ersetzt alle vorhandenen Daten.
- **Demo- und Testdaten entfernen** (Personen, Ausleihen, Konten, Klassen, Protokoll; der Altbestand bleibt): `php artisan app:launch-reset`. Vorher wird eine Sicherung erstellt. Danach gibt es kein Konto mehr; du legst es wie in Abschnitt 4 neu an.
- **Alles von vorn** (löscht die gesamte lokale Datenbank): `php artisan migrate:fresh`, danach wieder ab Abschnitt 4. Mit `php artisan migrate:fresh --seed` entsteht stattdessen ein Entwicklungsstand mit Demodaten (siehe `docs/DEVELOPMENT_SEED.md`; nie auf dem Webspace).
- **Bereinigte Datenbank auf den Webspace bringen:** Sicherung erstellen und in phpMyAdmin importieren, siehe `docs/GO_LIVE.md`. Einfacher ist der Neuaufbau direkt auf dem Webspace über die Weboberfläche.

## 8. Prüfen

```text
composer quality
```

führt Stilprüfung (Pint), Codeanalyse (PHPStan), Strukturprüfung und alle Tests (Pest) aus. Einzeln: `vendor/bin/pest`, `vendor/bin/phpstan analyse`, `vendor/bin/pint --test`, `php artisan foundation:check`.

## 9. Wenn etwas nicht klappt

| Problem | Lösung |
| --- | --- |
| `/_setup` zeigt 404 | `SETUP_TOKEN` in der `.env` fehlt oder ist kürzer als 24 Zeichen. Danach `php artisan config:clear`. |
| Anmeldung schlägt fehl oder „Zu viele Anmeldeversuche“ | `php artisan cache:clear`. Passwort vergessen: `/_setup` → „Zugang wiederherstellen“ (E-Mail und neues Passwort, mindestens 12 Zeichen). |
| Seite ohne Gestaltung | `npm install --ignore-scripts`, dann `npm run build`. |
| „No application encryption key“ | `php artisan key:generate`. |
| Änderungen an `.env` wirken nicht | `php artisan config:clear`. |
| Datenbank-Fehler „no such table“ | `/_setup` → „Migrationen ausführen“ oder `php artisan migrate`. |
| Excel-Import: Datei nicht lesbar | PHP-Erweiterung `zip` aktivieren (`extension=zip` in der `php.ini`). |
| Hochladen großer Dateien schlägt fehl | In der `php.ini` `upload_max_filesize` und `post_max_size` erhöhen (zum Beispiel 64M), PHP neu starten. |

Die `.env`-Dateien gleichst du mit `php artisan env:check` und `php artisan env:sync --backup` ab; Sicherungen liegen in `.foundation/env-backups/` und gehören nie ins Repository.
