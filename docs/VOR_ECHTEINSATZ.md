# Funktionen, die vor dem Echteinsatz noch fehlen

Stand: v0.20.0. Diese Liste enthält nur **Funktionen, die gebaut werden müssen**. Aufgaben für den Betreiber (Texte, Zugänge, Tests auf der echten Umgebung) stehen weiter in `docs/OFFENE_PUNKTE.md`.

Die Einstufung ist ein Vorschlag: **Muss** = ohne das würde ich nicht starten, **Sollte** = im ersten Halbjahr, **Kann** = bei Bedarf.

## Muss

Alle Punkte der früheren „Muss“-Liste sind umgesetzt. Ein Notbetrieb (Papierliste, Nachtragen) wurde gebaut und auf Wunsch wieder entfernt (v0.23.0, zurückgenommen in v0.23.1).

| Funktion | Stand |
|---|---|
| Fehlerüberwachung und Systemzustand | ✔ v0.17.0: Seite „Systemzustand“, Mails bei Fehlern, fehlgeschlagenen Jobs und Cron-Ausfall, Statusadresse `/_status` |
| Benutzerverwaltung für Mitarbeitende | ✔ v0.18.0: Konten anlegen und einladen, Rollen, Deaktivieren, Schutz vor Aussperren |
| Signaturen und Themen pflegen, Bücher zuordnen | ✔ v0.19.0, mit Regalbrett-Vorschlag beim Einsortieren |
| Aussonderung von Exemplaren | ✔ v0.20.0 |
| Notbetrieb bei Ausfall | entfällt (bewusst entfernt) |

## Sollte

| Funktion | Warum | Aufwand |
|---|---|---|
| ~~Inventur~~ | ✔ umgesetzt (v0.22.0) | |
| **Schuljahreswechsel: Teilwechsel und Rückgängig** | Der erste Wechsel im Sommer ist riskant. Gebraucht: Klassen einzeln umstellen und einen Wechsel innerhalb einiger Tage zurücknehmen können. | mittel |
| **Ausleihkonten-Import: vorhandene Konten aktualisieren** | Heute werden nur neue Konten angelegt. Für Nachmeldungen und Korrekturen muss ein erneuter Import Klasse, Name und Mail ändern können. | mittel |
| ~~Filter „nur verfügbare Titel“~~ | ✔ umgesetzt (v0.21.0) | |
| **Ende-zu-Ende-Browsertests** für Ausleihe, Ausweis-Registrierung und Rückgabe | Die Tests laufen heute ohne echten Browser. Bei den Kernabläufen am Tresen lohnt sich das. | mittel |
| **Installationsanleitung für die VM** | Für den Fall, dass der Webspace nicht reicht. | klein |
| **Jahresbericht als Text** | Statistik gibt Zahlen, aber keinen fertigen Bericht für Verein und Schulleitung. | klein |
| **Anreicherung** (Zusammenfassungen, Schlagwörter) | Fehlen fast überall und machen die Suche schwächer. | mittel |

## Geplant, wartet auf Angaben

| Funktion | Stand |
|---|---|
| **Etiketten für Regalbretter** (Strichcode der Bezeichnung, zum Scannen beim Einsortieren) | Kommt. **Du:** Maße und Etikettenformat heraussuchen |
| **Kamera-Scan am Handy** (Einsortieren, später auch Ausleihe) | Kommt später; die Seite „Medien einsortieren“ ist dafür vorbereitet |
| **Vorschlag des Regalbretts je Buch** nach Themenbereich | Braucht die Zuordnung der Bücher zu Themenbereichen, die es noch nicht gibt |

## Kann

| Funktion | Warum | Aufwand |
|---|---|---|
| **Merkliste/Favoriten** | Schön für Leser:innen, für den Start nicht nötig. (Buchwünsche sind seit v0.12.0 umgesetzt.) | mittel |
| **Zeitraumsvormerkungen und Click & Collect** | Wird erst gebraucht, wenn die Nachfrage das zeigt. | mittel |
| **Zählung der gedruckten Ausweise je Motiv** | Hilft bei gleichmäßigem Bestand; heute wählst du je Motiv „normal“, „mehr“ oder „auslassen“ beim Drucken von Hand. | klein |
| **Gebührenfreie Verlustregeln** (Ersatzbeschaffung, Hinweistexte) | Nur nötig, wenn ihr Verlust regeln wollt. | klein |

## Bereits erledigt (Auswahl)

Ausleihe mit Beleg, Vormerkungen, Verlängerungen, Erinnerungen und Klassenlisten, Ausweise (Zufallsnummern, Stapeldruck, Motive, Registrierung am Tresen, Kontoseite, klassenweise Ausgabe), Schuljahreswechsel, Datenschutz (Anonymisierung, Auskunft), Protokoll, Statistik, Hilfe, Sicherung, Webspace-Paket mit Einrichtungsseite und Cron-Weg.

## Empfohlene Reihenfolge

1. Fehlerüberwachung, Benutzerverwaltung prüfen (klein, sofort sinnvoll)
2. Signaturen und Themen pflegen, Aussonderung
4. Inventur, Schuljahreswechsel-Rückgängig, Konten-Import mit Aktualisierung
5. Filter „nur verfügbare Titel“, Jahresbericht, Browsertests
