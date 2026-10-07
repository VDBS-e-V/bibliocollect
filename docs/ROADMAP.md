# Entwicklungsplan — BiblioCollect

Stand: v0.37.0. Dieses Dokument gibt nur die Richtung vor. Den genauen Stand je Funktion nennt `docs/PROJECT_STATUS.md`, die Einrichtung für den Testeinsatz `docs/GO_LIVE.md`, offene Aufgaben für den Betreiber `docs/OFFENE_PUNKTE.md` und das, was bewusst später kommt, `docs/VOR_ECHTEINSATZ.md`.

## Erledigt

- **Fundament:** Module und Oberflächen (Öffentlich, Portal, Bibliotheksbetrieb, Verwaltung), Rollen und Rechte, Protokoll, Öffnungszeiten und Schließtage, Datenschutz (Anonymisierung nach drei Jahren, Auskunft, sofortige Anonymisierung ausgeschiedener Konten).
- **Katalog:** Altbestandsimport, Erfassung mit DNB-Abfrage, Katalogqualität, Cover, Signaturen und Themenbereiche, Regalbretter, Einsortieren, Inventur, Aussonderung, Etiketten, Zugänglichkeit der Exemplare.
- **Ausleihe:** Ausleihe, Rückgabe, Verlängern, Vormerken (eindeutig, Warteschlange begrenzt), Beleg, Erinnerungen per Mail, Klassenlisten, Überfällig-Liste, Verlust und Wiederfinden, Buchwünsche.
- **Personen:** Ausweise (Zufallsnummern, Stapeldruck, Motive), Konto mit Ausweis, Klassendaten-Import, Schuljahreswechsel, Onlinekonto und Portal.
- **Betrieb:** Systemzustand mit Fehlermeldungen, Cron per URL, Sicherung und Wiederherstellung (SQLite und MySQL/MariaDB), Seite „Regeln“ im Web, Einrichtungsseite ohne SSH, Release-Paket, `env:*`-Befehle.

## Als Nächstes (nach dem Start des Testeinsatzes, je nach Erfahrung)

1. Schuljahreswechsel: Teilwechsel und Rückgängig.
2. Konten-Import mit Aktualisierung vorhandener Konten, mehrere Klassen je Datei.
3. Anreicherung des Katalogs (Zusammenfassungen, Schlagwörter).
4. Etiketten für Regalbretter (sobald das Etikettenformat feststeht), Kamera-Scan am Handy.
5. Automatisierte Browsertests für Ausleihe, Ausweis-Registrierung und Rückgabe.
6. Jahresbericht als Text.

## Später, bei Bedarf

Merkliste, Zeitraumsvormerkungen und Click & Collect, MARC21-Import, Veranstaltungen und Leselisten, Zählung gedruckter Ausweise je Motiv. Mahnungen und Gebühren sind bewusst nicht vorgesehen.
