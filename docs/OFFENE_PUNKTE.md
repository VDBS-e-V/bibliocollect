# Offene Punkte vor dem Start

Stand: v0.8.0. ✔ = erledigt, ◐ = teilweise, ☐ = offen. „Du“ heißt: Entscheidung, Text oder Zugang kommt vom Betreiber.

## A. Muss vor dem Start

| | Punkt | Stand |
|---|---|---|
| ✔ | Passwort vergessen | `/passwort-vergessen`, Link per Mail (60 Min), deaktivierte Konten bekommen keinen Link, immer dieselbe Antwort |
| ✔ | Anmeldeversuche begrenzen | 5 Fehlversuche je Adresse und IP pro Minute |
| ◐ | Impressum, Datenschutz, Barrierefreiheit | Seiten und Bearbeitung unter `/verwaltung/seiten` sind da, die Texte sind Platzhalter. **Du:** Inhalte eintragen |
| ✔ | Leihfrist und Obergrenze je Rolle | `config/circulation.php`: Standard 14 Tage / 5 Medien, Lehrkräfte und Mitarbeiter:innen 28 Tage / 20 Medien, Frist auch je Medientyp. **Du:** Werte bestätigen |
| ✔ | Verlust und Beschädigung | „Problem melden“ an der Ausleihe beendet sie, setzt das Exemplar auf verloren bzw. beschädigt |
| ✔ | Sicherheits-Header, CSP, `composer audit`/`npm audit` | keine bekannten Lücken; strenge Policy im Produktivbetrieb |
| ✔ | Demo-Konten nicht live | `app:doctor` meldet sie im Produktivbetrieb als Fehler |
| ☐ | Wiederherstellung einer Sicherung ausprobieren | **Du:** auf der echten Umgebung testen |

## B. Sollte vor dem Start

| | Punkt | Stand |
|---|---|---|
| ✔ | Etiketten und Ausweise drucken | `/betrieb/etiketten` (21 je Bogen), `/betrieb/ausweise` (10 je Bogen), Code 128 |
| ✔ | Ausleihe wie eine Kasse | `/betrieb/ausleihe`: Person wählen, Positionen sammeln, bestätigen, Beleg drucken oder per Mail senden |
| ☐ | Statistik (Ausleihen, Bestand, Beliebtes, Jahresbericht) | offen |
| ☐ | Pflege von Signaturen und Themen, Inventur | offen |
| ☐ | Filter „nur verfügbare Titel“ | offen |
| ☐ | Buchwünsche, Merkliste/Favoriten | offen |
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
| ☐ | Barrierefreiheit prüfen (Screenreader, Tastatur, Kontraste) | offen |
| ☐ | Mobilansicht auf dem Handy ansehen | **Du** |
| ☐ | Ende-zu-Ende-Browsertests | offen |
| ☐ | Fehlerüberwachung | offen (nur Logdateien) |
| ☐ | Git: pushen, Branches aufräumen, CI prüfen | **Du**, nichts wurde gepusht |
| ☐ | Anleitung für Schüler-AG und Mitarbeiter:innen | offen |

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
- Google-Bilder speichern: ja/nein.
- Ob Statistik, Buchwünsche und Merkliste zum Start gebraucht werden.
