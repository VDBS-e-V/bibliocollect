# Offene Punkte vor dem Start

Stand: v0.12.0. Fehlende Funktionen vor dem Echteinsatz: siehe `docs/VOR_ECHTEINSATZ.md`. ✔ = erledigt, ◐ = teilweise, ☐ = offen. „Du“ heißt: Entscheidung, Text oder Zugang kommt vom Betreiber.

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
| ✔ | Etiketten und Ausweise drucken | `/betrieb/etiketten` (21 je Bogen), Code 128. Ausweise: nicht personalisiert, Zufallsnummer, Avery Zweckform C32016 (85 × 54 mm, 10 je Bogen) mit Vereinslogo, Name zum Selbsteintragen, Rückseite mit Logo, Stapeldruck und CSV-Export; Zuordnung am Tresen (siehe `docs/PROJECT_STATUS.md`). **Du:** Probedruck auf Normalpapier gegen einen Kartenbogen halten |
| ✔ | Ausleihe wie eine Kasse (zwei Bildschirme) | Start: Person oder Rückgabe scannen. Person: Übersicht der Ausleihen mit Verlängern/Zurückgeben, Ausleihe per Scan, gemeinsam bestätigen, Beleg drucken oder per Mail senden |
| ✔ | Statistik | `/betrieb/statistik`: Kennzahlen, Ausleihen je Monat, beliebteste Titel, nach Klasse und Medientyp, Bestand; Zeitraum je Schuljahr, letzte 12 Monate oder gesamt; CSV-Download und Druck. Ein fertiger „Jahresbericht“ als Text fehlt |
| ☐ | Pflege von Signaturen und Themen, Inventur | offen |
| ☐ | Filter „nur verfügbare Titel“ | offen |
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
| ☐ | Fehlerüberwachung | offen (nur Logdateien) |
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
- Google-Bilder speichern: ja/nein.
- Ob Statistik, Buchwünsche und Merkliste zum Start gebraucht werden.
