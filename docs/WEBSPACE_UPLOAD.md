# Auf den Webspace hochladen: alle Schritte

Von „lokal fertig“ bis „läuft auf Strato“, in genau dieser Reihenfolge. Die Hintergründe stehen in `docs/HOSTING_SHARED.md`, die Einrichtung in der Anwendung in `docs/GO_LIVE.md`. Die Bezeichnungen im Strato-Kundenbereich ändern sich gelegentlich; hier stehen sie so, wie sie üblich sind.

## A. Beim Anbieter vorbereiten (Strato-Kundenservicebereich)

1. **PHP-Version** des Pakets auf **8.4** stellen (Paket → PHP-Version). Ist 8.4 nicht wählbar, geht es nicht weiter.
2. **HTTPS** für die Domain aktivieren (SSL-Zertifikat).
3. **MySQL-Datenbank** anlegen (Datenbanken → MySQL-Datenbank anlegen). Notiere Host, Datenbankname, Benutzer, Passwort.
4. **Mail-Postfach** für die Absenderadresse anlegen (zum Beispiel `bibliothek@deine-domain.de`) und die SMTP-Daten notieren (Server, Port, Benutzer, Passwort).
5. **FTP/SFTP-Zugang** notieren (Server, Benutzer, Passwort).
6. **Ordner anlegen:** Lege per FTP im Hauptverzeichnis (oberhalb des öffentlichen Ordners, falls möglich) den Ordner `bibliocollect` an.
7. **Domain auf den Unterordner zeigen lassen:** Die (Sub-)Domain muss auf `bibliocollect/public` zeigen (Domains → Domainverwaltung → Verzeichnis ändern). Geht das nicht, gilt die Ausweichlösung in `docs/HOSTING_SHARED.md`, Abschnitt 2.

## B. Paket bauen (auf deinem Rechner)

1. PowerShell im Projektordner öffnen und ausführen:

   ```text
   powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1
   ```

2. Es entsteht `dist\bibliocollect-<Datum>.zip` (rund 13 MB, mit gebauter Oberfläche und Abhängigkeiten, ohne `.env`, Datenbank, Cover und Tests).
3. Die ZIP-Datei in einen leeren Ordner **entpacken**.

## C. Die Datei `.env` erstellen

Die `.env` enthält alle geheimen Einstellungen (Datenbank, Mail, Schlüssel). Sie ist **nicht** im Paket, du erstellst sie aus der Vorlage `.env.shared-hosting.example`, die im entpackten Ordner liegt.

### C.1 Versteckte Dateien und Endungen sichtbar machen

Im Windows-Explorer: Reiter **Ansicht → Anzeigen** und dort **Dateinamenerweiterungen** und **Ausgeblendete Elemente** einschalten. Sonst siehst du Dateien mit einem Punkt am Anfang (`.env`) nicht und Windows hängt beim Speichern leicht ein `.txt` an.

### C.2 Vorlage kopieren

PowerShell im entpackten Ordner öffnen (im Explorer in den Ordner gehen, in die Adresszeile `powershell` tippen, Enter). Dann:

```text
Copy-Item .env.shared-hosting.example .env
notepad .env
```

Der erste Befehl legt die Datei `.env` an (der Explorer erlaubt es meist nicht, eine Datei so zu benennen, PowerShell schon). Der zweite öffnet sie im Editor. **Wichtig:** Beim Speichern im Editor den Namen `.env` lassen und den Dateityp „Alle Dateien“ wählen, nie `.env.txt`.

### C.3 Die drei Schlüssel und den Anwendungsschlüssel bereithalten

Du hast die drei Zufallstexte schon. Zusätzlich brauchst du den **Anwendungsschlüssel**. Im selben PowerShell-Fenster (im entpackten Ordner):

```text
php artisan key:generate --show
```

Die Ausgabe `base64:…` (eine lange Zeile) kopieren. Das ist der Wert für `APP_KEY`. Er darf nach dem Start nie mehr geändert werden, sonst sind alle Sitzungen und verschlüsselten Daten ungültig. Lege ihn im Passwortmanager ab.

### C.4 Die Werte eintragen

Zeile für Zeile, **kein Leerzeichen** vor oder nach dem `=`. Werte, die Leerzeichen, `#` oder `$` enthalten (häufig bei Passwörtern), in **doppelte Anführungszeichen** setzen: `DB_PASSWORD="ab#cd$ef"`.

```text
APP_NAME=BiblioCollect
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:…                      ← aus C.3
APP_URL=https://deine-domain.de       ← mit https://, ohne Schrägstrich am Ende
APP_LOCALE=de
APP_FALLBACK_LOCALE=de
APP_TIMEZONE=Europe/Berlin
BUSINESS_TIMEZONE=Europe/Berlin
TRUSTED_PROXIES=*

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=…                             ← Datenbank-Host aus dem Strato-Kundenbereich
DB_PORT=3306
DB_DATABASE=…                         ← Datenbankname
DB_USERNAME=…                         ← Datenbank-Benutzer
DB_PASSWORD=…                         ← Datenbank-Passwort

SESSION_DRIVER=file                   ← vorerst so lassen
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=file                      ← vorerst so lassen
QUEUE_CONNECTION=database

FILESYSTEM_DISK=local
CATALOG_COVER_DISK=covers
CATALOG_COVER_OPEN_LIBRARY=true
CATALOG_COVER_GOOGLE_BOOKS_KEY=…      ← dein Google-Books-Schlüssel (leer lassen, wenn du noch keinen hast)

MAIL_MAILER=smtp
MAIL_SCHEME=null                      ← bei Port 587; bei Port 465 stattdessen smtps
MAIL_HOST=…                           ← SMTP-Server (bei Strato: smtp.strato.de)
MAIL_PORT=587                         ← oder 465
MAIL_USERNAME=…                       ← meist die volle Mail-Adresse des Postfachs
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS="bibliothek@deine-domain.de"   ← muss ein echtes Postfach bei deinem Anbieter sein
MAIL_FROM_NAME="${APP_NAME}"

SETUP_TOKEN=…                         ← Zufallstext 1
CRON_TOKEN=…                          ← Zufallstext 2
ALERT_EMAIL=deine@adresse.de          ← hier kommen Fehlermeldungen an
STATUS_TOKEN=…                        ← Zufallstext 3
```

Die Pfeile und Hinweise rechts sind **nicht** Teil der Datei, lösche sie. Zeilen, die nicht genannt sind, lässt du wie in der Vorlage. Die SMTP-Werte stehen im Kundenbereich bei den E-Mail-Einstellungen, die Datenbankwerte bei den MySQL-Datenbanken. Speichern und den Editor schließen.

### C.5 Gegenprobe

Öffne die `.env` noch einmal und prüfe: `APP_URL` beginnt mit `https://`, `APP_KEY` beginnt mit `base64:`, kein Wert steht in Pfeilen oder Klammern, die drei Tokens sind verschieden und je mindestens 24 Zeichen lang. Die Datei heißt exakt `.env` (Endung nicht `.txt`).

## D. Hochladen

1. FTP-Programm (zum Beispiel FileZilla) per **SFTP** mit den Zugangsdaten aus A verbinden. In FileZilla unter **Server → Versteckte Dateien anzeigen** einschalten, damit du `.env` und `.htaccess` siehst.
2. Rechts auf dem Server in den Ordner `bibliocollect` wechseln, links den entpackten Ordner öffnen.
3. Links **alles** markieren (Strg + A, einschließlich `.env`) und per Ziehen auf die rechte Seite hochladen. Es sind einige tausend kleine Dateien. Unter **Bearbeiten → Einstellungen → Übertragungen** die **maximale Anzahl gleichzeitiger Übertragungen** auf 5 bis 10 stellen. Bei der Frage nach vorhandenen Dateien „Überschreiben“ wählen.
4. Warte, bis die Warteschlange leer ist und im Reiter „Fehlgeschlagene Übertragungen“ nichts steht. Fehlgeschlagene Dateien erneut übertragen.
5. Prüfe auf dem Server: Im Ordner `bibliocollect` liegen `.env`, `artisan`, `app`, `vendor`, `public`, `storage`. Im Ordner `public` liegen `index.php`, `.htaccess` und `build`.
6. **Rechte setzen:** In FileZilla Rechtsklick auf `storage` → **Dateiberechtigungen** → Zahlenwert `775`, **„In Unterverzeichnisse einbeziehen“** und „Nur auf Verzeichnisse anwenden“. Dasselbe für `bootstrap/cache`.
7. **Prüfen:** `https://deine-domain.de/` im Browser öffnen. Es muss eine Seite erscheinen (auch eine Fehlerseite der Anwendung ist in Ordnung, nicht aber eine Dateiliste oder eine Anbieter-Fehlerseite). Zeigt der Browser eine Dateiliste, zeigt die Domain nicht auf `public` (siehe A.5).

## E. Einrichten im Browser

1. `https://deine-domain.de/_setup` öffnen. Die Seite erscheint nur, wenn `SETUP_TOKEN` in der `.env` steht.
2. **„Migrationen ausführen“:** Token (SETUP_TOKEN) eintragen, Knopf drücken. Es erscheint eine Liste der angelegten Tabellen ohne Fehler.
3. **„Verwaltungskonto anlegen“:** Token, dein Name, deine echte E-Mail-Adresse und ein Passwort mit mindestens 12 Zeichen. Das geht nur einmal.
4. **„Einrichtung prüfen“:** Es dürfen keine Fehler stehen. Warnungen zu Cron und Mail erledigst du in G und H.
5. **Danach die `.env` auf dem Server ändern** (in FileZilla Rechtsklick auf `.env` → Ansehen/Bearbeiten, speichern, hochladen bestätigen):
   - `SETUP_TOKEN=` **leeren** (nur das Gleichheitszeichen stehen lassen). Die Einrichtungsseite verschwindet.
   - `SESSION_DRIVER=database`
   - `CACHE_STORE=database`
6. `https://deine-domain.de/_setup` muss jetzt **404** zeigen.
7. Unter `https://deine-domain.de/anmelden` mit deinem Verwaltungskonto anmelden.

## F. Grundeinrichtung in der Anwendung

In dieser Reihenfolge:

1. **Verwaltung → Schule:** Schuljahr 2026/27 anlegen (1.8.2026 bis 31.7.2027) und aktivieren, dann **„Alle Klassen der Schule anlegen“** (47 Klassen).
2. **Verwaltung → Öffnungszeiten:** Wochentage, Zeiten und Schließtage (Ferien) eintragen.
3. **Verwaltung → Regeln:** Leihfristen, Höchstzahlen, Vormerken und Erinnerungen prüfen.
4. **Verwaltung → Seiten:** Impressum, Datenschutz und Barrierefreiheit prüfen und speichern.
5. **Verwaltung → Benutzerkonten:** Mitarbeitende einladen und Rollen vergeben.
6. **Bestand → Altbestand übernehmen:** Die JSON-Exporte `mediaList`, `mediaTopicList`, `mediaSignatures` aus dem alten System hochladen, **Prüfen**, dann **Übernehmen**. Danach `bookWishes` für die Buchwünsche. Ist ein Upload zu groß, beim Anbieter `upload_max_filesize` und `post_max_size` erhöhen.
7. **Ausleihkonten → Klassendaten importieren:** Excel-Vorlage an die Klassenleitungen geben, pro Klasse hochladen, Vorschau prüfen, bestätigen. Die Ausweise gibst du später klassenweise aus.

## G. Cron einrichten

1. Aufruf: `https://deine-domain.de/_cron` höchstens alle 5 Minuten, mit dem Header `X-Api-Key: <CRON_TOKEN>`. Kann der Dienst keine Header senden, geht `https://deine-domain.de/_cron/<CRON_TOKEN>`.
2. Der Strato-Cronjob-Manager führt in der Regel nur eigene PHP-Dateien aus. Reicht das nicht, nimm einen externen Dienst (zum Beispiel cron-job.org), der die URL aufruft.
3. Läuft der Aufruf seltener als alle 15 Minuten, `CRON_GAP_MINUTES` in der `.env` entsprechend erhöhen.
4. Prüfen: **Verwaltung → Systemzustand**, Punkt „Cronjob“ zeigt „zuletzt …“. Dort lässt sich jede Aufgabe auch einmal von Hand ausführen.

## H. Mail prüfen

**Verwaltung → Systemzustand → Testmeldung senden.** Sie muss bei `ALERT_EMAIL` ankommen (auch im Spam-Ordner nachsehen). Danach „Passwort vergessen“ mit einem Testkonto auslösen. Kommt nichts an: `MAIL_HOST`, Port, `MAIL_SCHEME`, Benutzer, Passwort und die Absenderadresse prüfen.

## I. Sicherung und Wiederherstellung üben

1. **Systemzustand → Sicherung jetzt erstellen**, die Datei herunterladen und **außerhalb des Servers** ablegen.
2. In phpMyAdmin (Strato-Kundenbereich) eine **zweite, leere** Datenbank anlegen und die `.sql.gz`-Datei über „Importieren“ einspielen. Tabellen und Zeilen mit der Hauptdatenbank vergleichen. Danach die Test-Datenbank löschen.

## J. Smoke-Test vor dem Start

Mit einem Testkonto und einem Probebuch durchklicken (siehe `docs/GO_LIVE.md`, Abschnitt 7): anmelden, Konto mit Ausweis anlegen, ausleihen, verlängern, zurückgeben, vormerken, Buchwunsch abgeben, Etikett und Ausweis drucken, Handyansicht. Zum Schluss prüfen: `/_setup` zeigt 404, `APP_DEBUG=false`, die Probekonten sind gelöscht.

## K. Updates später

1. Neues Paket bauen (B) und entpacken.
2. Hochladen, aber **nicht überschreiben**: `.env`, `public/covers`, `public/card-designs`, `storage/app/backups`.
3. Kurz `SETUP_TOKEN` in der `.env` setzen, auf `/_setup` „Migrationen ausführen“, Token wieder leeren.

## Wenn etwas nicht klappt

| Problem | Lösung |
| --- | --- |
| Weiße Seite oder Fehler 500 | `storage/logs` auf dem Server ansehen; Rechte von `storage` und `bootstrap/cache` prüfen; `APP_KEY` gesetzt? |
| Seite ohne Gestaltung | Der Ordner `public/build` fehlt oder die Domain zeigt nicht auf `public`. |
| `/_setup` zeigt 404 | `SETUP_TOKEN` fehlt oder hat weniger als 24 Zeichen. |
| Anmeldung springt zurück | `APP_URL` muss `https://` haben, `SESSION_SECURE_COOKIE=true` setzt HTTPS voraus. |
| Keine Mails | SMTP-Daten und Absenderadresse prüfen, Systemzustand → Testmeldung. |
| Import: Datei zu groß | `upload_max_filesize` und `post_max_size` beim Anbieter erhöhen. |
