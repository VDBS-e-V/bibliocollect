# Funktionen, die vor dem Echteinsatz noch fehlen

Stand: v0.12.0 (nach Buchwünschen und vorgangsorientierter Navigation). Diese Liste enthält nur **Funktionen, die gebaut werden müssen**. Aufgaben für den Betreiber (Texte, Zugänge, Tests auf der echten Umgebung) stehen weiter in `docs/OFFENE_PUNKTE.md`.

Die Einstufung ist ein Vorschlag: **Muss** = ohne das würde ich nicht starten, **Sollte** = im ersten Halbjahr, **Kann** = bei Bedarf.

## Muss

| Funktion | Warum | Aufwand |
|---|---|---|
| **Fehlerüberwachung** | Heute gibt es nur Logdateien. Fehler im Betrieb würde niemand bemerken. Gebraucht: Mail an die Administration bei unerwarteten Fehlern (gedrosselt) und eine Seite „Systemzustand“ mit den letzten Fehlern, fehlgeschlagenen Jobs und dem Stand von Cron und Sicherung. | klein bis mittel |
| **Notbetrieb bei Ausfall** | Fällt Internet oder Server aus, steht die Ausleihe. Gebraucht: ein Ausdruck oder eine Datei „Offene Ausleihen und Vormerkungen“ (aktuell, jederzeit abrufbar) und ein Weg, Papierausleihen nachträglich zu erfassen (Datum der Ausleihe frei wählbar). | mittel |
| **Ausleihen nachträglich erfassen** | Gehört zum Notbetrieb: Beim Nachtragen muss das tatsächliche Datum gesetzt werden können, sonst stimmen Fristen und Erinnerungen nicht. | klein |
| **Aussonderung von Exemplaren** | Verloren und beschädigt gibt es, aber kein geführter Ablauf „aussortieren“ (Grund, Datum, Liste für den Jahresbericht, Rückholbarkeit). | klein bis mittel |
| **Benutzerverwaltung für Mitarbeitende prüfen** | Sicherstellen, dass Verwaltung Konten für Mitarbeiter:innen und Schüler-AG anlegt, Rollen vergibt und Konten deaktiviert, ohne Konsole. Falls Lücken: bauen. | klein, erst Prüfung |
| **Signaturen und Themen pflegen** | Etiketten und Regalordnung hängen an Signaturen. Es gibt keine Oberfläche zum Anlegen, Umbenennen und Zusammenführen. | mittel |

## Sollte

| Funktion | Warum | Aufwand |
|---|---|---|
| **Inventur** | Bestand gegen Regal prüfen: Regal scannen, fehlende und falsch einsortierte Exemplare als Liste, Abschluss mit Protokoll. | mittel |
| **Schuljahreswechsel: Teilwechsel und Rückgängig** | Der erste Wechsel im Sommer ist riskant. Gebraucht: Klassen einzeln umstellen und einen Wechsel innerhalb einiger Tage zurücknehmen können. | mittel |
| **Ausleihkonten-Import: vorhandene Konten aktualisieren** | Heute werden nur neue Konten angelegt. Für Nachmeldungen und Korrekturen muss ein erneuter Import Klasse, Name und Mail ändern können. | mittel |
| **Filter „nur verfügbare Titel“** im öffentlichen Katalog | Häufigste Frage von Leser:innen: „Ist das gerade da?“ | klein |
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
| **Zählung der gedruckten Ausweise je Motiv** | Hilft bei gleichmäßigem Bestand; heute stellst du die Prozente von Hand ein. | klein |
| **Gebührenfreie Verlustregeln** (Ersatzbeschaffung, Hinweistexte) | Nur nötig, wenn ihr Verlust regeln wollt. | klein |

## Bereits erledigt (Auswahl)

Ausleihe mit Beleg, Vormerkungen, Verlängerungen, Erinnerungen und Klassenlisten, Ausweise (Zufallsnummern, Stapeldruck, Motive, Registrierung am Tresen, Kontoseite, klassenweise Ausgabe), Schuljahreswechsel, Datenschutz (Anonymisierung, Auskunft), Protokoll, Statistik, Hilfe, Sicherung, Webspace-Paket mit Einrichtungsseite und Cron-Weg.

## Empfohlene Reihenfolge

1. Fehlerüberwachung, Benutzerverwaltung prüfen (klein, sofort sinnvoll)
2. Signaturen und Themen pflegen, Aussonderung
3. Notbetrieb und nachträgliche Erfassung
4. Inventur, Schuljahreswechsel-Rückgängig, Konten-Import mit Aktualisierung
5. Filter „nur verfügbare Titel“, Jahresbericht, Browsertests
