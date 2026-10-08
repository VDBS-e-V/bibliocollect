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

## C. Zugangsdaten und `.env` vorbereiten

1. Drei **verschiedene** Zufallstexte erzeugen (je mindestens 24 Zeichen), je einen für `SETUP_TOKEN`, `CRON_TOKEN` und `STATUS_TOKEN`:

   ```text
   php -r "echo bin2hex(random_bytes(24));"
   ```

   Dreimal ausführen und sofort in den Passwortmanager legen.
2. **Anwendungsschlüssel** erzeugen (im Projektordner): `php artisan key:generate --show`. Ergebnis (`base64:…`) notieren.
3. Im entpackten Ordner die Datei `.env.shared-hosting.example` nach `.env` kopieren und mit einem Texteditor ausfüllen:
   - `APP_URL=https://deine-domain.de` (mit `https://`)
   - `APP_KEY=` der Schlüssel aus Schritt 2
   - `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` aus A.3
   - `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` aus A.4
   - `SETUP_TOKEN`, `CRON_TOKEN`, `STATUS_TOKEN` aus Schritt 1
   - `ALERT_EMAIL=` deine E-Mail-Adresse für Fehlermeldungen
   - `CATALOG_COVER_GOOGLE_BOOKS_KEY=` der Google-Books-Schlüssel (Google Cloud Console → Books API → API-Schlüssel), falls vorhanden
   - **Lassen:** `SESSION_DRIVER=file` und `CACHE_STORE=file` bis zur Einrichtung.

## D. Hochladen

1. Mit einem FTP-Programm (zum Beispiel FileZilla, per SFTP) verbinden.
2. Den **gesamten Inhalt** des entpackten Ordners (einschließlich der versteckten Datei `.env`) in den Ordner `bibliocollect` auf dem Server hochladen. Das sind einige tausend kleine Dateien, mehrere parallele Verbindungen (in FileZilla 5 bis 10) sparen Zeit.
3. Rechte prüfen: `storage` und `bootstrap/cache` samt allen Unterordnern müssen für PHP **beschreibbar** sein (FTP-Programm: Rechtsklick → Dateiberechtigungen → `775`, mit „Unterverzeichnisse einbeziehen“).
4. Der öffentliche Ordner ist `bibliocollect/public`. Prüfe, dass `https://deine-domain.de/` eine Seite zeigt (nicht eine Dateiliste oder einen Fehler).

## E. Einrichten im Browser

1. `https://deine-domain.de/_setup` öffnen. Die Seite gibt es nur, wenn `SETUP_TOKEN` gesetzt ist.
2. **„Migrationen ausführen“** (Token eingeben): legt alle Tabellen an.
3. **„Verwaltungskonto anlegen“**: Name, deine echte E-Mail-Adresse, Passwort mit mindestens 12 Zeichen.
4. **„Einrichtung prüfen“**: Es dürfen keine Fehler stehen. Warnungen zu Cron und Mail erledigst du in G und H.
5. Auf dem Server in der `.env` (per FTP bearbeiten):
   - `SETUP_TOKEN=` **leeren** (die Einrichtungsseite verschwindet)
   - `SESSION_DRIVER=database` und `CACHE_STORE=database` setzen
6. Auf `https://deine-domain.de/anmelden` anmelden.

## F. Grundeinrichtung in der Anwendung

1. **Verwaltung → Schule:** Schuljahr 2026/27 anlegen und aktivieren, dann „Alle Klassen der Schule anlegen“ (47 Klassen).
2. **Verwaltung → Öffnungszeiten:** Wochentage, Zeiten und Schließtage eintragen.
3. **Verwaltung → Regeln:** Leihfristen, Höchstzahlen, Vormerken und Erinnerungen prüfen.
4. **Verwaltung → Seiten:** Impressum, Datenschutz und Barrierefreiheit prüfen und speichern.
5. **Verwaltung → Benutzerkonten:** Mitarbeitende einladen, Rollen vergeben.
6. **Altbestand übernehmen** (Bestand → Altbestand übernehmen): die JSON-Exporte `mediaList`, `mediaTopicList`, `mediaSignatures` hochladen, **Prüfen**, dann **Übernehmen**; danach `bookWishes` für die Buchwünsche. Ist ein Upload zu groß, in der Strato-PHP-Konfiguration `upload_max_filesize` und `post_max_size` erhöhen.
7. **Klassen mit Personen:** Ausleihkonten → Klassendaten importieren, Excel-Vorlage an die Klassenleitungen, pro Klasse hochladen.

## G. Cron einrichten

1. Aufruf: `https://deine-domain.de/_cron` im Takt von höchstens 5 Minuten, mit dem Header `X-Api-Key: <CRON_TOKEN>`. Kann der Dienst keine Header, geht `https://deine-domain.de/_cron/<CRON_TOKEN>`.
2. Der Strato-Cronjob-Manager führt in der Regel nur eigene PHP-Dateien aus. Reicht das nicht, nimm einen externen Dienst (zum Beispiel cron-job.org), der die URL aufruft.
3. Passt das Intervall nicht zu einer Minute, `CRON_GAP_MINUTES` in der `.env` an das Intervall anpassen.
4. Prüfen: Verwaltung → Systemzustand, Punkt „Cronjob“ zeigt „zuletzt …“. Dort kannst du eine Aufgabe auch einmalig ausführen.

## H. Mail prüfen

Verwaltung → Systemzustand → Testmeldung senden. Sie muss bei `ALERT_EMAIL` ankommen. Danach „Passwort vergessen“ mit einem Testkonto probieren.

## I. Sicherung und Wiederherstellung üben

1. Verwaltung → Systemzustand → Sicherung jetzt erstellen, herunterladen und **außerhalb des Servers** ablegen.
2. Einmal die Wiederherstellung üben: in phpMyAdmin eine **zweite, leere** Datenbank anlegen und die `.sql.gz`-Datei über „Importieren“ einspielen. Tabellen und Zeilen vergleichen.

## J. Smoke-Test vor dem Start

Mit einem Testkonto und einem Probebuch durchklicken, siehe `docs/GO_LIVE.md`, Abschnitt 7: anmelden, Konto mit Ausweis anlegen, ausleihen, verlängern, zurückgeben, vormerken, Buchwunsch, Etikett und Ausweis drucken, Handyansicht. Zum Schluss `/_setup` muss 404 zeigen.

## K. Updates später

1. Neues Paket bauen (B).
2. Hochladen, aber **nicht überschreiben**: `.env`, `public/covers`, `public/card-designs`, `storage/app/backups`.
3. Kurz `SETUP_TOKEN` setzen, auf `/_setup` „Migrationen ausführen“, Token wieder leeren.

## Wenn etwas nicht klappt

| Problem | Lösung |
| --- | --- |
| Weiße Seite oder Fehler 500 | `storage/logs` auf dem Server ansehen; Rechte von `storage` und `bootstrap/cache` prüfen; `APP_KEY` gesetzt? |
| Seite ohne Gestaltung | Der Ordner `public/build` fehlt oder die Domain zeigt nicht auf `public`. |
| `/_setup` zeigt 404 | `SETUP_TOKEN` fehlt oder hat weniger als 24 Zeichen. |
| Anmeldung springt zurück | `APP_URL` muss `https://` haben, `SESSION_SECURE_COOKIE=true` setzt HTTPS voraus. |
| Keine Mails | SMTP-Daten und Absenderadresse prüfen, Systemzustand → Testmeldung. |
| Import: Datei zu groß | `upload_max_filesize` und `post_max_size` beim Anbieter erhöhen. |
