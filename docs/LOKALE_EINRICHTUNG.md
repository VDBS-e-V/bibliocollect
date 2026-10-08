# Lokale Einrichtung: BiblioCollect auf dem eigenen Rechner

Diese Anleitung bringt BiblioCollect auf einem Windows-Rechner zum Laufen, zum Ausprobieren, zum Üben und zum Vorbereiten der Echtdaten. Für den Betrieb auf dem Webspace gilt `docs/GO_LIVE.md`; dort steht auch der Weg ohne Konsole.

Die Anleitung beginnt bei einem Rechner, auf dem noch nichts installiert ist (Abschnitt 0). Wer PHP, Composer, Node.js und Git schon hat, springt zu Abschnitt 1.

## 0. Rechner vorbereiten (noch nichts installiert)

Alle Befehle unten tippst du in **PowerShell**: Windows-Taste drücken, „PowerShell“ eingeben, öffnen. Nach jeder Installation ein **neues** PowerShell-Fenster öffnen, sonst kennt es die neuen Programme noch nicht.

### 0.1 winget prüfen

Windows 10 und 11 bringen den Paketmanager `winget` meist mit:

```text
winget --version
```

Kommt eine Versionsnummer, geht es mit den Befehlen weiter. Sonst installierst du im Microsoft Store die „App-Installer“ (oder nimmst bei jedem Programm den Download-Link).

### 0.2 Git

```text
winget install --id Git.Git -e
```

Oder von <https://git-scm.com/download/win> herunterladen und mit den Standard-Einstellungen installieren. Prüfen (neues Fenster): `git --version`.

### 0.3 PHP 8.4 oder neuer

```text
winget install --id PHP.PHP.8.4 -e
```

Findet winget die Kennung nicht, lädst du PHP von <https://windows.php.net/download> („VS17 x64 Non Thread Safe“, Zip), entpackst es nach `C:\php` und fügst `C:\php` zur Umgebungsvariable `Path` hinzu (Windows-Suche: „Umgebungsvariablen für dieses Konto bearbeiten“ → `Path` → Neu).

Danach die Konfiguration anlegen und die benötigten Erweiterungen einschalten. Den Ordner findest du mit `where.exe php` (das ist der Ordner mit `php.exe`); im Beispiel `C:\php`:

```text
cd C:\php
Copy-Item php.ini-development php.ini
(Get-Content php.ini) -replace '^;\\s*extension_dir = "ext"', 'extension_dir = "ext"' | Set-Content php.ini
(Get-Content php.ini) -replace '^;extension=(curl|fileinfo|gd|intl|mbstring|openssl|pdo_sqlite|sqlite3|zip)\\s*$', 'extension=$1' | Set-Content php.ini
```

Prüfen (neues Fenster):

```text
php -v
php -m
```

`php -v` muss mindestens 8.4 zeigen, und `php -m` muss diese Namen enthalten: `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_sqlite`, `sqlite3`, `zip` (dazu wie üblich `ctype`, `json`, `tokenizer`, `xml`). Fehlt einer, die Zeile `extension=…` in `php.ini` von Hand freischalten (Semikolon am Anfang entfernen). Außerdem in der `php.ini` die Upload-Grenzen für große Importdateien erhöhen: `upload_max_filesize = 64M` und `post_max_size = 64M`.

Hast du XAMPP, liegt PHP unter `C:\xampp\php`; prüfe dort mit `php -v` die Version (XAMPP-Stände vor PHP 8.4 reichen nicht).

### 0.4 Composer (PHP-Paketmanager)

```text
winget install --id Composer.Composer -e
```

Oder den Installer von <https://getcomposer.org/download/> ausführen (er fragt nach dem Ort von `php.exe`). Prüfen (neues Fenster): `composer -V`. Meldet Composer später ein SSL-Zertifikatsproblem, hilft die Datei `cacert.pem` von <https://curl.se/docs/caextract.html>: im Ordner `C:\php` ablegen und in `php.ini` `curl.cainfo = "C:\php\cacert.pem"` und `openssl.cafile = "C:\php\cacert.pem"` eintragen.

### 0.5 Node.js (für das Bauen der Oberfläche)

```text
winget install --id OpenJS.NodeJS.LTS -e
```

Oder von <https://nodejs.org> die LTS-Version installieren. Prüfen (neues Fenster): `node -v` und `npm -v`.

### 0.6 Code holen

Ein Ordner für die Projekte, zum Beispiel `C:\Projekte`:

```text
mkdir C:\Projekte
cd C:\Projekte
git clone https://github.com/VDBS-e-V/bibliocollect.git
cd bibliocollect
```

Das Repository ist nicht öffentlich. Beim ersten Mal öffnet Git ein Anmeldefenster für GitHub; dein GitHub-Konto braucht Zugriff auf die Organisation VDBS-e-V. Alternativ lädst du auf der GitHub-Seite des Repositorys über „Code → Download ZIP“ den Stand als Zip herunter und entpackst ihn nach `C:\Projekte\bibliocollect`; dann gibt es aber keine Versionsverwaltung und keine Updates per `git pull`.

Ein Editor ist optional; für Textdateien wie die `.env` reicht der Windows-Editor, bequemer ist Visual Studio Code (`winget install --id Microsoft.VisualStudioCode -e`).

### 0.7 Alles zusammen prüfen

```text
git --version
php -v
composer -V
node -v
npm -v
```

Alle fünf Befehle müssen eine Version nennen. Dann geht es mit Abschnitt 1 und 2 weiter; die Befehle dort tippst du im Projektordner (`C:\Projekte\bibliocollect`).

## 1. Voraussetzungen

| Was | Version | Prüfen mit |
| --- | --- | --- |
| PHP | 8.4 oder neuer, mit den Erweiterungen `sqlite3`, `pdo_sqlite`, `mbstring`, `zip`, `curl`, `fileinfo`, `openssl` | `php -v`, `php -m` |
| Composer | 2.x | `composer -V` |
| Node.js | 20 oder neuer (mit npm) | `node -v` |
| Git | beliebig | `git --version` |

Wie du das auf einem leeren Rechner installierst, steht in Abschnitt 0. Eine Datenbank-Software ist nicht nötig: Lokal läuft BiblioCollect mit SQLite, einer einzelnen Datei (`database/database.sqlite`). Ein Webserver ist auch nicht nötig, PHP bringt einen eigenen mit.

## 2. Installieren

Im Projektordner (zum Beispiel `C:\Projekte\bibliocollect`) PowerShell öffnen (im Explorer in den Ordner gehen, in die Adresszeile `powershell` tippen und Enter drücken):

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
