# Start im Testeinsatz: Einrichtung Schritt für Schritt

Diese Anleitung führt von einem leeren Webspace zur laufenden Bibliothek. Sie ergänzt `docs/HOSTING_SHARED.md` (Technik) und `docs/OPERATIONS.md` (Betrieb). Die Verwaltungs-Startseite zeigt unter **„Startklar?“**, was davon schon erledigt ist.

## 0. Vorher klären

- Anbieter: PHP 8.4, MySQL/MariaDB, HTTPS, Domain auf `public`, Cron-Aufruf per URL (kleinstes Intervall prüfen), SMTP-Zugang (siehe `docs/HOSTING_SHARED.md`, Abschnitt 1).
- Entscheidungen der Schule: Leihfristen und Höchstzahlen (werden später unter **Verwaltung → Regeln** eingestellt), Rollen (wer bekommt welche Rechte), Google-Bilder speichern ja/nein.
- Texte: Impressum, Datenschutz und Barrierefreiheit (Entwürfe stehen bereit, alle `[BITTE ERGÄNZEN: …]` füllen und rechtlich prüfen lassen).

## 1. Paket und `.env`

1. `powershell -ExecutionPolicy Bypass -File scripts/build-release.ps1`, ZIP entpacken, per FTP hochladen, Domain auf `public` zeigen lassen.
2. `.env.shared-hosting.example` nach `.env` kopieren und ausfüllen: `APP_URL` (https), `APP_KEY` (lokal `php artisan key:generate --show`), Datenbank, SMTP und eine **echte Absenderadresse**, `ALERT_EMAIL`.
3. Drei lange Zufallstexte (mindestens 24 Zeichen) für `SETUP_TOKEN`, `CRON_TOKEN` und `STATUS_TOKEN`, zum Beispiel lokal mit `php -r "echo bin2hex(random_bytes(24));"`.
4. `SESSION_DRIVER=file` und `CACHE_STORE=file` lassen, bis die Datenbank angelegt ist; danach auf `database` umstellen.

## 2. Einrichtungsseite (`/_setup`)

1. **Migrationen ausführen**, 2. **Verwaltungskonto anlegen** (12 Zeichen Passwort), 3. **Einrichtung prüfen**. Alle Zeilen sollten „OK“ sein; offene Warnungen (Mail, Cron) erledigst du in den nächsten Schritten.
2. Danach `SETUP_TOKEN` in der `.env` **leeren** (Updates brauchen ihn nur kurz). Eine ausgesperrte Verwaltung stellst du mit „Zugang wiederherstellen“ auf derselben Seite wieder her.

## 3. Cron einrichten

Beim Anbieter den Aufruf `https://deine-domain/_cron/<CRON_TOKEN>` im Minutentakt (mindestens alle 5 Minuten) einrichten. Er führt Zeitplan (Sicherung 01:30, Fristen 04:00, Erinnerungen 07:00, Cover 03:30) und Warteschlange aus. Prüfen: **Verwaltung → Systemzustand**, Punkt „Cronjob“. Läuft der Anbieter-Cron nur seltener, `CRON_GAP_MINUTES` in der `.env` anpassen.

## 4. Mail prüfen

Lokal mit denselben SMTP-Daten `php artisan mail:test deine@adresse.de`, auf dem Server dann über **Systemzustand → Testmeldung senden**. Ohne Mail funktionieren Einladungen, Bestätigung, Passwort vergessen und Erinnerungen nicht.

## 5. Grundeinrichtung in der Verwaltung (in dieser Reihenfolge)

1. **Schule → Schuljahre:** Schuljahr und Klassen mit Klassenleitung anlegen und aktivieren.
2. **Öffnungszeiten:** Wochentage und Zeiten, Schließtage (Ferien, Studientage).
3. **Regeln:** Leihfristen, Höchstzahlen, Vormerken, Erinnerungen prüfen.
4. **Benutzerkonten:** Mitarbeitende einladen und Rollen vergeben; die Demo-Konten gibt es auf dem Server nicht (sonst löschen).
5. **Seiten:** Impressum, Datenschutz, Barrierefreiheit speichern, danach entfällt der Hinweis „Platzhalter“.

## 6. Bestand und Personen

**Alles ohne Konsole, von Null auf dem Webspace:** Eine frische Strato-Datenbank hat keine Demodaten (die gibt es nur lokal). Der Weg: `/_setup` (Migrationen, Verwaltungskonto) → Verwaltung → Schule (Schuljahr, „Alle Klassen der Schule anlegen“) → **Bestand → „Altbestand übernehmen“** (`/betrieb/katalog/altbestand`): die phpMyAdmin-JSON-Exporte `mediaList`, `mediaTopicList` und `mediaSignatures` des alten Systems hochladen, **prüfen** (es wird noch nichts geschrieben), dann **übernehmen**; darunter die Tabelle `bookWishes` als JSON für die alten Buchwünsche. Beides ist wiederholbar, vorhandene Datensätze werden wiederverwendet. Dateien bis 50 MB; bei sehr großen Exporten das Upload-Limit des Anbieters (`upload_max_filesize`, `post_max_size`) prüfen.

**Alternative mit lokaler Vorbereitung:** `php artisan app:launch-reset` entfernt lokal alle Demo- und Testdaten (der Altbestand bleibt), danach `backup:database` und die `.sql`-Datei über phpMyAdmin in die leere Strato-Datenbank importieren; dann auf `/_setup` das Verwaltungskonto anlegen.

- **Katalog:** Den Altbestand importierst du lokal (`php artisan catalog:legacy:import …`, siehe `docs/LEGACY_CATALOG_IMPORT.md`) und spielst die Datenbank über eine Sicherung ein (`backup:database` lokal → `.sql.gz` in phpMyAdmin importieren). Danach 364 eindeutige Qualitätsvorschläge übernehmen (Katalogqualität), den Rest nach und nach. Einsortieren läuft im Betrieb („Medien einsortieren“), eine Inventur folgt, wenn die Bücher ihre Regalbretter haben.
- **Ausweise:** Unter Ausleihkonten → Ausweise eine Charge erzeugen, einen **Probedruck auf Normalpapier** gegen einen Kartenbogen halten, dann auf Karten drucken.
- **Klassen:** Unter **Verwaltung → Schule** das Schuljahr anlegen und aktivieren, dann „Alle Klassen der Schule anlegen“ (47 Klassen).
- **Konten:** Die Klassenleitungen füllen die **Excel-Vorlage** aus (Vorname, Nachname, Geburtsdatum, E-Mail optional); du importierst pro Klasse unter **Ausleihkonten → Klassendaten importieren** und gibst die Ausweise klassenweise aus, wenn die Klasse da ist. Einzelne Personen legst du mit Ausweis an.

## 7. Probelauf vor dem Start (Smoke-Test)

Mit einem Testkonto und einem Probebuch durchklicken; alles soll ohne Fehlermeldung laufen:

1. Anmelden, Abmelden, „Passwort vergessen“ (Mail kommt an, deutsch).
2. Konto mit Ausweis anlegen; Ausweis scannen → Ausleihbildschirm der Person.
3. Buch ausleihen, Beleg ansehen, Rückgabe scannen, erneut ausleihen und **verlängern**.
4. Titel mit einem Exemplar ausleihen, mit einem zweiten Konto **vormerken**, Rückgabe → „für wen zurücklegen“, Abholung ausleihen.
5. Katalog öffentlich und angemeldet suchen, Filter „nur jetzt verfügbare Titel“, Buchwunsch abgeben und bearbeiten.
6. Medium erfassen (ISBN), Etikett drucken, Medium einsortieren, Inventur für ein Regalbrett.
7. Verlorenes Exemplar melden und wieder verfügbar machen; Klassenliste und Überfällig-Liste öffnen.
8. **Systemzustand:** Sicherung erstellen und herunterladen; **Wiederherstellung üben**: in phpMyAdmin eine leere zweite Datenbank anlegen und die `.sql.gz`-Datei importieren (Tabellen und Zeilen vergleichen).
9. Handy: Startseite, Katalog, Anmeldung, Mein Konto ansehen.
10. `app:doctor` (oder `/_setup` → Prüfen) ohne Fehler.

## 8. Im Alltag

- **Täglich:** Arbeitsplatz-Kacheln (überfällig, abholbereit), Buchwünsche.
- **Wöchentlich:** Systemzustand ansehen, eine Sicherung **außerhalb des Servers** ablegen, Klassenlisten für die Klassenleitungen drucken (sie sind auch der Papierweg, wenn die Anwendung einmal nicht erreichbar ist: bewahre den letzten Ausdruck am Ausleihplatz auf).
- **Bei Fehlern:** Meldung per Mail an `ALERT_EMAIL`, Einzelheiten unter Systemzustand und in `storage/logs`.
- **Updates:** Neues Paket hochladen (nicht `.env`, `public/covers`, `storage/app/backups`), `SETUP_TOKEN` kurz setzen, auf `/_setup` Migrationen ausführen, Token wieder leeren.
- **Ende des Schuljahres:** Verwaltung → Schule → Schuljahreswechsel (vorher wird automatisch gesichert; der Wechsel ist nicht umkehrbar).

## 9. Bekannte Grenzen im Testeinsatz

Der Schuljahreswechsel kennt keinen Teilwechsel und kein Rückgängig, der Konten-Import legt nur an und aktualisiert nichts, Zusammenfassungen und Schlagwörter fehlen weitgehend, Merkliste und Zeitraumsvormerkungen gibt es nicht, Etiketten für Regalbretter und Kamera-Scan folgen später. Was bewusst später kommt, steht in `docs/VOR_ECHTEINSATZ.md`.
