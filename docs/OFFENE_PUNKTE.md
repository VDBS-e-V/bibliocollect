# Offene Punkte vor dem Start

Stand: v0.37.0. Ablauf der Einrichtung für den Testeinsatz: `docs/GO_LIVE.md`. Funktionen, die bewusst später kommen: `docs/VOR_ECHTEINSATZ.md`. ✔ = erledigt, ◐ = teilweise, ☐ = offen. „Du“ heißt: Entscheidung, Text oder Zugang kommt vom Betreiber.

## A. Muss vor dem Start

| | Punkt | Stand |
|---|---|---|
| ✔ | Passwort vergessen | `/passwort-vergessen`, Link per Mail (60 Min), deaktivierte Konten bekommen keinen Link, immer dieselbe Antwort |
| ✔ | Anmeldeversuche begrenzen | 5 Fehlversuche je Adresse und IP pro Minute |
| ◐ | Impressum, Datenschutz, Barrierefreiheit | Ausführliche Entwürfe stehen unter `/verwaltung/seiten` (Quelle: `resources/content/legal/`). **Du:** alle Angaben in `[BITTE ERGÄNZEN: …]` ausfüllen, die Texte rechtlich prüfen lassen und speichern; erst dann entfällt der Hinweis „Platzhalter“ |
| ✔ | Leihfrist und Obergrenze je Rolle | `config/circulation.php`: Standard 14 Tage / 5 Medien, Lehrkräfte und Mitarbeiter:innen 28 Tage / 20 Medien, Frist auch je Medientyp. **Du:** Werte bestätigen |
| ✔ | Verlust und Beschädigung | „Problem melden“ an der Ausleihe beendet sie, setzt das Exemplar auf verloren bzw. beschädigt |
| ✔ | Sicherheits-Header, CSP, `composer audit`/`npm audit` | keine bekannten Lücken; strenge Policy im Produktivbetrieb |
| ✔ | Demo-Konten nicht live | `app:doctor` meldet sie im Produktivbetrieb als Fehler |
| ☐ | Wiederherstellung einer Sicherung ausprobieren | **Du:** auf der echten Umgebung testen |

## B. Sollte vor dem Start

| | Punkt | Stand |
|---|---|---|
| ✔ | Etiketten und Ausweise drucken | `/betrieb/etiketten` (24 je Bogen, 70 × 36 mm), Etiketten auf Vorrat unter `/betrieb/etiketten/vorrat`, Code 128. Ausweise: nicht personalisiert, Zufallsnummer, Avery Zweckform C32016 (85 × 54 mm, 10 je Bogen) mit Vereinslogo, Name zum Selbsteintragen, Rückseite mit Logo, Stapeldruck und CSV-Export; Zuordnung am Tresen (siehe `docs/PROJECT_STATUS.md`). **Du:** Probedruck auf Normalpapier gegen einen Kartenbogen halten |
| ✔ | Ausleihe wie eine Kasse (zwei Bildschirme) | Start: Person oder Rückgabe scannen. Person: Übersicht der Ausleihen mit Verlängern/Zurückgeben, Ausleihe per Scan, gemeinsam bestätigen, Beleg drucken oder per Mail senden |
| ✔ | Statistik | `/betrieb/statistik`: Kennzahlen, Ausleihen je Monat, beliebteste Titel, nach Klasse und Medientyp, Bestand; Zeitraum je Schuljahr, letzte 12 Monate oder gesamt; CSV-Download und Druck. Ein fertiger „Jahresbericht“ als Text fehlt |
| ✔ | Pflege von Signaturen und Themen, Inventur | Signaturen und Themen (v0.19.0), Inventur (v0.22.0) |
| ✔ | Filter „nur verfügbare Titel“ | v0.21.0 |
| ◐ | Buchwünsche, Merkliste/Favoriten | Buchwünsche sind da (Portal, Arbeitsplatz, Mails, Datenschutz). Merkliste/Favoriten offen |
| ☐ | Ausleihkonten-Import: vorhandene Konten aktualisieren | offen |
| ☐ | Schuljahreswechsel: Teilwechsel, Rückgängig | offen |
| ☐ | Zeitraumsvormerkungen / Click & Collect | offen |

## C. Katalogdaten

| | Punkt | Stand |
|---|---|---|
| ◐ | 732 Qualitätsfälle | Vorschläge geholt, 364 eindeutige per Klick übernehmbar. **Du:** bestätigen, Rest einzeln prüfen |
| ☐ | Zusammenfassungen und Schlagwörter | fehlen fast überall (Anreicherung) |
| ☐ | Google-Books-Key und Nutzungsbedingungen | **Du** |

## D. Qualität und Technik

| | Punkt | Stand |
|---|---|---|
| ◐ | Barrierefreiheit | Automatische Prüfung aller Hauptseiten im Test (Sprache, eine Hauptüberschrift, beschriftete Felder, Tabellenköpfe, alt-Texte, eindeutige IDs); dabei wurden doppelte IDs auf der Seite „Schule“ behoben. **Du:** Prüfung mit Screenreader, Tastatur und Kontrasten; dann Erklärung ausfüllen |
| ☐ | Mobilansicht auf dem Handy ansehen | **Du** |
| ☐ | Ende-zu-Ende-Browsertests | offen |
| ✔ | Fehlerüberwachung | Seite „Systemzustand“, Mails bei Fehlern und Cron-Ausfall, Statusadresse (v0.17.0) |
| ☐ | Git: pushen, Branches aufräumen, CI prüfen | **Du**, nichts wurde gepusht |
| ✔ | Anleitung für Schüler-AG und Mitarbeiter:innen | In der Anwendung unter „Hilfe“ (`/betrieb/hilfe`): Ausleihe, Katalog und Ausleihkonten, Verwaltung (`resources/help/*.md`). **Du:** lesen und an die Praxis anpassen; eine Schulung ersetzt das nicht |

## E. Betrieb

| | Punkt | Stand |
|---|---|---|
| ◐ | Webspace ohne SSH | Paket, `/_setup`, `/_cron`, Cover ohne Symlink sind da (`docs/HOSTING_SHARED.md`). **Du:** PHP 8.4 und Voraussetzungen beim Anbieter prüfen |
| ☐ | Installationsanleitung für die VM | offen |
| ☐ | SMTP, `APP_URL`, `.env`, Tokens | **Du** |
| ☐ | Echte Daten einspielen | Schuljahr, Klassen, Öffnungszeiten, Schülerliste, Rollen. **Du** |
| ☐ | Release-Paket auf einem echten Server testen | offen |

## F. Entscheidungen

- Werte der Leihfristen und Obergrenzen, Verlängerungen (2×, nicht bei Überfälligkeit), Abholfrist (7 Tage), Erinnerungen (2 Tage vorher, wöchentlich bei Überfälligkeit).
- Rollen-Matrix (besonders Schüler-AG Basis und Erweitert).
- Texte für Impressum, Datenschutz, Barrierefreiheitserklärung, Mails.
- ~~Google-Bilder speichern~~: entschieden, ja. Offen bleibt nur der API-Key `CATALOG_COVER_GOOGLE_BOOKS_KEY` (Google Cloud Console → Books API → API-Schlüssel) in der `.env` auf dem Server.
- Ob Statistik, Buchwünsche und Merkliste zum Start gebraucht werden.
