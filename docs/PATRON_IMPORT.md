# Klassendaten importieren

Klassenleitungen füllen eine einfache Tabelle mit den Schülerdaten ihrer Klasse aus, ihr importiert sie auf einen Schlag. Der Import liegt unter `/betrieb/ausleihkonten/import` (Link „Klassendaten importieren (CSV)“ in der Kontosuche, Recht `patrons.manage`: Mitarbeiter:innen und Verwaltung). Die Vorlage für die Klassenleitungen lädt man dort herunter.

**Ablauf im Betrieb:** Der Import legt **nur die Konten** an, **ohne Ausweis**. Die Ausweise werden erst ausgegeben, wenn die Schüler:innen vor euch stehen (Ausweise klassenweise ausgeben: Klasse wählen, Ausweis neben dem Namen scannen). Einzelne neue Konten legt ihr dagegen unter „Ausleihkonto anlegen“ an: Die Person steht vor euch, ihr scannt dabei gleich den Ausweis, und Konto und Ausweis entstehen zusammen. Das gilt auch für Lehrkräfte und Mitarbeiter:innen, die nicht über den Klassenimport kommen.

## Dateiformat

- CSV mit Kopfzeile. Trennzeichen Semikolon, Komma oder Tabulator (wird erkannt). Zeichensatz UTF-8 (auch mit BOM, so exportiert Excel „CSV UTF-8“) oder Windows-1252.
- Höchstens 5000 Zeilen und 2 MB je Datei.
- Spaltenreihenfolge und Schreibweise der Kopfzeile sind egal. Erkannte Namen (auch Alias): `vorname`; `nachname` (auch `familienname`); `geburtsdatum` (auch `geboren`); `email` (auch `e-mail`, `mail`). Weitere Spalten werden ignoriert.

| Spalte | Pflicht | Inhalt |
|---|---|---|
| `vorname`, `nachname` | ja | Namen |
| `geburtsdatum` | ja | `TT.MM.JJJJ` oder `JJJJ-MM-TT`, nicht in der Zukunft, ab 1900 |
| `email` | nein | E-Mail am Ausleihkonto |

Die **Klasse steht nicht in der Datei**, sondern wird beim Hochladen gewählt (eine aktive Klasse des aktiven Schuljahres). Alle Personen der Datei werden dieser Klasse als Schüler:innen zugeordnet. Die Bibliotheksnummern werden zufällig vergeben (sechs Ziffern ohne Kennung).

Beispiel:

```
vorname;nachname;geburtsdatum;email
Mia;Beispiel;14.03.2014;
Jonas;Muster;2012-11-02;jonas@example.invalid
```

## Ablauf

1. Klasse wählen und Datei hochladen. Es wird nur gespeichert, noch nichts angelegt.
2. Vorschau mit je Zeile einem Status:
   - **Wird angelegt**,
   - **Schon vorhanden**: gleicher Vorname, Nachname und gleiches Geburtsdatum (Schreibweise, Groß-/Kleinschreibung und Umlautformen egal). Vorhandene Konten werden nie geändert, auch nicht ausgeschiedene.
   - **Doppelt in der Datei**: nur die erste Zeile zählt.
   - **Fehler** mit Begründung (ungültiges Datum, fehlender Name, ungültige E-Mail, gewählte Klasse gibt es nicht mehr).
3. Gibt es Fehler, ist der Import gesperrt: Datei korrigieren und erneut hochladen. Sonst bestätigt man per Häkchen.
4. Beim Anlegen wird erneut geprüft. Der Import läuft in einer Transaktion (ganz oder gar nicht), die hochgeladene Datei wird danach gelöscht, im Protokoll steht `patrons.import.committed` mit den Zahlen und der Klasse.

## Zusammenspiel

- Zuerst Schuljahr und Klassen anlegen und aktivieren, dann importieren.
- Online-Konten entstehen nicht automatisch. Danach gibt man wie bisher Verknüpfungscodes aus (`patrons.link-code.issue`).
- Für ein neues Schuljahr werden Schüler:innen nicht neu importiert, sondern über den Schuljahreswechsel versetzt (`docs/SCHOOL_YEAR_TRANSITION.md`). Neue Schüler:innen (z. B. die 5. Klasse) importiert man nach der Aktivierung des Jahres.

## Offen

- Kein Abgleich/Aktualisieren vorhandener Konten (andere Klasse, geänderte E-Mail). Das wäre ein eigener Schritt mit Vorschau der Änderungen.
- Pro Datei eine Klasse. Für mehrere Klassen die Vorlage je Klasse ausfüllen und nacheinander importieren.
