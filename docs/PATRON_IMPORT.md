# Import von Ausleihkonten (v0.6.0)

Das Altsystem kennt nur Medien, keine Ausleihen. Für Schüler:innen, Lehrkräfte und Mitarbeiter:innen gibt es deshalb ein eigenes, einfaches CSV-Format. Der Import liegt unter `/betrieb/ausleihkonten/import` (Link „Aus CSV importieren“ in der Kontosuche, Recht `patrons.manage`: Mitarbeiter:innen und Verwaltung). Eine Vorlage lädt man dort herunter.

## Dateiformat

- CSV mit Kopfzeile. Trennzeichen Semikolon, Komma oder Tabulator (wird erkannt). Zeichensatz UTF-8 (auch mit BOM, so exportiert Excel „CSV UTF-8“) oder Windows-1252.
- Höchstens 5000 Zeilen und 2 MB je Datei.
- Spaltenreihenfolge und Schreibweise der Kopfzeile sind egal. Erkannte Namen (auch Alias): `vorname`; `nachname` (auch `familienname`); `geburtsdatum` (auch `geboren`); `klasse`; `art` (auch `typ`, `rolle`); `email` (auch `e-mail`, `mail`); `bibliotheksnummer` (auch `nummer`, `ausweisnummer`).

| Spalte | Pflicht | Inhalt |
|---|---|---|
| `vorname`, `nachname` | ja | Namen |
| `geburtsdatum` | ja | `TT.MM.JJJJ` oder `JJJJ-MM-TT`, nicht in der Zukunft, ab 1900 |
| `klasse` | für Schüler:innen | Name einer aktiven Klasse im **aktiven** Schuljahr, z. B. `5a`; Groß-/Kleinschreibung und Leerzeichen sind egal |
| `art` | nein | `Schüler:in` (Standard bei leerem Feld), `Lehrkraft`, `Mitarbeiter:in`; der Wortanfang genügt |
| `email` | nein | E-Mail am Ausleihkonto |
| `bibliotheksnummer` | nein | sonst automatisch: `S-…` (ab 10001), `L-…` (ab 20001), `M-…` (ab 30001), jeweils die nächste freie Nummer |

Beispiel:

```
vorname;nachname;geburtsdatum;klasse;art;email;bibliotheksnummer
Mia;Beispiel;14.03.2014;5a;Schüler:in;;
Anna;Lehrerin;21.05.1985;;Lehrkraft;anna@example.invalid;
```

Bei Lehrkräften und Mitarbeiter:innen wird eine angegebene Klasse ignoriert.

## Ablauf

1. Datei hochladen. Es wird nur gespeichert, noch nichts angelegt.
2. Vorschau mit je Zeile einem Status:
   - **Wird angelegt**,
   - **Schon vorhanden**: gleicher Vorname, Nachname und gleiches Geburtsdatum (Schreibweise, Groß-/Kleinschreibung und Umlautformen egal). Vorhandene Konten werden nie geändert, auch nicht ausgeschiedene.
   - **Doppelt in der Datei**: nur die erste Zeile zählt.
   - **Fehler** mit Begründung (unbekannte Klasse, ungültiges Datum oder ungültige Art/E-Mail, fehlende Klasse bei Schüler:innen, vergebene Bibliotheksnummer).
3. Gibt es Fehler, ist der Import gesperrt: Datei korrigieren und erneut hochladen. Sonst bestätigt man per Häkchen.
4. Beim Anlegen wird erneut geprüft. Der Import läuft in einer Transaktion (ganz oder gar nicht), die hochgeladene Datei wird danach gelöscht, im Protokoll steht `patrons.import.committed` mit den Zahlen.

## Zusammenspiel

- Der Import gehört vor den ersten Schuljahreswechsel: Zuerst Schuljahr und Klassen anlegen und aktivieren, dann importieren.
- Online-Konten entstehen nicht automatisch. Danach gibt man wie bisher Verknüpfungscodes aus (`patrons.link-code.issue`).
- Für ein neues Schuljahr werden Schüler:innen nicht neu importiert, sondern über den Schuljahreswechsel versetzt (`docs/SCHOOL_YEAR_TRANSITION.md`). Neue Schüler:innen (z. B. die 5. Klasse) importiert man nach der Aktivierung des Jahres.

## Offen

- Kein Abgleich/Aktualisieren vorhandener Konten (andere Klasse, geänderte E-Mail). Das wäre ein eigener Schritt mit Vorschau der Änderungen.
- Keine Spaltenzuordnung in der Oberfläche; die Kopfzeile muss die genannten Namen tragen.
