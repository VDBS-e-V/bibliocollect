---
titel: Cron und Aufgaben ohne Konsole
kurz: Was automatisch läuft, wie du Aufgaben von Hand anstößt und was nur über die Konsole geht.
bereich: verwaltung
rollen: verwaltung
stichworte: cron, zeitplan, aufgaben, benchmark, suche messen, konsole, artisan, hoster
---

Unter **Verwaltung → Systemzustand → Cron und Aufgaben** findest du alles, wofür sonst die Konsole nötig wäre.

- **Zeitplan-Aufgaben:** Was nachts und täglich automatisch läuft (Sicherung, Erinnerungen, Cover, Datenqualität, Anonymisierung …), mit dem nächsten Lauf. Jede Aufgabe lässt sich einzeln sofort ausführen. „Cron-Lauf jetzt auslösen“ macht dasselbe wie der Cronjob des Hosters.
- **Weitere Aufgaben (nicht im Zeitplan):** Aufgaben auf Knopfdruck, mit sichtbarer Ausgabe:
  - *Katalogsuche messen* (wie schnell ist die Suche auf diesem Server?),
  - *Reihen neu zuordnen*,
  - *Katalogqualität prüfen* (neue Prüffälle anlegen, ändert keine Katalogdaten),
  - *Anonymisierung: Vorschau* (zählt nur, ändert nichts),
  - *Einrichtung prüfen* (PHP, Datenbank, Rechte, Warteschlange, Zeitplan, Mail).
- Jede Ausführung steht im Protokoll.

**Was bewusst nur über die Konsole geht** (nicht über die Webseite, weil es Daten überschreibt oder nur bei der Einrichtung gebraucht wird): Datenbank aus einer Sicherung zurückspielen (`backup:restore`), Demodaten entfernen (`app:launch-reset`), Bibliotheksnummern neu vergeben (`patrons:renumber`), Altbestand-Analyse (`catalog:legacy:*`), Einrichtungshilfen für die `.env` (`env:*`).
