---
titel: Update einspielen
kurz: Neue Version von GitHub holen oder hochladen, einspielen, auch automatisch nachts.
bereich: verwaltung
rollen: verwaltung
stichworte: update, version, zip, ftp, wartungsmodus, einspielen, github, release, upload, zu groß, request entity too large, prüfsumme
---

Unter **Verwaltung → Update** spielst du eine neue Version ein. Es gibt drei Wege, das Paket bereitzustellen:

## 1. Von GitHub holen (empfohlen)

1. **Auf neue Version prüfen** drücken. Der Server fragt GitHub nach dem neuesten stabilen Release und zeigt Version, Größe und die Änderungen.
2. **Paket holen** drücken. Der Server lädt das Paket selbst herunter (kein Upload im Browser, daher keine Größengrenze des Webspace) und vergleicht die **Prüfsumme**. Stimmt sie nicht, wird das Paket verworfen.
3. Unter **Bereitliegende Pakete** auf **Jetzt einspielen** klicken.

Der Server muss dafür ausgehende Verbindungen zu `github.com` aufbauen dürfen. Meldet die Seite, dass GitHub nicht erreichbar ist oder Abfragen begrenzt, hilft später ein neuer Versuch oder einer der anderen Wege.

## 2. Hochladen oder per FTP

Das Paket (ZIP) auf der Seite hochladen oder per FTP in `storage/app/updates` legen. Meldet der Server „Request Entity Too Large“, ist die Datei größer als sein Upload-Limit: Dann den FTP-Weg oder Weg 1 nehmen.

## Was beim Einspielen passiert

Die Seite ist dabei kurz im Wartungsmodus, vorher wird die Datenbank gesichert, deine Einstellungen (.env), Cover und Ausweis-Motive bleiben unberührt. Mit **Nachts automatisch einspielen** übernimmt der Cron das um 03:15 Uhr. Zusätzlich kannst du einschalten, dass der Server **neue Versionen nachts selbst von GitHub holt**: Dann genügt ein neues Release, und die Seite aktualisiert sich von allein. Das Ergebnis steht unter „Letztes Update“.
