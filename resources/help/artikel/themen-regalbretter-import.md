---
titel: Themen und Regalbretter importieren
kurz: Themenliste und Regalsignaturen aus JSON-Dateien prüfen und übernehmen, ohne Konsole.
bereich: verwaltung
rollen: mitarbeiter, verwaltung
stichworte: import, themen, regalbretter, signaturen, json, klassifikation, altsystem
---

Unter **Verwaltung → Regalbretter → „Themen und Regalbretter importieren“** übernimmst du die Themenliste (`mediaTopicList.json`) und die Regalsignaturen (`mediaSignatures.json`) aus dem Altsystem.

1. **Sicherung erstellen** (Systemzustand → „Jetzt sichern“).
2. Eine oder beide Dateien auswählen und **prüfen**. Die Vorschau zeigt neue Themen und Regalbretter, Abweichungen vom Bestand, Warnungen und Fehler. Dabei wird nichts geschrieben.
3. Gibt es **Fehler**, ist der Import nicht möglich: Datei korrigieren und neu hochladen.
4. Sonst den Import **bestätigen**. Er läuft in einem Schritt, ganz oder gar nicht. Danach zeigt ein Bericht die Zahlen.

Wichtig: Medien, Ausleihen, Vormerkungen und Inventarnummern werden nie verändert, vorhandene Regalbretter behalten Reihenfolge und Schalter, nichts wird gelöscht. Weicht ein vorhandenes Thema von der Datei ab, bleibt es unverändert, außer du wählst ausdrücklich „Abweichungen übernehmen“. Den Import derselben Dateien kannst du gefahrlos wiederholen.
