# Betrieb auf einem reinen Webserver (ohne SSH)

Antwort auf die Frage, ob BiblioCollect auf einem normalen Webspace (z. B. bei Strato) laufen kann: **Ja, wenn der Webspace ein paar Voraussetzungen erfüllt.** Die Anwendung braucht keinen eigenen Server, keinen dauerhaft laufenden Prozess und kein Node auf dem Server. Was vorbereitet ist: ein Release-Paket, eine Einrichtungsseite ohne SSH, Cron per Befehl oder per URL und Cover ohne Symlinks.

## 1. Voraussetzungen, die du beim Anbieter prüfen musst

Das kann ich von hier aus nicht sicher beurteilen, weil sich Tarife und Funktionen ändern. Bitte im Kundenbereich nachsehen:

| Voraussetzung | Warum | Wenn es fehlt |
|---|---|---|
| **PHP 8.4** wählbar (Laravel 13) | Mindestversion der Anwendung | Hier gibt es keinen Ausweg. Dann wartet man auf die VM oder wechselt den Tarif. |
| **MySQL/MariaDB**-Datenbank | Ausleihen, Katalog, Konten | SQLite geht nur bei einer einzelnen Person und nicht empfohlen. |
| Domain kann auf einen **Unterordner** zeigen (`…/bibliocollect/public`) | `.env`, Code und Daten dürfen nicht öffentlich erreichbar sein | Ersatz unten unter „Wenn die Domain nicht auf `public` zeigen kann“. |
| **HTTPS** (SSL-Zertifikat) | Anmeldung mit Passwort | nicht verhandelbar |
| **Cronjob** (Befehl oder URL) im Minutentakt, mindestens alle 5 Minuten | Erinnerungen, Cover laden, Fristen, Sicherung | Externer Dienst, der die URL `/_cron/<Token>` aufruft. |
| **SMTP**-Zugang | Erinnerungs-Mails | ohne: nur Logeinträge, keine Mails |
| Ausgehende Verbindungen zu `services.dnb.de`, `covers.openlibrary.org` | DNB-Abfrage und Cover | Katalogpflege ohne Abfrage, Cover nur manuell |
| PHP-Erweiterungen `mbstring`, `openssl`, `pdo_mysql`, `curl`, `fileinfo`, `xml`, `ctype`, `json`, `tokenizer` | Standard bei Laravel | Wird von `app:doctor` und der Einrichtungsseite geprüft |

Ohne SSH gibt es keinen Befehl `php artisan …`. Dafür sind die Einrichtungsseite und der Web-Cron da (siehe unten).

## 2. Paket bauen und hochladen

Auf deinem Rechner im Projektordner:

```
powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1
```

Das erzeugt `dist/bibliocollect-<Datum>.zip` (ca. 13 MB, mit gebauter Oberfläche und Abhängigkeiten ohne Entwicklungswerkzeuge, ohne `.env`, Datenbank, Cover und Tests). Entpacke es lokal und lade den Inhalt per FTP/SFTP in einen Ordner **außerhalb** des öffentlichen Bereichs, z. B. `bibliocollect/`. Das sind einige tausend kleine Dateien, ein FTP-Programm mit mehreren parallelen Verbindungen spart Zeit.

Die Domain zeigt auf `bibliocollect/public`.

### Wenn die Domain nicht auf `public` zeigen kann

1. Lege die Anwendung in einen Ordner **neben** dem öffentlichen Webordner, z. B. `bibliocollect/` und `htdocs/` (oder wie der Anbieter ihn nennt).
2. Kopiere den Inhalt von `bibliocollect/public/` in den öffentlichen Webordner.
3. Passe dort in `index.php` die beiden Pfade an, z. B. `__DIR__.'/../bibliocollect/vendor/autoload.php'` und `__DIR__.'/../bibliocollect/bootstrap/app.php'`.
4. Nach jedem Update auch `public/` neu kopieren (`build/` ändert sich).

## 3. `.env` anlegen

Kopiere `.env.shared-hosting.example` nach `.env` und trage ein:

- `APP_URL` (mit `https://`),
- `APP_KEY`: auf deinem Rechner `php artisan key:generate --show` ausführen und das Ergebnis eintragen,
- Datenbank (Host, Name, Benutzer, Passwort aus dem Kundenbereich),
- SMTP-Daten,
- `SETUP_TOKEN` und `CRON_TOKEN`: je ein zufälliger Text mit mindestens 24 Zeichen (z. B. aus dem Passwortmanager).

Wichtig: `SESSION_DRIVER=file` und `CACHE_STORE=file` lassen, bis die Datenbank angelegt ist. `CATALOG_COVER_DISK=covers` speichert Cover direkt in `public/covers`, das braucht keinen Symlink.

## 4. Einrichten ohne SSH

Rufe `https://deine-domain/_setup` auf (die Seite gibt es nur, solange `SETUP_TOKEN` gesetzt ist):

1. **Migrationen ausführen**: legt alle Tabellen an.
2. **Verwaltungskonto anlegen**: Name, E-Mail, Passwort (mindestens 12 Zeichen). Das geht nur einmal, solange es noch kein Konto mit der Rolle Verwaltung gibt.
3. **Einrichtung prüfen**: zeigt dieselbe Prüfung wie `php artisan app:doctor`.

Danach `SETUP_TOKEN` in der `.env` **leeren** (die Seite verschwindet), `SESSION_DRIVER=database` und `CACHE_STORE=database` setzen und dich anmelden.

## 5. Cron einrichten

Eine Zeile reicht, sie erledigt Zeitplan (Erinnerungen, Fristen, Sicherung, Anonymisierung) und Warteschlange (Cover-Downloads):

- **Cronjob mit Befehl** (wenn der Anbieter das erlaubt): `php /pfad/zu/bibliocollect/artisan app:cron`, jede Minute oder alle 5 Minuten. Den PHP-Pfad nennt der Anbieter (er muss PHP 8.4 sein).
- **Externer Cron-Dienst (nur URL)**: `https://deine-domain/_cron` jede Minute oder alle 5 Minuten aufrufen und den `CRON_TOKEN` als Header **`X-Api-Key: <CRON_TOKEN>`** (oder `Authorization: Bearer <CRON_TOKEN>`) mitschicken. GET und POST gehen. Das ist die bessere Wahl, weil der Key nicht in Server-Logs landet. Kann der Dienst keine Header, geht auch `https://deine-domain/_cron/<CRON_TOKEN>`. Ohne oder mit falschem Key antwortet die Adresse mit 404. Die Zeitplan-Aufgaben laufen dabei im selben Prozess und brauchen keinen zweiten PHP-Prozess. Die Antwort zeigt kurz, was gelaufen ist.

Ob alles läuft, zeigt `app:doctor` bzw. die Einrichtungsseite („Cronjob: zuletzt …“).

## 6. Mail prüfen

Ohne SSH: Erinnerungen laufen erst, wenn SMTP stimmt. Teste lokal mit denselben SMTP-Daten `php artisan mail:test deine@adresse.de`, bevor du sie auf den Webspace überträgst.

## 7. Daten sichern

- Die Datenbank sichert `backup:database` täglich um 01:30 Uhr nach `storage/app/backups` (14 Stück). Für MySQL entsteht eine `.sql.gz`-Datei mit den Daten; die Tabellenstruktur kommt beim Wiederherstellen aus den Migrationen. Lade die Dateien regelmäßig herunter, ein Backup auf demselben Server schützt nicht vor einem Serverausfall.
- Cover (`public/covers`) lassen sich jederzeit neu laden; sie sind kein Datenverlust.
- Zusätzlich bietet fast jeder Anbieter Datenbank-Backups im Kundenbereich.

## 8. Updates einspielen

1. Neues Paket bauen und entpacken.
2. Alles außer `.env` und `public/covers`, `storage/app/backups` hochladen und überschreiben (`vendor/` komplett ersetzen).
3. `https://deine-domain/_setup` kurz aktivieren (`SETUP_TOKEN` setzen), „Migrationen ausführen“, danach Token wieder leeren.
4. Falls ein Konfigurations-Cache angelegt wurde: `bootstrap/cache/*.php` löschen (die Anwendung baut ihn neu).

## 9. Einschränkungen gegenüber einem eigenen Server

- Cover werden nicht sofort, sondern beim nächsten Cron-Lauf geladen (maximal wenige Minuten).
- Lange Läufe (`catalog:quality:propose` mit 700 Fällen) brauchen eine Konsole. Auf dem Webspace holt man Vorschläge in kleinen Stücken über die Oberfläche oder holt sie vorab lokal (die Prüftabelle liegt in der Datenbank, die du lokal vorbereitet und dann nach MySQL exportiert hast; das ist aber aufwendig und nur für den Start sinnvoll).
- Webspace-Tarife haben oft Begrenzungen bei Laufzeit (`max_execution_time`) und Speicher; große CSV-Importe in Teilen hochladen.
- Mit einer VM (eigener Server) entfällt das alles: Dort laufen Queue Worker und Scheduler dauerhaft, und `php artisan` steht zur Verfügung. Der Umzug ist ein Datenbank-Export und derselbe Code.
